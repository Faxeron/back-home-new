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
        if (!Schema::connection($this->connection)->hasTable('receipts')) {
            return;
        }

        $this->dropForeignIfExists();

        Schema::connection($this->connection)->table('receipts', function (Blueprint $table): void {
            if (
                Schema::connection($this->connection)->hasColumn('receipts', 'transaction_id')
                && Schema::connection($this->connection)->hasTable('transactions')
            ) {
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
        if (!Schema::connection($this->connection)->hasTable('receipts')) {
            return;
        }

        Schema::connection($this->connection)->table('receipts', function (Blueprint $table): void {
            if (
                Schema::connection($this->connection)->hasColumn('receipts', 'transaction_id')
                && $this->foreignKeyExists('receipts', 'receipts_transaction_id_fk')
            ) {
                $table->dropForeign('receipts_transaction_id_fk');
            }
        });
    }

    private function dropForeignIfExists(): void
    {
        if (!$this->foreignKeyExists('receipts', 'receipts_transaction_id_fk')) {
            return;
        }

        Schema::connection($this->connection)->table('receipts', function (Blueprint $table): void {
            $table->dropForeign('receipts_transaction_id_fk');
        });
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        return DB::connection($this->connection)->table('information_schema.table_constraints')
            ->where('table_schema', 'public')
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
