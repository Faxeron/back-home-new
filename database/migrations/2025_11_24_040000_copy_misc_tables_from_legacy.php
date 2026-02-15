<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $tableMap = [
        'estimates' => 'estimates',
        'group_smetas' => 'estimate_groups',
        'pattern_materials' => 'estimate_template_materials',
        'pattern_septiks' => 'estimate_template_septiks',
        'warehouse_products' => 'products',
        'product_brands' => 'product_brands',
        'product_subcategories' => 'product_subcategories',
        'product_categories' => 'product_categories',
    ];

    /**
     * @var array<int, string>
     */
    private array $extraColumns = [
        'tenant_id',
        'company_id',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    public function up(): void
    {
        if (DB::connection($this->connection)->getDriverName() === 'pgsql') {
            return;
        }

        $source = DB::connection('legacy');
        $dest = DB::connection('legacy_new');

        $sourceDb = $source->getDatabaseName();

        $dest->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->tableMap as $sourceTable => $destTable) {
            // recreate destination table structure
            $dest->statement("DROP TABLE IF EXISTS `{$destTable}`");
            $dest->statement("CREATE TABLE `{$destTable}` LIKE `{$sourceDb}`.`{$sourceTable}`");

            // add extra columns if missing
            Schema::connection('legacy_new')->table($destTable, function ($table) use ($destTable): void {
                if (!Schema::connection('legacy_new')->hasColumn($destTable, 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                }
                if (!Schema::connection('legacy_new')->hasColumn($destTable, 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('tenant_id');
                }
                if (!Schema::connection('legacy_new')->hasColumn($destTable, 'created_at')) {
                    $table->timestamp('created_at')->nullable();
                }
                if (!Schema::connection('legacy_new')->hasColumn($destTable, 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable();
                }
                if (!Schema::connection('legacy_new')->hasColumn($destTable, 'updated_at')) {
                    $table->timestamp('updated_at')->nullable();
                }
                if (!Schema::connection('legacy_new')->hasColumn($destTable, 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable();
                }
            });

            // collect source columns
            $sourceColumns = $source->table('information_schema.columns')
                ->where('table_schema', $sourceDb)
                ->where('table_name', $sourceTable)
                ->orderBy('ordinal_position')
                ->pluck('column_name')
                ->toArray();

            // build insert columns list for destination
            $insertColumns = $sourceColumns;
            foreach ($this->extraColumns as $col) {
                if (!in_array($col, $insertColumns, true)) {
                    $insertColumns[] = $col;
                }
            }

            // build SELECT part with literals for missing columns
            $selectParts = [];
            foreach ($insertColumns as $col) {
                if (in_array($col, $sourceColumns, true)) {
                    $selectParts[] = "`{$col}`";
                } else {
                    switch ($col) {
                        case 'tenant_id':
                        case 'company_id':
                            $selectParts[] = '1';
                            break;
                        case 'created_at':
                        case 'updated_at':
                            $selectParts[] = 'NOW()';
                            break;
                        case 'created_by':
                        case 'updated_by':
                            $selectParts[] = 'NULL';
                            break;
                        default:
                            $selectParts[] = 'NULL';
                    }
                }
            }

            $destColumnsSql = implode('`, `', $insertColumns);
            $selectSql = implode(', ', $selectParts);

            $dest->statement("INSERT INTO `{$destTable}` (`{$destColumnsSql}`) SELECT {$selectSql} FROM `{$sourceDb}`.`{$sourceTable}`");
        }

        $dest->statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(): void
    {
        $dest = DB::connection('legacy_new');
        $dest->statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($this->tableMap as $destTable) {
            $dest->statement("DROP TABLE IF EXISTS `{$destTable}`");
        }
        $dest->statement('SET FOREIGN_KEY_CHECKS=1');
    }
};

