<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

final class DbMigrateMysqlToPg extends Command
{
    protected $signature = 'db:migrate-mysql-to-pg
        {--source=legacy_new : Source connection (snapshot MySQL/MariaDB)}
        {--target=pgsql : Target PostgreSQL connection}
        {--chunk=1000 : Import chunk size}
        {--prepare-schema : Run migrate:fresh on target before data import}
        {--truncate-target : Truncate target tables before import}
        {--disable-constraints : Disable FK triggers during import}
        {--verify-only : Skip import and run verification only}
        {--companies=5 : Number of companies for multi-tenant verification}
        {--period-from= : Verification period start (Y-m-d)}
        {--period-to= : Verification period end (Y-m-d)}
        {--include= : Comma-separated table whitelist}
        {--exclude= : Comma-separated additional blacklist}
        {--allow-mismatch : Return success even when verification mismatches are found}
        {--report=storage/logs/mysql_to_pg_migration_report.json : Path to JSON report}
        {--log=storage/logs/mysql_to_pg_migration.log : Path to text log}';

    protected $description = 'Reproducible MySQL/MariaDB snapshot import into PostgreSQL with sequence reset and verification.';

    private const DEFAULT_EXCLUDED_TABLES = [
        'migrations',
        'cache',
        'cache_locks',
        'failed_jobs',
        'job_batches',
        'jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
    ];

    private string $logPath;

    private array $report = [];

