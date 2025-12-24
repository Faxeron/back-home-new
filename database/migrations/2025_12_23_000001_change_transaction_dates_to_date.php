<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection('legacy_new');

        if ($connection->getDriverName() === 'sqlite') {
            return;
        }

        $connection->statement('ALTER TABLE transactions MODIFY date_is_paid DATE NULL');
        $connection->statement('ALTER TABLE transactions MODIFY date_is_completed DATE NULL');
    }

    public function down(): void
    {
        $connection = DB::connection('legacy_new');

        if ($connection->getDriverName() === 'sqlite') {
            return;
        }

        $connection->statement('ALTER TABLE transactions MODIFY date_is_paid TIMESTAMP NULL');
        $connection->statement('ALTER TABLE transactions MODIFY date_is_completed TIMESTAMP NULL');
    }
};
