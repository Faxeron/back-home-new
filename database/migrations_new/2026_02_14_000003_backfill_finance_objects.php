<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    /** @var array<string, int> */
    private array $legacyObjectCache = [];

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (
            !$schema->hasTable('finance_objects')
            || !$schema->hasTable('contracts')
            || !$schema->hasTable('transactions')
            || !$schema->hasTable('receipts')
            || !$schema->hasTable('spendings')
        ) {
            return;
        }

        $this->syncContractsToFinanceObjects();
        $this->backfillByContract();
        $this->propagateFromTransactions();
        $this->assignLegacyImportsToTransactions();
        $this->propagateFromTransactions();
        $this->assignLegacyImportsToTable('receipts');
        $this->assignLegacyImportsToTable('spendings');
    }

    public function down(): void
    {
        // forward-only migration
    }

    private function syncContractsToFinanceObjects(): void
    {
        $db = DB::connection($this->connection);
        $statusById = $this->loadContractStatusCodes();

        $db->table('contracts')
            ->whereNull('finance_object_id')
            ->orderBy('id')
            ->chunkById(200, function ($contracts) use ($db, $statusById): void {
                foreach ($contracts as $contract) {
                    $existingObjectId = $db->table('finance_objects')
                        ->where('legal_contract_id', $contract->id)
                        ->value('id');

                    if ($existingObjectId) {
                        $db->table('contracts')
                            ->where('id', $contract->id)
                            ->update(['finance_object_id' => $existingObjectId]);
                        continue;
                    }

                    $dateFrom = $this->normalizeDate($contract->contract_date ?? $contract->created_at ?? now()->toDateString());
                    $dateTo = $this->normalizeDate($contract->work_end_date ?? null);
                    if ($dateTo && $dateTo < $dateFrom) {
                        $dateTo = $dateFrom;
                    }

                    $name = trim((string) ($contract->title ?? ''));
                    if ($name === '') {
                        $name = sprintf('Contract #%d', (int) $contract->id);
                    }

                    $objectId = $db->table('finance_objects')->insertGetId([
                        'tenant_id' => $contract->tenant_id,
                        'company_id' => $contract->company_id,
                        'type' => 'CONTRACT',
                        'name' => mb_substr($name, 0, 255),
                        'code' => sprintf('CTR-%d', (int) $contract->id),
                        'status' => $this->mapContractStatusToObjectStatus(
                            isset($contract->contract_status_id) ? (int) $contract->contract_status_id : null,
                            $statusById,
                        ),
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                        'counterparty_id' => $contract->counterparty_id,
                        'legal_contract_id' => $contract->id,
                        'description' => $contract->address,
                        'created_by' => $contract->created_by,
                        'updated_by' => $contract->updated_by,
                        'created_at' => $contract->created_at ?? now(),
                        'updated_at' => $contract->updated_at ?? now(),
                    ]);

                    $db->table('contracts')
                        ->where('id', $contract->id)
                        ->update(['finance_object_id' => $objectId]);
                }
            });
    }

    private function backfillByContract(): void
    {
        $driver = DB::connection($this->connection)->getDriverName();

        if ($driver === 'mysql') {
            DB::connection($this->connection)->statement(
                'UPDATE transactions t
                 INNER JOIN contracts c ON c.id = t.contract_id
                 SET t.finance_object_id = c.finance_object_id
                 WHERE t.finance_object_id IS NULL AND c.finance_object_id IS NOT NULL'
            );

            DB::connection($this->connection)->statement(
                'UPDATE receipts r
                 INNER JOIN contracts c ON c.id = r.contract_id
                 SET r.finance_object_id = c.finance_object_id
                 WHERE r.finance_object_id IS NULL AND c.finance_object_id IS NOT NULL'
            );

            DB::connection($this->connection)->statement(
                'UPDATE spendings s
                 INNER JOIN contracts c ON c.id = s.contract_id
                 SET s.finance_object_id = c.finance_object_id
                 WHERE s.finance_object_id IS NULL AND c.finance_object_id IS NOT NULL'
            );

            return;
        }

        $this->backfillByContractPortable('transactions');
        $this->backfillByContractPortable('receipts');
        $this->backfillByContractPortable('spendings');
    }

    private function backfillByContractPortable(string $table): void
    {
        $db = DB::connection($this->connection);

        $db->table($table)
            ->whereNull('finance_object_id')
            ->whereNotNull('contract_id')
            ->orderBy('id')
            ->chunkById(300, function ($rows) use ($db, $table): void {
                $contractIds = [];
                foreach ($rows as $row) {
                    if ($row->contract_id) {
                        $contractIds[] = (int) $row->contract_id;
                    }
                }

                if ($contractIds === []) {
                    return;
                }

                $map = $db->table('contracts')
                    ->whereIn('id', array_values(array_unique($contractIds)))
                    ->whereNotNull('finance_object_id')
                    ->pluck('finance_object_id', 'id');

                foreach ($rows as $row) {
                    if (!$row->contract_id) {
                        continue;
                    }
                    $objectId = $map->get((int) $row->contract_id);
                    if (!$objectId) {
                        continue;
                    }

                    $db->table($table)
                        ->where('id', $row->id)
                        ->update(['finance_object_id' => (int) $objectId]);
                }
            });
    }

    private function propagateFromTransactions(): void
    {
        $driver = DB::connection($this->connection)->getDriverName();

        if ($driver === 'mysql') {
            DB::connection($this->connection)->statement(
                'UPDATE receipts r
                 INNER JOIN transactions t ON t.id = r.transaction_id
                 SET r.finance_object_id = t.finance_object_id
                 WHERE r.finance_object_id IS NULL AND t.finance_object_id IS NOT NULL'
            );

            DB::connection($this->connection)->statement(
                'UPDATE spendings s
                 INNER JOIN transactions t ON t.id = s.transaction_id
                 SET s.finance_object_id = t.finance_object_id
                 WHERE s.finance_object_id IS NULL AND t.finance_object_id IS NOT NULL'
            );

            return;
        }

        $this->propagateFromTransactionsPortable('receipts');
        $this->propagateFromTransactionsPortable('spendings');
    }

    private function propagateFromTransactionsPortable(string $table): void
    {
        $db = DB::connection($this->connection);

        $db->table($table)
            ->whereNull('finance_object_id')
            ->whereNotNull('transaction_id')
            ->orderBy('id')
            ->chunkById(300, function ($rows) use ($db, $table): void {
                $txIds = [];
                foreach ($rows as $row) {
                    if ($row->transaction_id) {
                        $txIds[] = (int) $row->transaction_id;
                    }
                }

                if ($txIds === []) {
                    return;
                }

                $map = $db->table('transactions')
                    ->whereIn('id', array_values(array_unique($txIds)))
                    ->whereNotNull('finance_object_id')
                    ->pluck('finance_object_id', 'id');

                foreach ($rows as $row) {
                    if (!$row->transaction_id) {
                        continue;
                    }
                    $objectId = $map->get((int) $row->transaction_id);
                    if (!$objectId) {
                        continue;
                    }

                    $db->table($table)
                        ->where('id', $row->id)
                        ->update(['finance_object_id' => (int) $objectId]);
                }
            });
    }

    private function assignLegacyImportsToTransactions(): void
    {
        $db = DB::connection($this->connection);

        $pairs = $db->table('transactions')
            ->select(['tenant_id', 'company_id'])
            ->whereNull('finance_object_id')
            ->groupBy('tenant_id', 'company_id')
            ->get();

        foreach ($pairs as $pair) {
            $objectId = $this->resolveLegacyObjectId($pair->tenant_id, $pair->company_id);
            $query = $db->table('transactions')->whereNull('finance_object_id');
            $this->applyContext($query, $pair->tenant_id, $pair->company_id);
            $query->update(['finance_object_id' => $objectId]);
        }
    }

    private function assignLegacyImportsToTable(string $table): void
    {
        $db = DB::connection($this->connection);

        $pairs = $db->table($table)
            ->select(['tenant_id', 'company_id'])
            ->whereNull('finance_object_id')
            ->groupBy('tenant_id', 'company_id')
            ->get();

        foreach ($pairs as $pair) {
            $objectId = $this->resolveLegacyObjectId($pair->tenant_id, $pair->company_id);
            $query = $db->table($table)->whereNull('finance_object_id');
            $this->applyContext($query, $pair->tenant_id, $pair->company_id);
            $query->update(['finance_object_id' => $objectId]);
        }
    }

    private function resolveLegacyObjectId($tenantId, $companyId): int
    {
        $key = sprintf('%s|%s', $tenantId ?? 'null', $companyId ?? 'null');
        if (isset($this->legacyObjectCache[$key])) {
            return $this->legacyObjectCache[$key];
        }

        $db = DB::connection($this->connection);

        $existing = $db->table('finance_objects')
            ->where('type', 'LEGACY_IMPORT')
            ->where('code', 'LEGACY_IMPORT')
            ->where(function (Builder $query) use ($tenantId): void {
                if ($tenantId === null) {
                    $query->whereNull('tenant_id');
                    return;
                }
                $query->where('tenant_id', $tenantId);
            })
            ->where(function (Builder $query) use ($companyId): void {
                if ($companyId === null) {
                    $query->whereNull('company_id');
                    return;
                }
                $query->where('company_id', $companyId);
            })
            ->value('id');

        if ($existing) {
            $this->legacyObjectCache[$key] = (int) $existing;
            return (int) $existing;
        }

        $objectId = $db->table('finance_objects')->insertGetId([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'type' => 'LEGACY_IMPORT',
            'name' => 'Legacy Import',
            'code' => 'LEGACY_IMPORT',
            'status' => 'ARCHIVED',
            'date_from' => now()->toDateString(),
            'date_to' => null,
            'counterparty_id' => null,
            'legal_contract_id' => null,
            'description' => 'System object for historical transactions imported from legacy data.',
            'created_by' => null,
            'updated_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->legacyObjectCache[$key] = (int) $objectId;

        return (int) $objectId;
    }

    private function applyContext(Builder $query, $tenantId, $companyId): void
    {
        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }
    }

    /**
     * @return array<int, string>
     */
    private function loadContractStatusCodes(): array
    {
        if (!Schema::connection($this->connection)->hasTable('contract_statuses')) {
            return [];
        }

        return DB::connection($this->connection)->table('contract_statuses')
            ->pluck('code', 'id')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();
    }

    /**
     * @param array<int, string> $statusById
     */
    private function mapContractStatusToObjectStatus(?int $contractStatusId, array $statusById): string
    {
        $code = strtoupper((string) ($statusById[$contractStatusId ?? 0] ?? ''));

        if (str_contains($code, 'CANCEL')) {
            return 'CANCELED';
        }

        if (in_array($code, ['DONE', 'COMPLETED', 'CLOSED', 'FINISHED'], true)) {
            return 'DONE';
        }

        if (in_array($code, ['ARCHIVED'], true)) {
            return 'ARCHIVED';
        }

        if (in_array($code, ['DRAFT', 'NEW'], true)) {
            return 'DRAFT';
        }

        if (in_array($code, ['ON_HOLD', 'HOLD', 'PAUSED'], true)) {
            return 'ON_HOLD';
        }

        return 'ACTIVE';
    }

    private function normalizeDate($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return substr($value, 0, 10);
        }

        return now()->toDateString();
    }
};