    public function handle(): int
    {
        $startedAt = CarbonImmutable::now();
        $sourceConnection = (string) $this->option('source');
        $targetConnection = (string) $this->option('target');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $verifyOnly = (bool) $this->option('verify-only');
        $reportPath = (string) $this->option('report');
        $this->logPath = (string) $this->option('log');

        $this->report = [
            'started_at' => $startedAt->toIso8601String(),
            'source_connection' => $sourceConnection,
            'target_connection' => $targetConnection,
            'options' => [
                'chunk' => $chunkSize,
                'prepare_schema' => (bool) $this->option('prepare-schema'),
                'truncate_target' => (bool) $this->option('truncate-target'),
                'disable_constraints' => (bool) $this->option('disable-constraints'),
                'verify_only' => $verifyOnly,
                'companies' => max(1, (int) $this->option('companies')),
                'period_from' => $this->option('period-from'),
                'period_to' => $this->option('period-to'),
                'include' => $this->option('include'),
                'exclude' => $this->option('exclude'),
            ],
            'tables' => [
                'imported' => [],
                'order' => [],
            ],
            'copy' => [],
            'sequence_reset' => [],
            'verification' => [],
            'errors' => [],
        ];

        $this->clearLogFile($this->logPath);
        $this->logLine('db:migrate-mysql-to-pg started');

        try {
            $this->assertConnectionAvailable($sourceConnection);
            $this->assertConnectionAvailable($targetConnection);

            $sourceDriver = DB::connection($sourceConnection)->getDriverName();
            $targetDriver = DB::connection($targetConnection)->getDriverName();

            if (!in_array($sourceDriver, ['mysql', 'mariadb'], true)) {
                $this->logLine("Warning: source driver is '{$sourceDriver}', expected mysql/mariadb snapshot.");
            }

            if ($targetDriver !== 'pgsql') {
                throw new RuntimeException("Target connection '{$targetConnection}' must use pgsql driver, '{$targetDriver}' given.");
            }

            if ((bool) $this->option('prepare-schema') && !$verifyOnly) {
                $this->prepareTargetSchema($targetConnection);
            }

            $tables = $this->discoverTransferTables($sourceConnection, $targetConnection);
            $orderedTables = $this->sortTablesByForeignKeys($targetConnection, $tables);

            $this->report['tables']['imported'] = $tables;
            $this->report['tables']['order'] = $orderedTables;

            if (!$verifyOnly) {
                if ((bool) $this->option('truncate-target')) {
                    $this->truncateTargetTables($targetConnection, $orderedTables);
                }

                $constraintsWereDisabled = false;
                if ((bool) $this->option('disable-constraints')) {
                    $this->toggleTargetConstraints($targetConnection, true);
                    $constraintsWereDisabled = true;
                }

                try {
                    $this->report['copy'] = $this->copyTables(
                        $sourceConnection,
                        $targetConnection,
                        $orderedTables,
                        $chunkSize
                    );
                } finally {
                    if ($constraintsWereDisabled) {
                        $this->toggleTargetConstraints($targetConnection, false);
                    }
                }

                $this->report['sequence_reset'] = $this->resetPgSequences($targetConnection);
            }

            $this->report['verification'] = $this->runVerification(
                $sourceConnection,
                $targetConnection,
                max(1, (int) $this->option('companies')),
                $this->nullableStringOption('period-from'),
                $this->nullableStringOption('period-to'),
            );
        } catch (Throwable $exception) {
            $this->report['errors'][] = [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
            ];

            $this->logLine('Error: ' . $exception->getMessage());
            $this->writeReport($reportPath, CarbonImmutable::now());
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $finishedAt = CarbonImmutable::now();
        $this->writeReport($reportPath, $finishedAt);

        $hasMismatches = (bool) ($this->report['verification']['has_mismatches'] ?? false);
        if ($hasMismatches && !(bool) $this->option('allow-mismatch')) {
            $this->warn('Verification mismatches found. Review report and rerun with --allow-mismatch only if expected.');

            return self::FAILURE;
        }

        $this->info('Migration and verification completed.');

        return self::SUCCESS;
    }

    private function assertConnectionAvailable(string $connectionName): void
    {
        try {
            DB::connection($connectionName)->getPdo();
        } catch (Throwable $exception) {
            throw new RuntimeException("Connection '{$connectionName}' is not available: {$exception->getMessage()}", 0, $exception);
        }
    }

    private function prepareTargetSchema(string $targetConnection): void
    {
        $this->logLine("Running migrate:fresh on '{$targetConnection}'...");

        $exitCode = Artisan::call('migrate:fresh', [
            '--database' => $targetConnection,
            '--force' => true,
        ]);

        $this->logLine(trim(Artisan::output()));

        if ($exitCode !== 0) {
            throw new RuntimeException('php artisan migrate:fresh failed on target connection.');
        }
    }

    /**
     * @return array<int, string>
     */
    private function discoverTransferTables(string $sourceConnection, string $targetConnection): array
    {
        $sourceTables = $this->listTables($sourceConnection);
        $targetTables = $this->listTables($targetConnection);

        $common = array_values(array_intersect($sourceTables, $targetTables));

        $include = $this->csvOption('include');
        if ($include !== []) {
            $common = array_values(array_intersect($common, $include));
        }

        $exclude = array_values(array_unique(array_merge(
            self::DEFAULT_EXCLUDED_TABLES,
            $this->csvOption('exclude'),
        )));

        $filtered = array_values(array_diff($common, $exclude));
        sort($filtered);

        if ($filtered === []) {
            throw new RuntimeException('No common tables found for transfer after include/exclude filtering.');
        }

        $this->logLine('Discovered ' . count($filtered) . ' tables for transfer.');

        return $filtered;
    }

    /**
     * @return array<int, string>
     */
    private function listTables(string $connectionName): array
    {
        $connection = DB::connection($connectionName);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $databaseName = $connection->getDatabaseName();
            $rows = $connection->select(
                'SELECT table_name FROM information_schema.tables WHERE table_schema = ? AND table_type = ? ORDER BY table_name',
                [$databaseName, 'BASE TABLE']
            );

            return array_map(static fn (object $row): string => (string) $row->table_name, $rows);
        }

        if ($driver === 'pgsql') {
            $rows = $connection->select(
                "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name"
            );

            return array_map(static fn (object $row): string => (string) $row->table_name, $rows);
        }

        throw new RuntimeException("Unsupported driver '{$driver}' for table listing on connection '{$connectionName}'.");
    }

