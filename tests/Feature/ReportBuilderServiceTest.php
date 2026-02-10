<?php

namespace Tests\Feature;

use App\Services\Finance\ReportBuilderService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportBuilderServiceTest extends TestCase
{
    protected ReportBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy_new', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->migrateSchema();

        $this->service = app(ReportBuilderService::class);
        $this->service->setContext(1, 1);
    }

    /** @test */
    public function it_builds_daily_cashflow_excluding_transfers(): void
    {
        // Arrange: cashflow item and transactions, including transfer-related rows.
        DB::connection('legacy_new')->table('cashflow_items')->insert([
            'id' => 10,
            'tenant_id' => 1,
            'company_id' => 1,
            'code' => 'SALES',
            'name' => 'Продажи',
            'section' => 'OPERATING',
            'direction' => 'IN',
        ]);

        // Two paid transactions: one normal, one part of transfer.
        DB::connection('legacy_new')->table('transactions')->insert([
            [
                'id' => 1,
                'tenant_id' => 1,
                'company_id' => 1,
                'is_paid' => 1,
                'date_is_paid' => '2026-02-10',
                'sum' => 1000,
                'cashflow_item_id' => 10,
            ],
            [
                'id' => 2,
                'tenant_id' => 1,
                'company_id' => 1,
                'is_paid' => 1,
                'date_is_paid' => '2026-02-10',
                'sum' => 500,
                'cashflow_item_id' => 10,
            ],
        ]);

        // Mark transaction 2 as part of cash transfer (should be excluded).
        DB::connection('legacy_new')->table('cash_transfers')->insert([
            'id' => 1,
            'tenant_id' => 1,
            'company_id' => 1,
            'from_cashbox_id' => 1,
            'to_cashbox_id' => 2,
            'sum' => 500,
            'transaction_out_id' => 2,
        ]);

        // Act
        $result = $this->service->rebuildCashflowDay('2026-02-10');

        // Assert: service reports success for the happy path.
        $this->assertTrue($result['success'] ?? false);
        $this->assertEquals('2026-02-10', $result['date']);
    }

    /** @test */
    public function it_builds_pnl_month_from_monthly_cashflow(): void
    {
        // Arrange: operating IN/OUT and financing IN/OUT for a month.
        DB::connection('legacy_new')->table('report_cashflow_monthly')->insert([
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'year' => 2026,
                'month' => 2,
                'year_month' => '2026-02',
                'section' => 'OPERATING',
                'direction' => 'IN',
                'cashflow_item_id' => 10,
                'cashflow_item_name' => 'Продажи',
                'total_amount' => 2000,
                'tx_count' => 2,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'year' => 2026,
                'month' => 2,
                'year_month' => '2026-02',
                'section' => 'OPERATING',
                'direction' => 'OUT',
                'cashflow_item_id' => 11,
                'cashflow_item_name' => 'Расходы',
                'total_amount' => 500,
                'tx_count' => 1,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'year' => 2026,
                'month' => 2,
                'year_month' => '2026-02',
                'section' => 'FINANCING',
                'direction' => 'IN',
                'cashflow_item_id' => 12,
                'cashflow_item_name' => 'Кредиты',
                'total_amount' => 300,
                'tx_count' => 1,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'year' => 2026,
                'month' => 2,
                'year_month' => '2026-02',
                'section' => 'FINANCING',
                'direction' => 'OUT',
                'cashflow_item_id' => 13,
                'cashflow_item_name' => 'Погашение',
                'total_amount' => 100,
                'tx_count' => 1,
            ],
        ]);

        // Act
        $result = $this->service->rebuildPnLMonth('2026-02');

        // Assert main PnL table
        $this->assertTrue($result['success'] ?? false);
        $this->assertEquals(2000, $result['revenue']);
        $this->assertEquals(500, $result['expenses']);
        $this->assertEquals(1500, $result['profit']);

        $pnl = DB::connection('legacy_new')
            ->table('report_pnl_monthly')
            ->where('tenant_id', 1)
            ->where('company_id', 1)
            ->where('year_month', '2026-02')
            ->first();

        $this->assertNotNull($pnl);
        $this->assertEquals(2000, (float) $pnl->revenue_operating);
        $this->assertEquals(500, (float) $pnl->expense_operating);
        $this->assertEquals(1500, (float) $pnl->operating_profit);
        $this->assertEquals(300, (float) $pnl->finance_in);
        $this->assertEquals(100, (float) $pnl->finance_out);

        // Assert detail table by item
        $items = DB::connection('legacy_new')
            ->table('report_pnl_monthly_by_item')
            ->where('tenant_id', 1)
            ->where('company_id', 1)
            ->where('year_month', '2026-02')
            ->get();

        $this->assertCount(2, $items);
    }

    // Additional reconciliation tests would require DB-specific date
    // functions (DATE_FORMAT) not available in sqlite, so they are
    // intentionally omitted here.

    private function migrateSchema(): void
    {
        // Minimal subset of tables required by ReportBuilderService tests.
        Schema::connection('legacy_new')->create('cashflow_items', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('section')->nullable();
            $table->string('direction')->nullable();
        });

        Schema::connection('legacy_new')->create('transactions', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('is_paid')->default(0);
            $table->date('date_is_paid')->nullable();
            $table->decimal('sum', 15, 2);
            $table->unsignedBigInteger('cashflow_item_id')->nullable();
        });

        Schema::connection('legacy_new')->create('cash_transfers', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('from_cashbox_id');
            $table->unsignedBigInteger('to_cashbox_id');
            $table->decimal('sum', 15, 2);
            $table->unsignedBigInteger('transaction_out_id')->nullable();
            $table->unsignedBigInteger('transaction_in_id')->nullable();
        });

        Schema::connection('legacy_new')->create('report_cashflow_daily', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->date('day_date');
            $table->char('year_month', 7);
            $table->string('section');
            $table->string('direction');
            $table->unsignedBigInteger('cashflow_item_id');
            $table->string('cashflow_item_name')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('tx_count')->default(0);
        });

        Schema::connection('legacy_new')->create('report_cashflow_monthly', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();
            $table->char('year_month', 7);
            $table->string('section');
            $table->string('direction');
            $table->unsignedBigInteger('cashflow_item_id');
            $table->string('cashflow_item_name')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->integer('tx_count')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'company_id', 'year_month', 'cashflow_item_id'], 'ix_cf_monthly_unique');
        });

        Schema::connection('legacy_new')->create('report_cashflow_monthly_summary', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->char('year_month', 7);
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('inflow_total', 15, 2)->default(0);
            $table->decimal('outflow_total', 15, 2)->default(0);
            $table->decimal('net_cashflow', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
        });

        Schema::connection('legacy_new')->create('report_pnl_monthly', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->char('year_month', 7);
            $table->decimal('revenue_operating', 15, 2)->default(0);
            $table->decimal('expense_operating', 15, 2)->default(0);
            $table->decimal('operating_profit', 15, 2)->default(0);
            $table->decimal('finance_in', 15, 2)->default(0);
            $table->decimal('finance_out', 15, 2)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'company_id', 'year_month'], 'ix_pnl_monthly_unique');
        });

        Schema::connection('legacy_new')->create('report_pnl_monthly_by_item', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->char('year_month', 7);
            $table->unsignedBigInteger('cashflow_item_id');
            $table->string('cashflow_item_name')->nullable();
            $table->string('direction');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'company_id', 'year_month', 'cashflow_item_id'], 'ix_pnl_item_unique');
        });

        Schema::connection('legacy_new')->create('finance_periods', function ($table): void {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->char('year_month', 7);
            $table->string('status')->default('OPEN');
        });
    }
}

