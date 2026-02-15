<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (!Schema::connection($this->connection)->hasTable('contract_groups')) {
            return;
        }

        $table = 'contract_groups';

        if (
            Schema::connection($this->connection)->hasColumn($table, 'installation_date')
            && !Schema::connection($this->connection)->hasColumn($table, 'work_date_actual')
        ) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $connection->statement("ALTER TABLE {$table} CHANGE installation_date work_date_actual DATE NULL");
            } else {
                $connection->statement("ALTER TABLE {$table} RENAME COLUMN installation_date TO work_date_actual");
            }
        }

        if (!Schema::connection($this->connection)->hasColumn($table, 'worker_id')) {
            Schema::connection($this->connection)->table($table, function (Blueprint $table): void {
                $table->unsignedBigInteger('worker_id')->nullable()->after('work_date_actual');
                $table->index(['worker_id'], 'contract_groups_worker_idx');
            });
        }
    }

    public function down(): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        if (!Schema::connection($this->connection)->hasTable('contract_groups')) {
            return;
        }

        $table = 'contract_groups';

        if (Schema::connection($this->connection)->hasColumn($table, 'worker_id')) {
            Schema::connection($this->connection)->table($table, function (Blueprint $table): void {
                $table->dropIndex('contract_groups_worker_idx');
                $table->dropColumn('worker_id');
            });
        }

        if (
            Schema::connection($this->connection)->hasColumn($table, 'work_date_actual')
            && !Schema::connection($this->connection)->hasColumn($table, 'installation_date')
        ) {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $connection->statement("ALTER TABLE {$table} CHANGE work_date_actual installation_date DATE NULL");
            } else {
                $connection->statement("ALTER TABLE {$table} RENAME COLUMN work_date_actual TO installation_date");
            }
        }
    }
};
