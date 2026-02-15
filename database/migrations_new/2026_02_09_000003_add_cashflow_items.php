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

        if (!$schema->hasTable('cashflow_items')) {
            $schema->create('cashflow_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('code', 80);
                $table->string('name', 255);
                $table->enum('section', ['OPERATING', 'INVESTING', 'FINANCING']);
                $table->enum('direction', ['IN', 'OUT']);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(100);
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->unique('code', 'cashflow_items_code_unique');
                $table->index('parent_id', 'cashflow_items_parent_id_idx');
                $table->index(['tenant_id', 'company_id'], 'cashflow_items_tenant_company_idx');
            });

            DB::connection($this->connection)->statement(
                'ALTER TABLE cashflow_items ADD CONSTRAINT cashflow_items_parent_fk FOREIGN KEY (parent_id) REFERENCES cashflow_items(id) ON DELETE SET NULL'
            );
        }

        if ($schema->hasTable('spending_items') && !$schema->hasColumn('spending_items', 'cashflow_item_id')) {
            $schema->table('spending_items', function (Blueprint $table): void {
                $table->unsignedBigInteger('cashflow_item_id')->nullable()->after('fond_id');
            });
            $this->addIndexIfMissing('spending_items', 'spending_items_cashflow_item_id_fk', ['cashflow_item_id']);
            DB::connection($this->connection)->statement(
                'ALTER TABLE spending_items ADD CONSTRAINT spending_items_cashflow_item_id_fk FOREIGN KEY (cashflow_item_id) REFERENCES cashflow_items(id) ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }

        if ($schema->hasTable('transactions') && !$schema->hasColumn('transactions', 'cashflow_item_id')) {
            $schema->table('transactions', function (Blueprint $table): void {
                $table->unsignedBigInteger('cashflow_item_id')->nullable()->after('payment_method_id');
            });
            $this->addIndexIfMissing('transactions', 'transactions_cashflow_item_id_fk', ['cashflow_item_id']);
            DB::connection($this->connection)->statement(
                'ALTER TABLE transactions ADD CONSTRAINT transactions_cashflow_item_id_fk FOREIGN KEY (cashflow_item_id) REFERENCES cashflow_items(id) ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }

        $this->addIndexIfMissing('transactions', 'transactions_paid_date_idx', ['tenant_id', 'company_id', 'is_paid', 'date_is_paid']);
        $this->addIndexIfMissing('transactions', 'transactions_cashbox_paid_date_idx', ['cashbox_id', 'is_paid', 'date_is_paid']);
        $this->addIndexIfMissing('transactions', 'transactions_cashflow_item_idx', ['cashflow_item_id']);

        $this->seedDefaults();
    }

    public function down(): void
    {
        // forward-only migration
    }

    private function seedDefaults(): void
    {
        $db = DB::connection($this->connection);
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('cashflow_items')) {
            return;
        }

        $exists = (int) $db->table('cashflow_items')->count();
        if ($exists > 0) {
            return;
        }

        $now = now();

        $rows = [
            ['code' => 'OP_IN_CLIENT_PAYMENT', 'name' => 'Оплата клиентов', 'section' => 'OPERATING', 'direction' => 'IN', 'sort_order' => 10],
            ['code' => 'OP_IN_CLIENT_ADVANCE', 'name' => 'Авансы клиентов', 'section' => 'OPERATING', 'direction' => 'IN', 'sort_order' => 20],
            ['code' => 'OP_IN_SERVICE', 'name' => 'Сервис/доп.услуги', 'section' => 'OPERATING', 'direction' => 'IN', 'sort_order' => 30],
            ['code' => 'OP_IN_REFUND_VENDOR', 'name' => 'Возвраты от поставщиков', 'section' => 'OPERATING', 'direction' => 'IN', 'sort_order' => 40],
            ['code' => 'OP_IN_OTHER', 'name' => 'Прочие поступления', 'section' => 'OPERATING', 'direction' => 'IN', 'sort_order' => 50],

            ['code' => 'OP_OUT_VENDOR_PURCHASE', 'name' => 'Закупка у поставщиков', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 110],
            ['code' => 'OP_OUT_SUBCONTRACTORS', 'name' => 'Подрядчики/бригады', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 120],
            ['code' => 'OP_OUT_SALARY', 'name' => 'Зарплата/премии', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 130],
            ['code' => 'OP_OUT_TAXES', 'name' => 'Налоги/взносы', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 140],
            ['code' => 'OP_OUT_RENT', 'name' => 'Аренда', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 150],
            ['code' => 'OP_OUT_UTILS', 'name' => 'Связь/интернет/хостинг', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 160],
            ['code' => 'OP_OUT_ADS_YANDEX', 'name' => 'Реклама Яндекс', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 170],
            ['code' => 'OP_OUT_ADS_AVITO', 'name' => 'Реклама Авито', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 180],
            ['code' => 'OP_OUT_ADS_OTHER', 'name' => 'Прочий маркетинг', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 190],
            ['code' => 'OP_OUT_FUEL', 'name' => 'Топливо', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 200],
            ['code' => 'OP_OUT_LOGISTICS', 'name' => 'Доставка/логистика', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 210],
            ['code' => 'OP_OUT_REPAIR_MAINT', 'name' => 'Ремонт/обслуживание', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 220],
            ['code' => 'OP_OUT_BANK_FEES', 'name' => 'Комиссии/эквайринг', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 230],
            ['code' => 'OP_OUT_OFFICE', 'name' => 'Хоз/канц/прочее', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 240],
            ['code' => 'OP_OUT_REFUND_CLIENT', 'name' => 'Возвраты клиентам', 'section' => 'OPERATING', 'direction' => 'OUT', 'sort_order' => 250],

            ['code' => 'INV_OUT_EQUIPMENT', 'name' => 'Покупка техники/оборудования', 'section' => 'INVESTING', 'direction' => 'OUT', 'sort_order' => 310],
            ['code' => 'INV_OUT_IMPROVEMENTS', 'name' => 'Капвложения/стройка/улучшения', 'section' => 'INVESTING', 'direction' => 'OUT', 'sort_order' => 320],
            ['code' => 'INV_OUT_IT', 'name' => 'Капитализируемое IT/ПО', 'section' => 'INVESTING', 'direction' => 'OUT', 'sort_order' => 330],
            ['code' => 'INV_IN_ASSET_SALE', 'name' => 'Продажа активов', 'section' => 'INVESTING', 'direction' => 'IN', 'sort_order' => 340],

            ['code' => 'FIN_IN_LOAN_BANK', 'name' => 'Получение кредита/займа', 'section' => 'FINANCING', 'direction' => 'IN', 'sort_order' => 410],
            ['code' => 'FIN_IN_LOAN_OWNER', 'name' => 'Займ директора/взнос', 'section' => 'FINANCING', 'direction' => 'IN', 'sort_order' => 420],
            ['code' => 'FIN_OUT_LOAN_REPAYMENT', 'name' => 'Погашение тела займа', 'section' => 'FINANCING', 'direction' => 'OUT', 'sort_order' => 430],
            ['code' => 'FIN_OUT_LOAN_INTEREST', 'name' => 'Проценты', 'section' => 'FINANCING', 'direction' => 'OUT', 'sort_order' => 440],
            ['code' => 'FIN_OUT_DIVIDENDS', 'name' => 'Вывод прибыли/дивиденды', 'section' => 'FINANCING', 'direction' => 'OUT', 'sort_order' => 450],
        ];

        $payload = array_map(function (array $row) use ($now) {
            return array_merge($row, [
                'tenant_id' => 1,
                'company_id' => null,
                'parent_id' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $rows);

        $db->table('cashflow_items')->insert($payload);
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $cols = implode(', ', $columns);
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE INDEX {$indexName} ON {$table} ({$cols})");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();

        if ($driver === 'pgsql') {
            return $connection->table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->where('indexname', $indexName)
                ->exists();
        }

        return $connection->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};
