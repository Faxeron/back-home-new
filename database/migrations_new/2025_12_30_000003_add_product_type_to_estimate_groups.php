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

        if ($schema->hasTable('estimate_groups')) {
            $schema->table('estimate_groups', function (Blueprint $table) use ($schema): void {
                if (!$schema->hasColumn('estimate_groups', 'product_type_id')) {
                    $table->unsignedBigInteger('product_type_id')->nullable()->after('company_id');
                }
                if (!$schema->hasColumn('estimate_groups', 'sort_order')) {
                    $table->integer('sort_order')->default(100)->after('product_type_id');
                }
            });

            if (!$this->indexExists('estimate_groups', 'estimate_groups_product_type_idx')) {
                DB::connection($this->connection)->statement(
                    'ALTER TABLE estimate_groups ADD INDEX estimate_groups_product_type_idx (product_type_id)'
                );
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank: no destructive changes on production data.
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
