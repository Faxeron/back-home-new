<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $this->dropColumnsIfExists('products', [
            'price',
            'price_sale',
            'price_delivery',
            'montaj',
            'montaj_sebest',
        ]);
    }

    public function down(): void
    {
        // intentionally no-op (operational prices should not return to products)
    }

    private function dropColumnsIfExists(string $tableName, array $columns): void
    {
        $connection = $this->connection;

        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($connection, $tableName, $columns): void {
            foreach ($columns as $col) {
                if (Schema::connection($connection)->hasColumn($tableName, $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
