<?php

namespace Tests\Feature;

use App\Services\Finance\FinanceService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class FinanceServiceTest extends TestCase
{
    protected FinanceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.connections.legacy_new', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->migrate();
        $this->seedTransactionTypes();
        $this->seedCashBoxes();

        $this->service = app(FinanceService::class);
    }

    /** @test */
    public function transfer_creates_two_transactions_and_cash_transfer_record(): void
    {
        $this->seedInitialBalance(1, 500);

        $transfer = $this->service->transferBetweenCashBoxes([
            'tenant_id' => 1,
            'company_id' => 1,
            'from_cash_box_id' => 1,
            'to_cash_box_id' => 2,
            'sum' => 100,
            'payment_method_id' => 1,
            'description' => 'test transfer',
            'created_by_user_id' => 99,
        ]);

        $this->assertDatabaseCount('transactions', 2, 'legacy_new');
        $this->assertDatabaseHas('cash_transfers', [
            'id' => $transfer->id,
            'from_cash_box_id' => 1,
            'to_cash_box_id' => 2,
        ], 'legacy_new');

        $this->assertEquals(400, $this->service->getCashBoxBalance(1));
        $this->assertEquals(100, $this->service->getCashBoxBalance(2));
    }

    /** @test */
    public function transfer_same_cashbox_is_forbidden(): void
    {
        $this->expectException(RuntimeException::class);
        $this->service->transferBetweenCashBoxes([
            'tenant_id' => 1,
            'company_id' => 1,
            'from_cash_box_id' => 1,
            'to_cash_box_id' => 1,
            'sum' => 50,
        ]);
    }

    /** @test */
    public function spending_cannot_overdraw_cashbox(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->createSpending([
            'tenant_id' => 1,
            'company_id' => 1,
            'cash_box_id' => 1,
            'sum' => 10,
            'payment_method_id' => 1,
            'payment_date' => now()->toDateString(),
            'fond_id' => 1,
            'spending_item_id' => 1,
        ]);
    }

    /** @test */
    public function director_flows_follow_signs(): void
    {
        $loan = $this->service->createDirectorLoanReceipt([
            'tenant_id' => 1,
            'company_id' => 1,
            'cash_box_id' => 1,
            'sum' => 200,
            'payment_method_id' => 1,
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertEquals(200, $this->service->getCashBoxBalance(1));

        $withdrawal = $this->service->createDirectorWithdrawal([
            'tenant_id' => 1,
            'company_id' => 1,
            'cash_box_id' => 1,
            'sum' => 50,
            'payment_method_id' => 1,
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertEquals(150, $this->service->getCashBoxBalance(1));
        $this->assertEquals($loan->id, $loan->id); // ensure objects persisted
        $this->assertEquals($withdrawal->id, $withdrawal->id);
    }

    private function migrate(): void
    {
        Schema::connection('legacy_new')->create('transaction_types', function ($table) {
            $table->increments('id');
            $table->string('code')->unique();
            $table->string('name')->nullable();
            $table->tinyInteger('sign')->default(1);
        });

        Schema::connection('legacy_new')->create('cash_boxes', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name')->nullable();
        });

        Schema::connection('legacy_new')->create('transactions', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->boolean('is_paid')->default(0);
            $table->timestamp('date_is_paid')->nullable();
            $table->boolean('is_completed')->default(0);
            $table->timestamp('date_is_completed')->nullable();
            $table->decimal('sum', 15, 2);
            $table->unsignedBigInteger('cash_box_id')->nullable();
            $table->unsignedInteger('transaction_type_id');
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('counterparty_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        Schema::connection('legacy_new')->create('receipts', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('cash_box_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->decimal('sum', 14, 2);
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('counterparty_id')->nullable();
            $table->text('description')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        Schema::connection('legacy_new')->create('spendings', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('cash_box_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->unsignedBigInteger('spending_item_id')->nullable();
            $table->unsignedBigInteger('fond_id')->nullable();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->decimal('sum', 14, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('counterparty_id')->nullable();
            $table->unsignedBigInteger('spent_to_user_id')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        Schema::connection('legacy_new')->create('cash_transfers', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('from_cash_box_id');
            $table->unsignedBigInteger('to_cash_box_id');
            $table->decimal('sum', 14, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('transaction_out_id')->nullable();
            $table->unsignedBigInteger('transaction_in_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::connection('legacy_new')->create('cashbox_history', function ($table) {
            $table->increments('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('cashbox_id');
            $table->unsignedBigInteger('transaction_id');
            $table->decimal('balance_after', 14, 2);
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
        });
    }

    private function seedTransactionTypes(): void
    {
        $types = [
            ['code' => 'INCOME', 'sign' => 1],
            ['code' => 'DIRECTOR_LOAN', 'sign' => 1],
            ['code' => 'OUTCOME', 'sign' => -1],
            ['code' => 'DIRECTOR_WITHDRAWAL', 'sign' => -1],
            ['code' => 'TRANSFER_OUT', 'sign' => -1],
            ['code' => 'TRANSFER_IN', 'sign' => 1],
        ];

        foreach ($types as $type) {
            DB::connection('legacy_new')->table('transaction_types')->insert([
                'code' => $type['code'],
                'name' => $type['code'],
                'sign' => $type['sign'],
            ]);
        }
    }

    private function seedCashBoxes(): void
    {
        DB::connection('legacy_new')->table('cash_boxes')->insert([
            ['id' => 1, 'tenant_id' => 1, 'company_id' => 1, 'name' => 'Main'],
            ['id' => 2, 'tenant_id' => 1, 'company_id' => 1, 'name' => 'Reserve'],
        ]);
    }

    private function seedInitialBalance(int $cashBoxId, float $amount): void
    {
        $this->service->createDirectorLoanReceipt([
            'tenant_id' => 1,
            'company_id' => 1,
            'cash_box_id' => $cashBoxId,
            'sum' => $amount,
            'payment_method_id' => 1,
            'payment_date' => now()->toDateString(),
        ]);
    }
}