    /**
     * @param array<int, string> $tables
     * @return array<int, string>
     */
    private function sortTablesByForeignKeys(string $targetConnection, array $tables): array
    {
        $tableSet = array_fill_keys($tables, true);
        $childrenByParent = [];
        $incoming = [];

        foreach ($tables as $table) {
            $childrenByParent[$table] = [];
            $incoming[$table] = 0;
        }

        $rows = DB::connection($targetConnection)->select(
            "SELECT tc.table_name AS child_table, ccu.table_name AS parent_table
             FROM information_schema.table_constraints tc
             JOIN information_schema.key_column_usage kcu
               ON tc.constraint_name = kcu.constraint_name
              AND tc.table_schema = kcu.table_schema
             JOIN information_schema.constraint_column_usage ccu
               ON ccu.constraint_name = tc.constraint_name
              AND ccu.table_schema = tc.table_schema
             WHERE tc.constraint_type = 'FOREIGN KEY'
               AND tc.table_schema = 'public'"
        );

        foreach ($rows as $row) {
            $parent = (string) $row->parent_table;
            $child = (string) $row->child_table;

            if (!isset($tableSet[$parent], $tableSet[$child])) {
                continue;
            }

            if (!in_array($child, $childrenByParent[$parent], true)) {
                $childrenByParent[$parent][] = $child;
                $incoming[$child]++;
            }
        }

        $queue = [];
        foreach ($incoming as $table => $degree) {
            if ($degree === 0) {
                $queue[] = $table;
            }
        }

        sort($queue);
        $ordered = [];

        while ($queue !== []) {
            $current = array_shift($queue);
            $ordered[] = $current;

            foreach ($childrenByParent[$current] as $child) {
                $incoming[$child]--;
                if ($incoming[$child] === 0) {
                    $queue[] = $child;
                }
            }

            sort($queue);
        }

        if (count($ordered) < count($tables)) {
            $remaining = [];
            foreach ($incoming as $table => $degree) {
                if ($degree > 0) {
                    $remaining[] = $table;
                }
            }
            sort($remaining);

            $this->logLine(
                'Foreign-key cycles detected for tables: ' . implode(', ', $remaining) . '. Appending them at the end.'
            );

            $ordered = array_values(array_merge($ordered, $remaining));
        }

        return $ordered;
    }

    private function truncateTargetTables(string $targetConnection, array $orderedTables): void
    {
        if ($orderedTables === []) {
            return;
        }

        $tablesForTruncate = array_reverse($orderedTables);
        $quotedTables = array_map(fn (string $table): string => $this->quoteIdentifier($table), $tablesForTruncate);

        $sql = 'TRUNCATE TABLE ' . implode(', ', $quotedTables) . ' RESTART IDENTITY CASCADE';
        DB::connection($targetConnection)->statement($sql);

        $this->logLine('Target tables truncated with RESTART IDENTITY CASCADE.');
    }

    private function toggleTargetConstraints(string $targetConnection, bool $disable): void
    {
        $role = $disable ? 'replica' : 'origin';
        DB::connection($targetConnection)->statement("SET session_replication_role = '{$role}'");
        $this->logLine($disable ? 'Constraints disabled (session_replication_role=replica).' : 'Constraints re-enabled.');
    }

