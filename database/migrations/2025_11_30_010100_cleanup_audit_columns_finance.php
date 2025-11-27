<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropColumnsIfExists('transaction_types', ['created_at', 'updated_at', 'created_by', 'updated_by']);
        $this->dropColumnsIfExists('payment_methods', ['created_at', 'updated_at', 'created_by', 'updated_by']);
        $this->dropColumnsIfExists('contract_statuses', ['created_at', 'updated_at', 'created_by', 'updated_by']);
    }

    public function down(): void
    {
        // intentionally no-op (audit поля не возвращаем)
    }

    private function dropColumnsIfExists(string $tableName, array $columns): void
    {
        Schema::connection('legacy_new')->table($tableName, function (Blueprint $table) use ($tableName, $columns): void {
            foreach ($columns as $col) {
                if (Schema::connection('legacy_new')->hasColumn($tableName, $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
