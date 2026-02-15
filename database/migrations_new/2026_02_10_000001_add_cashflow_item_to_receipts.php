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

        if ($schema->hasTable('receipts') && !$schema->hasColumn('receipts', 'cashflow_item_id')) {
            $schema->table('receipts', function (Blueprint $table): void {
                $table->unsignedBigInteger('cashflow_item_id')->nullable()->after('transaction_id');
            });

            $connection = DB::connection($this->connection);
            $driver = $connection->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $connection->statement('ALTER TABLE receipts ADD KEY receipts_cashflow_item_id_fk (cashflow_item_id)');
            } else {
                $connection->statement('CREATE INDEX receipts_cashflow_item_id_fk ON receipts (cashflow_item_id)');
            }
            DB::connection($this->connection)->statement(
                'ALTER TABLE receipts ADD CONSTRAINT receipts_cashflow_item_id_fk FOREIGN KEY (cashflow_item_id) REFERENCES cashflow_items(id) ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }
    }

    public function down(): void
    {
        // forward-only migration
    }
};
