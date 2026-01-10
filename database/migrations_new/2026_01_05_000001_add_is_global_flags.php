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
        $tables = [
            'products',
            'product_categories',
            'product_subcategories',
            'product_brands',
            'product_kinds',
            'payment_methods',
            'spending_funds',
            'spending_items',
            'transaction_types',
        ];

        foreach ($tables as $table) {
            if ($schema->hasTable($table) && !$schema->hasColumn($table, 'is_global')) {
                $schema->table($table, function (Blueprint $table): void {
                    $table->boolean('is_global')->default(false);
                });
            }

            if ($schema->hasTable($table)
                && $schema->hasColumn($table, 'is_global')
                && $schema->hasColumn($table, 'company_id')) {
                DB::connection($this->connection)
                    ->table($table)
                    ->whereNull('company_id')
                    ->update(['is_global' => 1]);
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $tables = [
            'products',
            'product_categories',
            'product_subcategories',
            'product_brands',
            'product_kinds',
            'payment_methods',
            'spending_funds',
            'spending_items',
            'transaction_types',
        ];

        foreach ($tables as $table) {
            if ($schema->hasTable($table) && $schema->hasColumn($table, 'is_global')) {
                $schema->table($table, function (Blueprint $table): void {
                    $table->dropColumn('is_global');
                });
            }
        }
    }
};
