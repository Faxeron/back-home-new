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

        if ($schema->hasTable('transactions') && !$schema->hasColumn('transactions', 'finance_object_id')) {
            $schema->table('transactions', function (Blueprint $table): void {
                $table->unsignedBigInteger('finance_object_id')->nullable()->after('contract_id');
            });
            $this->addForeignKey(
                'transactions',
                'transactions_finance_object_fk',
                'finance_object_id',
                'finance_objects',
                'id'
            );
            $this->addIndex('transactions', 'transactions_finance_object_idx', ['finance_object_id']);
        }

        if ($schema->hasTable('receipts') && !$schema->hasColumn('receipts', 'finance_object_id')) {
            $schema->table('receipts', function (Blueprint $table): void {
                $table->unsignedBigInteger('finance_object_id')->nullable()->after('contract_id');
            });
            $this->addForeignKey(
                'receipts',
                'receipts_finance_object_fk',
                'finance_object_id',
                'finance_objects',
                'id'
            );
            $this->addIndex('receipts', 'receipts_finance_object_idx', ['finance_object_id']);
        }

        if ($schema->hasTable('spendings') && !$schema->hasColumn('spendings', 'finance_object_id')) {
            $schema->table('spendings', function (Blueprint $table): void {
                $table->unsignedBigInteger('finance_object_id')->nullable()->after('contract_id');
            });
            $this->addForeignKey(
                'spendings',
                'spendings_finance_object_fk',
                'finance_object_id',
                'finance_objects',
                'id'
            );
            $this->addIndex('spendings', 'spendings_finance_object_idx', ['finance_object_id']);
        }

        if ($schema->hasTable('contracts') && !$schema->hasColumn('contracts', 'finance_object_id')) {
            $schema->table('contracts', function (Blueprint $table): void {
                $table->unsignedBigInteger('finance_object_id')->nullable()->after('id');
            });
            $this->addForeignKey(
                'contracts',
                'contracts_finance_object_fk',
                'finance_object_id',
                'finance_objects',
                'id'
            );
            $this->addIndex('contracts', 'contracts_finance_object_idx', ['finance_object_id']);
            $this->addUnique('contracts', 'contracts_finance_object_unique', ['finance_object_id']);
        }
    }

    public function down(): void
    {
        // forward-only migration
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndex(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $cols = implode(', ', $columns);
        DB::connection($this->connection)->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");
    }

    /**
     * @param array<int, string> $columns
     */
    private function addUnique(string $table, string $indexName, array $columns): void
    {
        if ($this->indexExists($table, $indexName)) {
            return;
        }

        $cols = implode(', ', $columns);
        DB::connection($this->connection)->statement("ALTER TABLE {$table} ADD UNIQUE {$indexName} ({$cols})");
    }

    private function addForeignKey(
        string $table,
        string $constraintName,
        string $column,
        string $refTable,
        string $refColumn
    ): void {
        if ($this->foreignKeyExists($table, $constraintName)) {
            return;
        }

        DB::connection($this->connection)->statement(
            "ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE SET NULL ON UPDATE CASCADE"
        );
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)->table('information_schema.table_constraints')
            ->where('constraint_schema', $db)
            ->where('table_name', $table)
            ->where('constraint_name', $constraintName)
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};

