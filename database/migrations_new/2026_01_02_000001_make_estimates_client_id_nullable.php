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

        if (!$schema->hasTable('estimates') || !$schema->hasColumn('estimates', 'client_id')) {
            return;
        }

        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement('ALTER TABLE estimates MODIFY client_id INT(11) NULL');

            return;
        }

        if ($driver === 'pgsql') {
            $connection->statement('ALTER TABLE estimates ALTER COLUMN client_id DROP NOT NULL');
        }
    }

    public function down(): void
    {
        // Intentionally left blank: avoid destructive changes on production data.
    }
};
