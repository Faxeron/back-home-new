<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $skipTables = ['migrations'];

    public function up(): void
    {
        foreach ($this->tableNames() as $tableName) {
            if (in_array($tableName, $this->skipTables, true)) {
                continue;
            }

            $this->updateTable($tableName);
        }
    }

    public function down(): void
    {
        foreach ($this->tableNames() as $tableName) {
            if (in_array($tableName, $this->skipTables, true)) {
                continue;
            }

            $this->rollbackTable($tableName);
        }
    }

    /**
     * @return array<int, string>
     */
    private function tableNames(): array
    {
        $connection = DB::connection('legacy_new');
        $database = $connection->getDatabaseName();

        return collect($connection->select('SHOW TABLES'))
            ->map(static function (object $row): string {
                return reset($row); // first column contains table name
            })
            ->filter()
            ->values()
            ->all();
    }

    private function updateTable(string $tableName): void
    {
        $connection = Schema::connection('legacy_new');

        $connection->table($tableName, function (Blueprint $table) use ($connection, $tableName): void {
            if (!$connection->hasColumn($tableName, 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->default(1);
            }

            if ($connection->hasColumn($tableName, 'created_by_user_id') && !$connection->hasColumn($tableName, 'created_by')) {
                $table->renameColumn('created_by_user_id', 'created_by');
            } elseif (!$connection->hasColumn($tableName, 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable();
            }

            if ($connection->hasColumn($tableName, 'updated_by_user_id') && !$connection->hasColumn($tableName, 'updated_by')) {
                $table->renameColumn('updated_by_user_id', 'updated_by');
            } elseif (!$connection->hasColumn($tableName, 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable();
            }

            if (!$connection->hasColumn($tableName, 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (!$connection->hasColumn($tableName, 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

            if ($connection->hasColumn($tableName, 'summ') && !$connection->hasColumn($tableName, 'sum')) {
                $table->renameColumn('summ', 'sum');
            }
        });
    }

    private function rollbackTable(string $tableName): void
    {
        $connection = Schema::connection('legacy_new');

        $connection->table($tableName, function (Blueprint $table) use ($connection, $tableName): void {
            if ($connection->hasColumn($tableName, 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }

            if ($connection->hasColumn($tableName, 'created_by') && !$connection->hasColumn($tableName, 'created_by_user_id')) {
                $table->renameColumn('created_by', 'created_by_user_id');
            } elseif ($connection->hasColumn($tableName, 'created_by')) {
                $table->dropColumn('created_by');
            }

            if ($connection->hasColumn($tableName, 'updated_by') && !$connection->hasColumn($tableName, 'updated_by_user_id')) {
                $table->renameColumn('updated_by', 'updated_by_user_id');
            } elseif ($connection->hasColumn($tableName, 'updated_by')) {
                $table->dropColumn('updated_by');
            }

            if ($connection->hasColumn($tableName, 'created_at')) {
                $table->dropColumn('created_at');
            }

            if ($connection->hasColumn($tableName, 'updated_at')) {
                $table->dropColumn('updated_at');
            }

            if ($connection->hasColumn($tableName, 'sum') && !$connection->hasColumn($tableName, 'summ')) {
                $table->renameColumn('sum', 'summ');
            }
        });
    }
};
