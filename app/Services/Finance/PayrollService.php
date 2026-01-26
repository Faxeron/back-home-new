<?php

namespace App\Services\Finance;

use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use App\Domain\CRM\Models\ContractStatus;
use App\Domain\Finance\Models\PayrollAccrual;
use App\Domain\Finance\Models\PayrollRule;
use App\Domain\Finance\Models\FinanceAllocation;
use App\Domain\Finance\Models\Receipt;
use App\Domain\Finance\Models\Spending;
use Illuminate\Support\Collection;

class PayrollService
{
    private const COMPLETED_CODES = ['COMPLETED', 'DONE', 'FINISHED', 'DONE_WORK', 'DONE_MONTAGE'];
    private const CANCELLED_CODES = ['CANCELLED', 'CANCELED', 'CANCEL'];

    public function accrueFixedForContract(Contract $contract, ?int $actorId = null): void
    {
        $managerId = $this->resolveManagerId($contract);
        if (!$managerId) {
            return;
        }

        $contract->loadMissing(['documents.template.productTypes']);
        foreach ($contract->documents as $document) {
            $rule = $this->resolveRule($contract, $managerId, $document->document_type ?? 'combined');
            if (!$rule || $rule['fixed_amount'] <= 0) {
                continue;
            }

            $existing = PayrollAccrual::query()
                ->where('contract_id', $contract->id)
                ->where('contract_document_id', $document->id)
                ->where('type', 'fixed')
                ->where('source', 'system')
                ->where('status', 'active')
                ->first();

            if ($existing) {
                $existing->update([
                    'amount' => $rule['fixed_amount'],
                    'rule_id' => $rule['rule_id'],
                    'document_type' => $document->document_type,
                    'status' => $existing->paid_amount >= $existing->amount ? 'paid' : 'active',
                    'updated_by' => $actorId,
                ]);
                continue;
            }

            PayrollAccrual::query()->create([
                'tenant_id' => $contract->tenant_id,
                'company_id' => $contract->company_id,
                'user_id' => $managerId,
                'contract_id' => $contract->id,
                'contract_document_id' => $document->id,
                'rule_id' => $rule['rule_id'],
                'document_type' => $document->document_type,
                'type' => 'fixed',
                'source' => 'system',
                'status' => 'active',
                'base_amount' => 0,
                'percent' => null,
                'amount' => $rule['fixed_amount'],
                'comment' => 'Фикс за создание договора',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }
    }

    public function accrueMarginForContract(Contract $contract, ?int $actorId = null): void
    {
        $managerId = $this->resolveManagerId($contract);
        if (!$managerId) {
            return;
        }

        $contract->loadMissing(['documents.template.productTypes', 'items']);
        if ($contract->documents->isEmpty()) {
            return;
        }

        $actualRevenue = (float) Receipt::query()
            ->where('contract_id', $contract->id)
            ->sum('sum');

        $allocationQuery = FinanceAllocation::query()
            ->where('contract_id', $contract->id)
            ->whereNotNull('spending_id');

        if ($allocationQuery->exists()) {
            $actualExpense = (float) $allocationQuery->sum('amount');
        } else {
            $actualExpense = (float) Spending::query()
                ->where('contract_id', $contract->id)
                ->sum('sum');
        }

        $margin = $actualRevenue - $actualExpense;

        $documentTotals = $this->buildDocumentRevenueMap($contract);
        $totalPlanned = array_sum($documentTotals);
        $docCount = max(1, $contract->documents->count());

        foreach ($contract->documents as $document) {
            $rule = $this->resolveRule($contract, $managerId, $document->document_type ?? 'combined');
            if (!$rule || $rule['margin_percent'] <= 0) {
                continue;
            }

            $planned = (float) ($documentTotals[$document->id] ?? 0);
            $share = $totalPlanned > 0 ? ($planned / $totalPlanned) : (1 / $docCount);
            $base = $margin * $share;
            $amount = $base * ($rule['margin_percent'] / 100);

            $existing = PayrollAccrual::query()
                ->where('contract_id', $contract->id)
                ->where('contract_document_id', $document->id)
                ->where('type', 'margin_percent')
                ->where('source', 'system')
                ->where('status', 'active')
                ->first();

            if ($existing) {
                $existing->update([
                    'base_amount' => $base,
                    'percent' => $rule['margin_percent'],
                    'amount' => $amount,
                    'rule_id' => $rule['rule_id'],
                    'document_type' => $document->document_type,
                    'status' => $existing->paid_amount >= $existing->amount ? 'paid' : 'active',
                    'updated_by' => $actorId,
                ]);
                continue;
            }

            PayrollAccrual::query()->create([
                'tenant_id' => $contract->tenant_id,
                'company_id' => $contract->company_id,
                'user_id' => $managerId,
                'contract_id' => $contract->id,
                'contract_document_id' => $document->id,
                'rule_id' => $rule['rule_id'],
                'document_type' => $document->document_type,
                'type' => 'margin_percent',
                'source' => 'system',
                'status' => 'active',
                'base_amount' => $base,
                'percent' => $rule['margin_percent'],
                'amount' => $amount,
                'comment' => 'Процент от маржи',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }
    }

    public function handleStatusChange(
        Contract $contract,
        ?int $previousStatusId,
        int $newStatusId,
        ?int $actorId = null
    ): void {
        $nextStatus = ContractStatus::query()->find($newStatusId);
        $previousStatus = $previousStatusId ? ContractStatus::query()->find($previousStatusId) : null;

        if ($nextStatus && $this->isCancelledStatus($nextStatus)) {
            $this->cancelSystemAccruals($contract, $actorId, ['fixed', 'margin_percent']);
            return;
        }

        if ($nextStatus && $this->isCompletedStatus($nextStatus)) {
            $this->accrueFixedForContract($contract, $actorId);
            $this->accrueMarginForContract($contract, $actorId);
            return;
        }

        if ($previousStatus && $this->isCompletedStatus($previousStatus)) {
            $this->cancelSystemAccruals($contract, $actorId, ['margin_percent']);
        }
    }

    public function cancelSystemAccruals(Contract $contract, ?int $actorId = null, array $types = []): void
    {
        $query = PayrollAccrual::query()
            ->where('contract_id', $contract->id)
            ->where('source', 'system')
            ->where('status', 'active');

        if (!empty($types)) {
            $query->whereIn('type', $types);
        }

        $query->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $actorId,
        ]);
    }

    public function createManualAccrual(
        Contract $contract,
        string $type,
        float $amount,
        ?string $comment,
        ?int $actorId = null
    ): PayrollAccrual {
        $managerId = $this->resolveManagerId($contract);
        $normalizedType = $type === 'penalty' ? 'penalty' : 'bonus';
        $value = abs($amount);
        if ($normalizedType === 'penalty') {
            $value = $value * -1;
        }

        return PayrollAccrual::query()->create([
            'tenant_id' => $contract->tenant_id,
            'company_id' => $contract->company_id,
            'user_id' => $managerId ?? $actorId ?? 0,
            'contract_id' => $contract->id,
            'contract_document_id' => null,
            'rule_id' => null,
            'document_type' => null,
            'type' => $normalizedType,
            'source' => 'manual',
            'status' => 'active',
            'base_amount' => 0,
            'percent' => null,
            'amount' => $value,
            'comment' => $comment,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    private function resolveManagerId(Contract $contract): ?int
    {
        return $contract->manager_id ?: $contract->created_by ?: null;
    }

    private function resolveRule(Contract $contract, int $userId, string $documentType): ?array
    {
        $rule = PayrollRule::query()
            ->where('tenant_id', $contract->tenant_id)
            ->where('company_id', $contract->company_id)
            ->where('user_id', $userId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();

        if ($rule) {
            return [
                'rule_id' => $rule->id,
                'fixed_amount' => (float) $rule->fixed_amount,
                'margin_percent' => (float) $rule->margin_percent,
            ];
        }

        return null;
    }

    private function buildDocumentRevenueMap(Contract $contract): array
    {
        $items = $contract->items ?? collect();
        $map = [];

        foreach ($contract->documents as $document) {
            $typeIds = $this->extractTemplateTypeIds($document);
            $sum = 0.0;

            foreach ($items as $item) {
                $itemTypeId = (int) ($item->product_type_id ?? 0);
                if (empty($typeIds) || in_array($itemTypeId, $typeIds, true)) {
                    $sum += (float) ($item->total ?? 0);
                }
            }

            $map[$document->id] = $sum;
        }

        return $map;
    }

    private function extractTemplateTypeIds(ContractDocument $document): array
    {
        $template = $document->template;
        if (!$template || !method_exists($template, 'productTypes')) {
            return [];
        }

        return $template->productTypes->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    private function isCompletedStatus(ContractStatus $status): bool
    {
        $code = strtoupper((string) $status->code);
        if ($code !== '' && in_array($code, self::COMPLETED_CODES, true)) {
            return true;
        }

        $name = mb_strtolower((string) $status->name);
        return str_contains($name, 'выполн');
    }

    private function isCancelledStatus(ContractStatus $status): bool
    {
        $code = strtoupper((string) $status->code);
        if ($code !== '' && in_array($code, self::CANCELLED_CODES, true)) {
            return true;
        }

        $name = mb_strtolower((string) $status->name);
        return str_contains($name, 'отмен') || str_contains($name, 'аннули');
    }
}

