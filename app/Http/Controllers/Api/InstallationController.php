<?php

namespace App\Http\Controllers\Api;

use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractStatus;
use App\Domain\Common\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InstallationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()
            ->with(['counterparty', 'worker', 'status'])
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) {
                $q->whereNotNull('work_start_date')
                    ->orWhereNotNull('work_end_date')
                    ->orWhereNotNull('work_done_date');
            });

        if ($request->filled('worker_id')) {
            $query->where('worker_id', $request->integer('worker_id'));
        }

        $query->orderByDesc('id');

        $perPage = (int) $request->input('per_page', 0);
        $page = (int) $request->input('page', 1);
        $paginator = null;
        if ($perPage > 0) {
            $paginator = $query->paginate($perPage, ['*'], 'page', max(1, $page));
            $contracts = $paginator->getCollection();
        } else {
            $contracts = $query->get();
        }

        $rows = $contracts->map(function (Contract $contract) use ($user) {
            $status = $this->resolveStatus($contract);
            $workStart = $contract->work_start_date ?? $contract->work_done_date;
            $workEnd = $contract->work_end_date ?? $contract->work_done_date;

            return [
                'contract_id' => $contract->id,
                'contract_title' => $contract->title,
                'counterparty_name' => $contract->counterparty?->name,
                'address' => $contract->address,
                'work_start_date' => $workStart?->toDateString(),
                'work_end_date' => $workEnd?->toDateString(),
                'work_done_date' => $contract->work_done_date?->toDateString(),
                'worker_id' => $contract->worker_id,
                'worker_name' => $contract->worker?->name,
                'status' => $status['code'],
                'status_label' => $status['label'],
                'can_edit' => $this->canSchedule($user, $contract),
            ];
        });

        if ($paginator) {
            return response()->json([
                'data' => $rows->values(),
                'meta' => [
                    'total' => $paginator->total(),
                ],
            ]);
        }

        return response()->json(['data' => $rows->values()]);
    }

    public function update(Request $request, int $contract): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $model = Contract::query()
            ->with(['status', 'counterparty', 'worker'])
            ->where('id', $contract)
            ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->firstOrFail();

        if (!$this->canSchedule($user, $model)) {
            abort(403, 'Not allowed to schedule installations.');
        }

        $validated = $request->validate([
            'work_done_date' => ['required', 'date'],
            'worker_id' => ['required', 'integer'],
        ]);

        $model->work_done_date = $validated['work_done_date'];
        $model->worker_id = $validated['worker_id'];
        $model->save();

        return response()->json([
            'data' => [
                'contract_id' => $model->id,
                'work_done_date' => $model->work_done_date?->toDateString(),
                'worker_id' => $model->worker_id,
                'worker_name' => $model->worker?->name,
            ],
        ]);
    }

    private function resolveStatus(Contract $contract): array
    {
        if ($contract->relationLoaded('status') && $this->isCompletedStatus($contract->status)) {
            return ['code' => 'completed', 'label' => 'Выполнен'];
        }

        if ($contract->work_done_date && $contract->worker_id) {
            return ['code' => 'assigned', 'label' => 'Назначен'];
        }

        return ['code' => 'waiting', 'label' => 'Ожидание'];
    }

    private function isCompletedStatus(?ContractStatus $status): bool
    {
        if (!$status) {
            return false;
        }

        $code = strtoupper((string) $status->code);
        if (in_array($code, ['COMPLETED', 'DONE', 'FINISHED', 'DONE_WORK', 'DONE_MONTAGE'], true)) {
            return true;
        }

        $name = mb_strtolower((string) $status->name);
        return str_contains($name, 'выполн');
    }

    private function canSchedule(?User $user, Contract $contract): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isAdmin($user->id)) {
            return true;
        }

        $userId = (int) $user->id;
        if ((int) $contract->manager_id === $userId) {
            return true;
        }

        if ((int) $contract->worker_id === $userId) {
            return true;
        }

        return $this->hasRoleCode($userId, ['installer', 'worker', 'montaj']);
    }

    private function isAdmin(int $userId): bool
    {
        $db = DB::connection('legacy_new');
        $has = false;

        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $has = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $userId)
                ->where('roles.code', 'admin')
                ->exists();
        }

        return $has;
    }

    private function hasRoleCode(int $userId, array $codes): bool
    {
        if (empty($codes)) {
            return false;
        }

        $db = DB::connection('legacy_new');
        if (!Schema::connection('legacy_new')->hasTable('role_users') || !Schema::connection('legacy_new')->hasTable('roles')) {
            return false;
        }

        return $db->table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->where('role_users.user_id', $userId)
            ->whereIn('roles.code', $codes)
            ->exists();
    }
}
