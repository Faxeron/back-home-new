<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $db = DB::connection($this->connection);

        if (!$schema->hasColumn('users', 'default_company_id')) {
            $schema->table('users', function (Blueprint $table): void {
                $table->unsignedBigInteger('default_company_id')->nullable()->after('company_id');
            });
        }

        $defaultCompanyId = $db->table('companies')->orderBy('id')->value('id');

        if ($defaultCompanyId) {
            $db->table('users')->whereNull('company_id')->update(['company_id' => $defaultCompanyId]);
            $db->table('users')->whereNull('default_company_id')->update(['default_company_id' => DB::raw('company_id')]);
            $db->table('users')->whereNull('default_company_id')->update(['default_company_id' => $defaultCompanyId]);
        }

        if ($schema->hasColumn('users', 'default_company_id')
            && !$this->foreignExists('users', 'users_default_company_id_fk')) {
            $schema->table('users', function (Blueprint $table): void {
                $table->foreign('default_company_id', 'users_default_company_id_fk')
                    ->references('id')
                    ->on('companies')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        if ($schema->hasTable('user_company')) {
            $now = now();
            $pairs = $db->table('users')
                ->select(['id as user_id', 'company_id', 'default_company_id'])
                ->get();

            $payload = [];
            foreach ($pairs as $row) {
                if ($row->company_id !== null) {
                    $payload[] = [
                        'user_id' => (int) $row->user_id,
                        'company_id' => (int) $row->company_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($row->default_company_id !== null) {
                    $payload[] = [
                        'user_id' => (int) $row->user_id,
                        'company_id' => (int) $row->default_company_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            $payload = collect($payload)
                ->unique(fn (array $row): string => $row['user_id'] . ':' . $row['company_id'])
                ->values()
                ->all();

            if ($payload !== []) {
                $db->table('user_company')->insertOrIgnore($payload);
            }
        }
    }

    public function down(): void
    {
        // No rollback: data updates are intentional.
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        $db = DB::connection($this->connection);
        $database = $db->getDatabaseName();
        $driver = $db->getDriverName();
        $schema = $driver === 'pgsql' ? 'public' : $database;

        return $db->table('information_schema.table_constraints')
            ->where('constraint_schema', $schema)
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
