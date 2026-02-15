<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('legacy_new');
        $schema = Schema::connection('legacy_new');

        if (!$schema->hasTable('transactions')) {
            return;
        }

        if ($connection->getDriverName() === 'sqlite') {
            return;
        }

        if ($connection->getDriverName() === 'pgsql') {
            $connection->statement('ALTER TABLE transactions ALTER COLUMN date_is_paid TYPE DATE USING date_is_paid::date');
            $connection->statement('ALTER TABLE transactions ALTER COLUMN date_is_completed TYPE DATE USING date_is_completed::date');
            return;
        }

        $connection->statement('ALTER TABLE transactions MODIFY date_is_paid DATE NULL');
        $connection->statement('ALTER TABLE transactions MODIFY date_is_completed DATE NULL');
    }

    public function down(): void
    {
        $connection = DB::connection('legacy_new');
        $schema = Schema::connection('legacy_new');

        if (!$schema->hasTable('transactions')) {
            return;
        }

        if ($connection->getDriverName() === 'sqlite') {
            return;
        }

        if ($connection->getDriverName() === 'pgsql') {
            $connection->statement('ALTER TABLE transactions ALTER COLUMN date_is_paid TYPE TIMESTAMP USING date_is_paid::timestamp');
            $connection->statement('ALTER TABLE transactions ALTER COLUMN date_is_completed TYPE TIMESTAMP USING date_is_completed::timestamp');
            return;
        }

        $connection->statement('ALTER TABLE transactions MODIFY date_is_paid TIMESTAMP NULL');
        $connection->statement('ALTER TABLE transactions MODIFY date_is_completed TIMESTAMP NULL');
    }
};