    /**
     * @param array<int, string> $tables
     * @return array<string, array{rows:int,columns:array<int,string>,chunked_by:string,seconds:float}>
     */
    private function copyTables(string $sourceConnection, string $targetConnection, array $tables, int $chunkSize): array
    {
        $source = DB::connection($sourceConnection);
        $target = DB::connection($targetConnection);

        $stats = [];
        $sourceSchema = Schema::connection($sourceConnection);
        $targetSchema = Schema::connection($targetConnection);

        foreach ($tables as $table) {
            $startedAt = microtime(true);

            $sourceColumns = $sourceSchema->getColumnListing($table);
            $targetColumns = $targetSchema->getColumnListing($table);
            $columns = array_values(array_intersect($sourceColumns, $targetColumns));

            if ($columns === []) {
                $this->logLine("Skipping '{$table}': no common columns.");
                continue;
            }

            $insertBatchSize = max(1, min($chunkSize, (int) floor(60000 / max(1, count($columns)))));
            $copiedRows = 0;

            if (in_array('id', $columns, true)) {
                $lastId = 0;

                while (true) {
                    $rows = $source->table($table)
                        ->select($columns)
                        ->where('id', '>', $lastId)
                        ->orderBy('id')
                        ->limit($chunkSize)
                        ->get();

                    if ($rows->isEmpty()) {
                        break;
                    }

                    $payload = [];
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $payload[] = $rowArray;
                        $lastId = max($lastId, (int) ($rowArray['id'] ?? 0));
                    }

                    foreach (array_chunk($payload, $insertBatchSize) as $batch) {
                        $target->table($table)->insert($batch);
                    }

                    $copiedRows += count($payload);
                }
            } else {
                $offset = 0;

                while (true) {
                    $rows = $source->table($table)
                        ->select($columns)
                        ->offset($offset)
                        ->limit($chunkSize)
                        ->get();

                    if ($rows->isEmpty()) {
                        break;
                    }

                    $payload = array_map(static fn ($row): array => (array) $row, $rows->all());
                    foreach (array_chunk($payload, $insertBatchSize) as $batch) {
                        $target->table($table)->insert($batch);
                    }

                    $copiedRows += count($payload);
                    $offset += $chunkSize;
                }
            }

            $seconds = round(microtime(true) - $startedAt, 3);
            $stats[$table] = [
                'rows' => $copiedRows,
                'columns' => $columns,
                'chunked_by' => in_array('id', $columns, true) ? 'id' : 'offset',
                'seconds' => $seconds,
            ];

            $this->logLine("Copied {$copiedRows} rows from '{$table}' in {$seconds}s.");
        }

