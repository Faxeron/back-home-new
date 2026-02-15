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
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if ($schema->hasColumn('products', 'delivery_price') && !$schema->hasColumn('products', 'price_delivery')) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $connection->statement('ALTER TABLE products CHANGE `delivery_price` `price_delivery` FLOAT NULL');
            } elseif ($driver === 'pgsql') {
                $connection->statement('ALTER TABLE products RENAME COLUMN delivery_price TO price_delivery');
            }
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if ($schema->hasColumn('products', 'price_delivery') && !$schema->hasColumn('products', 'delivery_price')) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $connection->statement('ALTER TABLE products CHANGE `price_delivery` `delivery_price` FLOAT NULL');
            } elseif ($driver === 'pgsql') {
                $connection->statement('ALTER TABLE products RENAME COLUMN price_delivery TO delivery_price');
            }
        }
    }
};
