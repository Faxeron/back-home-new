<?php

namespace App\Http\Controllers\Api;

use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractStatusChange;
use App\Domain\Finance\Models\FinanceAuditLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractHistoryController extends Controller
{
    public function index(Request $request, int $contract): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        Contract::query()
            ->where('id', $contract)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $statusChanges = ContractStatusChange::query()
            ->with(['previousStatus', 'newStatus', 'changedBy'])
            ->where('contract_id', $contract)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->get()
            ->map(function (ContractStatusChange $change) {
                $prev = $change->previousStatus?->name;
                $next = $change->newStatus?->name;
                $title = $prev
                    ? sprintf('Статус: %s -> %s', $prev, $next ?? '-')
                    : sprintf('Статус: %s', $next ?? '-');

                return [
                    'id' => 'status-' . $change->id,
                    'created_at' => $change->changed_at?->toISOString(),
                    'title' => $title,
                    'user' => $change->changedBy
                        ? [
                            'id' => $change->changedBy->id,
                            'name' => $change->changedBy->name,
                            'email' => $change->changedBy->email,
                        ]
                        : null,
                ];
            })
            ->values()
            ->toBase();

        $auditLogs = FinanceAuditLog::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('payload->contract_id', $contract)
            ->whereIn('action', [
                'contract.created',
                'contract.updated',
                'contract_receipt.created',
                'spending.created',
                'spending.deleted',
                'contract_document.created',
                'contract_document.deleted',
            ])
            ->orderByDesc('created_at')
            ->get();

        $userIds = $auditLogs->pluck('user_id')->filter()->unique()->values();
        $users = $userIds->isEmpty()
            ? collect()
            : DB::connection('legacy_new')
                ->table('users')
                ->whereIn('id', $userIds->all())
                ->get(['id', 'name', 'email'])
                ->keyBy('id');

        $auditItems = $auditLogs->map(function (FinanceAuditLog $log) use ($users) {
            $payload = $log->payload ?? [];
            $sum = $payload['sum'] ?? null;
            $sumLabel = $sum !== null ? $this->formatSum($sum) : null;

            return [
                'id' => 'audit-' . $log->id,
                'created_at' => $log->created_at?->toISOString(),
                'title' => $this->formatAuditTitle($log->action, $sumLabel, $payload),
                'user' => $log->user_id
                    ? [
                        'id' => $log->user_id,
                        'name' => $users[$log->user_id]->name ?? null,
                        'email' => $users[$log->user_id]->email ?? null,
                    ]
                    : null,
            ];
        });

        $items = collect($statusChanges)
            ->merge($auditItems)
            ->unique(function (array $item) {
                $userId = $item['user']['id'] ?? '';

                return implode('|', [
                    $item['created_at'] ?? '',
                    $item['title'] ?? '',
                    $userId,
                ]);
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
        ]);
    }

    private function formatAuditTitle(string $action, ?string $sumLabel, array $payload = []): string
    {
        return match ($action) {
            'contract.created' => 'Договор создан (черновик)',
            'contract.updated' => 'Данные договора обновлены',
            'contract_receipt.created' => $sumLabel
                ? sprintf('Добавлен приход: %s', $sumLabel)
                : 'Добавлен приход',
            'spending.created' => $sumLabel
                ? sprintf('Добавлен расход: %s', $sumLabel)
                : 'Добавлен расход',
            'spending.deleted' => 'Расход удален',
            'contract_document.created' => $this->formatDocumentTitle('Сформирован документ', $payload),
            'contract_document.deleted' => $this->formatDocumentTitle('Документ удален', $payload),
            default => $action,
        };
    }

    private function formatSum($value): string
    {
        $amount = is_numeric($value) ? (float) $value : 0.0;

        return number_format($amount, 0, '.', ' ') . ' ₽';
    }

    private function formatDocumentTitle(string $base, array $payload): string
    {
        $templateName = trim((string) ($payload['template_name'] ?? ''));

        if ($templateName !== '') {
            return sprintf('%s: %s', $base, $templateName);
        }

        return $base;
    }
}
