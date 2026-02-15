<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('estimates') || !$schema->hasColumn('estimates', 'random_id')) {
            return;
        }

        $db = DB::connection($this->connection);
        $counts = $db->table('estimates')
            ->select('random_id', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('random_id')
            ->where('random_id', '<>', '')
            ->groupBy('random_id')
            ->get();

        $dupCounts = [];
        $used = [];
        foreach ($counts as $row) {
            $dupCounts[$row->random_id] = (int) $row->cnt;
            $used[$row->random_id] = true;
        }

        $seen = [];

        $db->table('estimates')
            ->select('id', 'random_id')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($db, &$dupCounts, &$seen, &$used): void {
                foreach ($rows as $row) {
                    $randomId = $row->random_id;
                    $needsNew = !$randomId || $randomId === '';

                    if (!$needsNew && (($dupCounts[$randomId] ?? 0) > 1)) {
                        $seen[$randomId] = ($seen[$randomId] ?? 0) + 1;
                        if ($seen[$randomId] > 1) {
                            $needsNew = true;
                        }
                    }

                    if (!$needsNew) {
                        continue;
                    }

                    do {
                        $newId = Str::random(12);
                    } while (isset($used[$newId]));

                    $used[$newId] = true;

                    $db->table('estimates')
                        ->where('id', $row->id)
                        ->update(['random_id' => $newId]);
                }
            });

        $driver = $db->getDriverName();
        $database = $db->getDatabaseName();
        $indexExists = $driver === 'pgsql'
            ? $db->table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', 'estimates')
                ->where('indexname', 'estimates_random_id_unique')
                ->exists()
            : $db->table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', $database)
                ->where('TABLE_NAME', 'estimates')
                ->where('INDEX_NAME', 'estimates_random_id_unique')
                ->exists();

        if (!$indexExists) {
            $schema->table('estimates', function (Blueprint $table): void {
                $table->unique('random_id', 'estimates_random_id_unique');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left blank: no destructive changes on production data.
    }
};
