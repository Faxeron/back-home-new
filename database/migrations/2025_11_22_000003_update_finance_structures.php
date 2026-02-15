<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) cashboxes: drop balance
        Schema::connection('legacy_new')->table('cashboxes', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('cashboxes', 'balance')) {
                $table->dropColumn('balance');
            }
        });

        // 2-4) receipts: add payment_date (not null), add counterparty_id, backfill payment_date from transactions
        Schema::connection('legacy_new')->table('receipts', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('receipts', 'payment_date')) {
                $table->date('payment_date')->default('1970-01-01');
            }

            if (!Schema::connection('legacy_new')->hasColumn('receipts', 'counterparty_id')) {
                $table->unsignedBigInteger('counterparty_id')->nullable();
            }
        });

        $driver = DB::connection('legacy_new')->getDriverName();
        if ($driver === 'pgsql') {
            DB::connection('legacy_new')->statement(
                "UPDATE receipts r
                 SET payment_date = t.date_is_paid::date
                 FROM transactions t
                 WHERE t.id = r.transaction_id
                   AND t.date_is_paid IS NOT NULL"
            );
        } else {
            DB::connection('legacy_new')->statement(
                "UPDATE receipts r
                 JOIN transactions t ON t.id = r.transaction_id
                 SET r.payment_date = DATE(t.date_is_paid)
                 WHERE t.date_is_paid IS NOT NULL"
            );
        }

        // 5-6) spendings: add counterparty_id, spent_to_user_id
        Schema::connection('legacy_new')->table('spendings', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('spendings', 'counterparty_id')) {
                $table->unsignedBigInteger('counterparty_id')->nullable();
            }

            if (!Schema::connection('legacy_new')->hasColumn('spendings', 'spent_to_user_id')) {
                $table->unsignedBigInteger('spent_to_user_id')->nullable();
            }
        });

        // 7-9) transactions: add counterparty_id, contract_id, related_id
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('transactions', 'counterparty_id')) {
                $table->unsignedBigInteger('counterparty_id')->nullable();
            }

            if (!Schema::connection('legacy_new')->hasColumn('transactions', 'contract_id')) {
                $table->unsignedBigInteger('contract_id')->nullable();
            }

            if (!Schema::connection('legacy_new')->hasColumn('transactions', 'related_id')) {
                $table->unsignedBigInteger('related_id')->nullable();
            }
        });

        // 10) cash_transfers table
        if (!Schema::connection('legacy_new')->hasTable('cash_transfers')) {
            Schema::connection('legacy_new')->create('cash_transfers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1);
                $table->unsignedBigInteger('from_cashbox_id');
                $table->unsignedBigInteger('to_cashbox_id');
                $table->decimal('sum', 14, 2);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('transaction_out_id')->nullable();
                $table->unsignedBigInteger('transaction_in_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
            });
        }

        // 11) advances table
        if (!Schema::connection('legacy_new')->hasTable('advances')) {
            Schema::connection('legacy_new')->create('advances', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1);
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('cashbox_id');
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->decimal('amount', 14, 2)->default(0);
                $table->decimal('balance', 14, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // drop new tables
        Schema::connection('legacy_new')->dropIfExists('cash_transfers');
        Schema::connection('legacy_new')->dropIfExists('advances');

        // revert transactions additions
        Schema::connection('legacy_new')->table('transactions', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'counterparty_id')) {
                $table->dropColumn('counterparty_id');
            }
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'contract_id')) {
                $table->dropColumn('contract_id');
            }
            if (Schema::connection('legacy_new')->hasColumn('transactions', 'related_id')) {
                $table->dropColumn('related_id');
            }
        });

        // revert spendings additions
        Schema::connection('legacy_new')->table('spendings', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('spendings', 'counterparty_id')) {
                $table->dropColumn('counterparty_id');
            }
            if (Schema::connection('legacy_new')->hasColumn('spendings', 'spent_to_user_id')) {
                $table->dropColumn('spent_to_user_id');
            }
        });

        // revert receipts additions
        Schema::connection('legacy_new')->table('receipts', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('receipts', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
            if (Schema::connection('legacy_new')->hasColumn('receipts', 'counterparty_id')) {
                $table->dropColumn('counterparty_id');
            }
        });

        // add balance back to cashboxes
        Schema::connection('legacy_new')->table('cashboxes', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('cashboxes', 'balance')) {
                $table->decimal('balance', 14, 2)->default(0);
            }
        });
    }
};
