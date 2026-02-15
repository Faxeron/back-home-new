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
        $this->updateContractTemplates();
        $this->updateContractDocuments();
    }

    public function down(): void
    {
        // No automatic rollback. Use backups for production data.
    }

    private function updateContractTemplates(): void
    {
        if (!Schema::connection($this->connection)->hasTable('contract_templates')) {
            return;
        }

        Schema::connection($this->connection)->table('contract_templates', function (Blueprint $table): void {
            if (!Schema::connection($this->connection)->hasColumn('contract_templates', 'document_type')) {
                $table->string('document_type', 20)->nullable()->after('docx_template_path');
            }
        });

        DB::connection($this->connection)
            ->table('contract_templates')
            ->whereNull('document_type')
            ->update(['document_type' => 'combined']);
    }

    private function updateContractDocuments(): void
    {
        if (!Schema::connection($this->connection)->hasTable('contract_documents')) {
            return;
        }

        Schema::connection($this->connection)->table('contract_documents', function (Blueprint $table): void {
            if (!Schema::connection($this->connection)->hasColumn('contract_documents', 'document_type')) {
                $table->string('document_type', 20)->nullable()->after('template_id');
            }
            if (!Schema::connection($this->connection)->hasColumn('contract_documents', 'number_suffix')) {
                $table->string('number_suffix', 10)->nullable()->after('document_type');
            }
        });

        if ($this->hasColumn('contract_documents', 'file_path')) {
            $connection = DB::connection($this->connection);
            $driver = $connection->getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $connection->statement('ALTER TABLE contract_documents MODIFY file_path VARCHAR(255) NULL');
            } elseif ($driver === 'pgsql') {
                $connection->statement('ALTER TABLE contract_documents ALTER COLUMN file_path DROP NOT NULL');
            }
        }

        if ($this->hasColumn('contract_documents', 'document_type')) {
            DB::connection($this->connection)
                ->table('contract_documents')
                ->whereNull('document_type')
                ->update(['document_type' => 'combined', 'number_suffix' => '']);
        }

        if (!$this->indexExistsOnColumn('contract_documents', 'document_type')) {
            $this->addIndex('contract_documents', 'contract_documents_type_idx', ['contract_id', 'document_type', 'is_current']);
        }
    }

    private function hasColumn(string $table, string $column): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();
        $schema = $driver === 'pgsql' ? 'public' : $db;

        return $connection->table('information_schema.columns')
            ->where('table_schema', $schema)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    private function indexExistsOnColumn(string $table, string $column): bool
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $db = $connection->getDatabaseName();

        if ($driver === 'pgsql') {
            return $connection->table('pg_indexes')
                ->where('schemaname', 'public')
                ->where('tablename', $table)
                ->whereRaw('indexdef ILIKE ?', ["%($column)%"])
                ->exists();
        }

        return $connection->table('information_schema.statistics')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->exists();
    }

    /**
     * @param array<int, string> $columns
     */
    private function addIndex(string $table, string $indexName, array $columns): void
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();
        $cols = implode(', ', $columns);

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $connection->statement("ALTER TABLE {$table} ADD INDEX {$indexName} ({$cols})");

            return;
        }

        $connection->statement("CREATE INDEX {$indexName} ON {$table} ({$cols})");
    }
};