        return $stats;
    }

    /**
     * @return array<string, array{column:string,max_id:int,next_value:int}>
     */
    private function resetPgSequences(string $targetConnection): array
    {
        $connection = DB::connection($targetConnection);
        $rows = $connection->select(
            "SELECT table_name, column_name
             FROM information_schema.columns
             WHERE table_schema = 'public'
               AND column_default LIKE 'nextval(%'"
        );

        $result = [];

        foreach ($rows as $row) {
            $table = (string) $row->table_name;
            $column = (string) $row->column_name;

            $sequenceRow = $connection->selectOne(
                'SELECT pg_get_serial_sequence(?, ?) as seq',
                ["public.{$table}", $column]
            );
            $sequenceName = (string) ($sequenceRow->seq ?? '');
            if ($sequenceName === '') {
                continue;
            }

            $maxId = (int) ($connection->table($table)->max($column) ?? 0);
            $nextValue = $maxId + 1;
            $connection->statement('SELECT setval(?::regclass, ?, false)', [$sequenceName, $nextValue]);

            $result[$table] = [
                'column' => $column,
                'max_id' => $maxId,
                'next_value' => $nextValue,
            ];
        }

        $this->logLine('PostgreSQL sequences were reset to max(id)+1.');

        return $result;
    }

    /**
     * @return array{
     *  key_table_counts:array<int,array<string,mixed>>,
     *  company_checks:array<int,array<string,mixed>>,
     *  mismatches:array<int,array<string,mixed>>,
     *  has_mismatches:bool
     * }
     */
    private function runVerification(
        string $sourceConnection,
        string $targetConnection,
        int $companiesLimit,
        ?string $periodFrom,
        ?string $periodTo,
    ): array {
        $source = DB::connection($sourceConnection);
        $target = DB::connection($targetConnection);

        $sourceSchema = Schema::connection($sourceConnection);
        $targetSchema = Schema::connection($targetConnection);

        $keyTables = [
            'tenants',
            'companies',
            'users',
            'counterparties',
            'contracts',
            'projects',
            'transactions',
            'receipts',
            'spendings',
        ];

        $tableCounts = [];
        $mismatches = [];

        foreach ($keyTables as $table) {
            if (!$sourceSchema->hasTable($table) || !$targetSchema->hasTable($table)) {
                $tableCounts[] = [
                    'table' => $table,
                    'source_count' => null,
                    'target_count' => null,
                    'status' => 'skipped_missing_table',
                ];
                continue;
            }

            $sourceCount = (int) $source->table($table)->count();
            $targetCount = (int) $target->table($table)->count();
            $isMatch = $sourceCount === $targetCount;

            $tableCounts[] = [
                'table' => $table,
                'source_count' => $sourceCount,
                'target_count' => $targetCount,
                'status' => $isMatch ? 'ok' : 'mismatch',
            ];

            if (!$isMatch) {
                $mismatches[] = [
                    'scope' => 'table_count',
                    'table' => $table,
                    'source_count' => $sourceCount,
                    'target_count' => $targetCount,
                ];
            }
        }

        $companyChecks = [];
        $companyIds = $this->detectCompaniesForVerification($sourceConnection, $companiesLimit);

        $dateFiltersEnabled = $periodFrom !== null && $periodTo !== null;

        foreach ($companyIds as $companyId) {
            $row = [
                'company_id' => $companyId,
                'period_from' => $periodFrom,
                'period_to' => $periodTo,
                'metrics' => [],
            ];

            if ($sourceSchema->hasTable('transactions') && $targetSchema->hasTable('transactions')) {
                $sourceTxQuery = $source->table('transactions')->where('company_id', $companyId);
                $targetTxQuery = $target->table('transactions')->where('company_id', $companyId);

                if ($dateFiltersEnabled && $sourceSchema->hasColumn('transactions', 'date_is_paid') && $targetSchema->hasColumn('transactions', 'date_is_paid')) {
                    $sourceTxQuery->whereBetween('date_is_paid', [$periodFrom, $periodTo]);
                    $targetTxQuery->whereBetween('date_is_paid', [$periodFrom, $periodTo]);
                }

                $sourceTxCount = (int) (clone $sourceTxQuery)->count();
                $targetTxCount = (int) (clone $targetTxQuery)->count();
                $sourceTxSum = (float) ((clone $sourceTxQuery)->sum('sum') ?? 0);
                $targetTxSum = (float) ((clone $targetTxQuery)->sum('sum') ?? 0);

                $sumDiff = round(abs($sourceTxSum - $targetTxSum), 2);
                $countMatch = $sourceTxCount === $targetTxCount;
                $sumMatch = $sumDiff <= 0.01;

                $row['metrics']['transactions'] = [
                    'source_count' => $sourceTxCount,
                    'target_count' => $targetTxCount,
                    'source_sum' => round($sourceTxSum, 2),
                    'target_sum' => round($targetTxSum, 2),
                    'sum_diff' => $sumDiff,
                    'status' => $countMatch && $sumMatch ? 'ok' : 'mismatch',
                ];

                if (!$countMatch || !$sumMatch) {
                    $mismatches[] = [
                        'scope' => 'company_transactions',
                        'company_id' => $companyId,
                        'source_count' => $sourceTxCount,
                        'target_count' => $targetTxCount,
                        'source_sum' => round($sourceTxSum, 2),
                        'target_sum' => round($targetTxSum, 2),
                        'sum_diff' => $sumDiff,
                    ];
                }
            }

            foreach (['receipts', 'spendings'] as $table) {
                if (!$sourceSchema->hasTable($table) || !$targetSchema->hasTable($table)) {
                    continue;
                }

                $sourceCount = (int) $source->table($table)->where('company_id', $companyId)->count();
                $targetCount = (int) $target->table($table)->where('company_id', $companyId)->count();
                $match = $sourceCount === $targetCount;

                $row['metrics'][$table] = [
                    'source_count' => $sourceCount,
                    'target_count' => $targetCount,
                    'status' => $match ? 'ok' : 'mismatch',
                ];

                if (!$match) {
                    $mismatches[] = [
                        'scope' => 'company_table_count',
                        'company_id' => $companyId,
                        'table' => $table,
                        'source_count' => $sourceCount,
                        'target_count' => $targetCount,
                    ];
                }
            }

            $companyChecks[] = $row;
        }

        $verification = [
            'key_table_counts' => $tableCounts,
            'company_checks' => $companyChecks,
            'mismatches' => $mismatches,
            'has_mismatches' => $mismatches !== [],
        ];

        $this->logLine(
            $verification['has_mismatches']
                ? 'Verification completed with mismatches.'
                : 'Verification completed successfully, no mismatches.'
        );

        return $verification;
    }

    /**
     * @return array<int, int>
     */
    private function detectCompaniesForVerification(string $sourceConnection, int $limit): array
    {
        $schema = Schema::connection($sourceConnection);
        if (!$schema->hasTable('companies')) {
            return [];
        }

        $source = DB::connection($sourceConnection);

        if ($schema->hasTable('transactions')) {
            $rows = $source->table('companies as c')
                ->leftJoin('transactions as t', 't.company_id', '=', 'c.id')
                ->groupBy('c.id')
                ->orderByRaw('COUNT(t.id) DESC')
                ->orderBy('c.id')
                ->limit($limit)
                ->pluck('c.id')
                ->all();

            $companyIds = array_map(static fn ($id): int => (int) $id, $rows);
            if ($companyIds !== []) {
                return $companyIds;
            }
        }

        $rows = $source->table('companies')
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id')
            ->all();

        return array_map(static fn ($id): int => (int) $id, $rows);
    }

    private function writeReport(string $reportPath, CarbonImmutable $finishedAt): void
    {
        $this->report['finished_at'] = $finishedAt->toIso8601String();
        $this->report['duration_seconds'] = CarbonImmutable::parse($this->report['started_at'])->diffInSeconds($finishedAt);
        $this->report['log_path'] = $this->logPath;

        $absoluteReportPath = $this->makeAbsolutePath($reportPath);
        $directory = dirname($absoluteReportPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $absoluteReportPath,
            json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );

        $this->logLine("Report written to {$absoluteReportPath}");
    }

    private function nullableStringOption(string $option): ?string
    {
        $value = $this->option($option);
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<int, string>
     */
    private function csvOption(string $option): array
    {
        $value = $this->option($option);
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $items = array_map(
            static fn (string $item): string => trim($item),
            explode(',', $value)
        );

        $items = array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
        $items = array_values(array_unique($items));
        sort($items);

        return $items;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private function makeAbsolutePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            throw new RuntimeException('Path cannot be empty.');
        }

        if (preg_match('/^[A-Za-z]:\\\\/', $trimmed) === 1 || str_starts_with($trimmed, '/') || str_starts_with($trimmed, '\\\\')) {
            return $trimmed;
        }

        return base_path($trimmed);
    }

    private function clearLogFile(string $path): void
    {
        $absolutePath = $this->makeAbsolutePath($path);
        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($absolutePath, '');
        $this->logPath = $absolutePath;
    }

    private function logLine(string $message): void
    {
        $line = '[' . now()->format('Y-m-d H:i:s') . '] ' . $message;
        $this->line($line);
        file_put_contents($this->logPath, $line . PHP_EOL, FILE_APPEND);
    }
}

