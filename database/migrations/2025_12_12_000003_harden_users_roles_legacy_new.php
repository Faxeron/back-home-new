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

        if ($db->getDriverName() === 'pgsql') {
            return;
        }

        $database = $db->getDatabaseName();

        // Drop tenant_id on tenants if meta migration added it.
        if ($schema->hasColumn('tenants', 'tenant_id')) {
            $db->statement('ALTER TABLE `tenants` DROP COLUMN `tenant_id`');
        }

        // Cleanup orphaned pivots/profiles before adding FKs.
        $db->statement('DELETE FROM role_users WHERE user_id NOT IN (SELECT id FROM users) OR role_id NOT IN (SELECT id FROM roles)');
        $db->statement('DELETE FROM user_company WHERE user_id NOT IN (SELECT id FROM users) OR company_id NOT IN (SELECT id FROM companies)');
        $db->statement('DELETE FROM user_profiles WHERE user_id NOT IN (SELECT id FROM users)');

        // Rename company_id -> default_company_id to make pivot canonical.
        if ($schema->hasColumn('users', 'company_id') && !$schema->hasColumn('users', 'default_company_id')) {
            $db->statement('ALTER TABLE `users` CHANGE `company_id` `default_company_id` BIGINT UNSIGNED NULL');
        }

        // Ensure tenant_id exists and is NOT NULL DEFAULT 1 on key tables.
        foreach (['users', 'user_profiles', 'roles', 'role_users', 'user_company', 'companies'] as $table) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            if (!$schema->hasColumn($table, 'tenant_id')) {
                $schema->table($table, function (Blueprint $table): void {
                    $table->unsignedBigInteger('tenant_id')->default(1)->after('id');
                });
            }

            $db->table($table)->whereNull('tenant_id')->update(['tenant_id' => 1]);
            $db->statement("ALTER TABLE `{$table}` MODIFY `tenant_id` BIGINT UNSIGNED NOT NULL DEFAULT 1");
        }

        // Add FKs if missing.
        $this->addForeignIfMissing($database, 'users', 'tenant_id', 'tenants');
        $this->addForeignIfMissing($database, 'users', 'default_company_id', 'companies', nullable: true);

        $this->addForeignIfMissing($database, 'user_profiles', 'user_id', 'users', onDelete: 'cascade');
        $this->addForeignIfMissing($database, 'user_profiles', 'tenant_id', 'tenants');
        $this->addForeignIfMissing($database, 'user_profiles', 'company_id', 'companies', nullable: true);

        $this->addForeignIfMissing($database, 'roles', 'tenant_id', 'tenants');

        $this->addForeignIfMissing($database, 'role_users', 'role_id', 'roles', onDelete: 'cascade');
        $this->addForeignIfMissing($database, 'role_users', 'user_id', 'users', onDelete: 'cascade');
        $this->addForeignIfMissing($database, 'role_users', 'tenant_id', 'tenants');

        $this->addForeignIfMissing($database, 'user_company', 'user_id', 'users', onDelete: 'cascade');
        $this->addForeignIfMissing($database, 'user_company', 'company_id', 'companies', onDelete: 'cascade');
        $this->addForeignIfMissing($database, 'user_company', 'tenant_id', 'tenants');

        $this->addForeignIfMissing($database, 'companies', 'tenant_id', 'tenants');
    }

    public function down(): void
    {
        // No down migration to avoid data loss; intent is to keep unified user/tenant model.
    }

    private function addForeignIfMissing(
        string $database,
        string $table,
        string $column,
        string $refTable,
        string $refColumn = 'id',
        bool $nullable = false,
        bool $unique = false,
        ?string $onDelete = null
    ): void {
        $schema = Schema::connection($this->connection);
        $db = DB::connection($this->connection);

        if (!$schema->hasTable($table) || !$schema->hasColumn($table, $column) || !$schema->hasTable($refTable)) {
            return;
        }

        $exists = $db->table('information_schema.KEY_COLUMN_USAGE')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();

        if ($exists) {
            return;
        }

        $constraint = "{$table}_{$column}_fk";

        $schema->table($table, function (Blueprint $table) use ($column, $refTable, $refColumn, $nullable, $unique, $constraint, $onDelete): void {
            if ($unique) {
                $table->unique($column, "{$column}_unique");
            }

            $fk = $table->foreign($column, $constraint)->references($refColumn)->on($refTable)->onUpdate('cascade');
            if ($onDelete === 'cascade') {
                $fk->cascadeOnDelete();
            } elseif ($nullable) {
                $fk->nullOnDelete();
            } else {
                $fk->restrictOnDelete();
            }
        });
    }
};
