<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    /**
     * @var array<int, array{table:string,column:string,ref:string,refColumn?:string}>
     */
    private array $fks = [
        // counterparties
        ['table' => 'counterparties', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'counterparties', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'counterparties', 'column' => 'updated_by', 'ref' => 'users'],
        // counterparty_companies
        ['table' => 'counterparty_companies', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'counterparty_companies', 'column' => 'counterparty_id', 'ref' => 'counterparties'],
        ['table' => 'counterparty_companies', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'counterparty_companies', 'column' => 'updated_by', 'ref' => 'users'],
        // counterparty_individuals
        ['table' => 'counterparty_individuals', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'counterparty_individuals', 'column' => 'counterparty_id', 'ref' => 'counterparties'],
        ['table' => 'counterparty_individuals', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'counterparty_individuals', 'column' => 'updated_by', 'ref' => 'users'],
        // spending_funds
        ['table' => 'spending_funds', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'spending_funds', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'spending_funds', 'column' => 'updated_by', 'ref' => 'users'],
        // spending_items
        ['table' => 'spending_items', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'spending_items', 'column' => 'fond_id', 'ref' => 'spending_funds'],
        ['table' => 'spending_items', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'spending_items', 'column' => 'updated_by', 'ref' => 'users'],
        // cash_boxes
        ['table' => 'cash_boxes', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'cash_boxes', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'cash_boxes', 'column' => 'updated_by', 'ref' => 'users'],
        // cash_box_company
        ['table' => 'cash_box_company', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'cash_box_company', 'column' => 'cash_box_id', 'ref' => 'cash_boxes'],
        // cash_transfers
        ['table' => 'cash_transfers', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'cash_transfers', 'column' => 'from_cash_box_id', 'ref' => 'cash_boxes'],
        ['table' => 'cash_transfers', 'column' => 'to_cash_box_id', 'ref' => 'cash_boxes'],
        ['table' => 'cash_transfers', 'column' => 'transaction_out_id', 'ref' => 'transactions'],
        ['table' => 'cash_transfers', 'column' => 'transaction_in_id', 'ref' => 'transactions'],
        ['table' => 'cash_transfers', 'column' => 'created_by', 'ref' => 'users'],
        // contracts
        ['table' => 'contracts', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'contracts', 'column' => 'counterparty_id', 'ref' => 'counterparties'],
        ['table' => 'contracts', 'column' => 'contract_status_id', 'ref' => 'contract_statuses'],
        ['table' => 'contracts', 'column' => 'sale_type_id', 'ref' => 'sale_types'],
        ['table' => 'contracts', 'column' => 'city_id', 'ref' => 'cities'],
        ['table' => 'contracts', 'column' => 'manager_id', 'ref' => 'users'],
        ['table' => 'contracts', 'column' => 'worker_id', 'ref' => 'users'],
        ['table' => 'contracts', 'column' => 'measurer_id', 'ref' => 'users'],
        ['table' => 'contracts', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'contracts', 'column' => 'updated_by', 'ref' => 'users'],
        // transactions
        ['table' => 'transactions', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'transactions', 'column' => 'cash_box_id', 'ref' => 'cash_boxes'],
        ['table' => 'transactions', 'column' => 'transaction_type_id', 'ref' => 'transaction_types'],
        ['table' => 'transactions', 'column' => 'payment_method_id', 'ref' => 'payment_methods'],
        ['table' => 'transactions', 'column' => 'contract_id', 'ref' => 'contracts'],
        ['table' => 'transactions', 'column' => 'counterparty_id', 'ref' => 'counterparties'],
        ['table' => 'transactions', 'column' => 'related_id', 'ref' => 'transactions'],
        ['table' => 'transactions', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'transactions', 'column' => 'updated_by', 'ref' => 'users'],
        // receipts
        ['table' => 'receipts', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'receipts', 'column' => 'cash_box_id', 'ref' => 'cash_boxes'],
        ['table' => 'receipts', 'column' => 'transaction_id', 'ref' => 'transactions'],
        ['table' => 'receipts', 'column' => 'contract_id', 'ref' => 'contracts'],
        ['table' => 'receipts', 'column' => 'counterparty_id', 'ref' => 'counterparties'],
        ['table' => 'receipts', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'receipts', 'column' => 'updated_by', 'ref' => 'users'],
        // spendings
        ['table' => 'spendings', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'spendings', 'column' => 'cash_box_id', 'ref' => 'cash_boxes'],
        ['table' => 'spendings', 'column' => 'transaction_id', 'ref' => 'transactions'],
        ['table' => 'spendings', 'column' => 'spending_item_id', 'ref' => 'spending_items'],
        ['table' => 'spendings', 'column' => 'fond_id', 'ref' => 'spending_funds'],
        ['table' => 'spendings', 'column' => 'contract_id', 'ref' => 'contracts'],
        ['table' => 'spendings', 'column' => 'counterparty_id', 'ref' => 'counterparties'],
        ['table' => 'spendings', 'column' => 'spent_to_user_id', 'ref' => 'users'],
        ['table' => 'spendings', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'spendings', 'column' => 'updated_by', 'ref' => 'users'],
        // users
        ['table' => 'users', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'users', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'users', 'column' => 'updated_by', 'ref' => 'users'],
        // products catalog
        ['table' => 'product_categories', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'product_categories', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'product_categories', 'column' => 'updated_by', 'ref' => 'users'],

        ['table' => 'product_subcategories', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'product_subcategories', 'column' => 'category_id', 'ref' => 'product_categories'],
        ['table' => 'product_subcategories', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'product_subcategories', 'column' => 'updated_by', 'ref' => 'users'],

        ['table' => 'product_brands', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'product_brands', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'product_brands', 'column' => 'updated_by', 'ref' => 'users'],

        ['table' => 'products', 'column' => 'company_id', 'ref' => 'companies'],
        ['table' => 'products', 'column' => 'category_id', 'ref' => 'product_categories'],
        ['table' => 'products', 'column' => 'sub_category_id', 'ref' => 'product_subcategories'],
        ['table' => 'products', 'column' => 'brand_id', 'ref' => 'product_brands'],
        ['table' => 'products', 'column' => 'created_by', 'ref' => 'users'],
        ['table' => 'products', 'column' => 'updated_by', 'ref' => 'users'],
    ];

    public function up(): void
    {
        // Normalize column types to unsigned bigints where needed before adding FKs.
        $this->normalizeColumns();

        foreach ($this->fks as $fk) {
            $table = $fk['table'];
            $column = $fk['column'];
            $ref = $fk['ref'];
            $refColumn = $fk['refColumn'] ?? 'id';

            if (!$this->hasColumn($table, $column) || !$this->hasColumn($ref, $refColumn)) {
                continue;
            }

            if ($this->foreignExists($table, $column)) {
                continue;
            }

            $nullable = $this->isNullable($table, $column);

            if ($nullable) {
                $this->sanitizeForeign($table, $column, $ref, $refColumn);
            }

            Schema::connection($this->connection)->table($table, function (Blueprint $table) use ($column, $ref, $refColumn, $nullable): void {
                $fk = $table->foreign($column, $this->fkName($table->getTable(), $column))
                    ->references($refColumn)
                    ->on($ref)
                    ->onUpdate('cascade');

                if ($nullable) {
                    $fk->nullOnDelete();
                } else {
                    $fk->restrictOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->fks as $fk) {
            $table = $fk['table'];
            $column = $fk['column'];

            if (!$this->hasColumn($table, $column)) {
                continue;
            }

            $fkName = $this->fkName($table, $column);

            Schema::connection($this->connection)->table($table, function (Blueprint $table) use ($fkName): void {
                if (Schema::connection($this->connection)->getConnection()->getSchemaBuilder()->hasTable($table->getTable())) {
                    try {
                        $table->dropForeign($fkName);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }
            });
        }
    }

    private function fkName(string $table, string $column): string
    {
        return "{$table}_{$column}_fk";
    }

    private function hasColumn(string $table, string $column): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.columns')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    private function isNullable(string $table, string $column): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        $nullable = DB::connection($this->connection)->table('information_schema.columns')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('is_nullable');

        return $nullable === 'YES';
    }

    private function foreignExists(string $table, string $column): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.KEY_COLUMN_USAGE')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();
    }

    private function sanitizeForeign(string $table, string $column, string $refTable, string $refColumn = 'id'): void
    {
        $db = DB::connection($this->connection);
        $db->statement("
            UPDATE `{$table}` t
            LEFT JOIN `{$refTable}` r ON r.`{$refColumn}` = t.`{$column}`
            SET t.`{$column}` = NULL
            WHERE t.`{$column}` IS NOT NULL AND r.`{$refColumn}` IS NULL
        ");
    }

    private function normalizeColumns(): void
    {
        $toBigInt = [
            ['product_subcategories', 'category_id'],
            ['products', 'category_id'],
            ['products', 'sub_category_id'],
            ['products', 'brand_id'],
        ];

        foreach ($toBigInt as [$table, $column]) {
            if ($this->hasColumn($table, $column)) {
                $this->alterToUnsignedBigintNullable($table, $column);
            }
        }
    }

    private function alterToUnsignedBigintNullable(string $table, string $column): void
    {
        DB::connection($this->connection)
            ->statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL");
    }
};
