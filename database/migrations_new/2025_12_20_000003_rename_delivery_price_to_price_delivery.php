<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasColumn('products', 'delivery_price') && !$schema->hasColumn('products', 'price_delivery')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE products CHANGE `delivery_price` `price_delivery` FLOAT NULL'
            );
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasColumn('products', 'price_delivery') && !$schema->hasColumn('products', 'delivery_price')) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE products CHANGE `price_delivery` `delivery_price` FLOAT NULL'
            );
        }
    }
};
