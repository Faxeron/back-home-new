<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $this->dropForeignIfExists();

        Schema::connection($this->connection)->table('receipts', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('receipts', 'transaction_id')) {
                $table->foreign('transaction_id', 'receipts_transaction_id_fk')
                    ->references('id')
                    ->on('transactions')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('receipts', function (Blueprint $table): void {
            $table->dropForeign('receipts_transaction_id_fk');
        });
    }

    private function dropForeignIfExists(): void
    {
        try {
            DB::connection($this->connection)->statement('ALTER TABLE `receipts` DROP FOREIGN KEY `receipts_transaction_id_fk`');
        } catch (\Throwable $e) {
            // ignore if not exists
        }
    }
};
