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
            $db->statement('ALTER TABLE `users` ADD CONSTRAINT `users_default_company_id_fk` FOREIGN KEY (`default_company_id`) REFERENCES `companies` (`id`) ON DELETE SET NULL ON UPDATE CASCADE');
        }

        if ($schema->hasTable('user_company')) {
            $db->statement(
                "INSERT IGNORE INTO `user_company` (`user_id`, `company_id`, `created_at`, `updated_at`)
                 SELECT `id`, `company_id`, NOW(), NOW()
                 FROM `users`
                 WHERE `company_id` IS NOT NULL"
            );

            $db->statement(
                "INSERT IGNORE INTO `user_company` (`user_id`, `company_id`, `created_at`, `updated_at`)
                 SELECT `id`, `default_company_id`, NOW(), NOW()
                 FROM `users`
                 WHERE `default_company_id` IS NOT NULL"
            );
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

        return $db->table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->exists();
    }
};
