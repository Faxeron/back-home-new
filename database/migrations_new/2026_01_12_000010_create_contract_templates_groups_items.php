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
        $this->createContractTemplates();
        $this->createContractTemplateProductTypes();
        $this->createContractGroups();
        $this->createContractItems();
        $this->createContractDocuments();
        $this->addContractColumns();
        $this->ensureDraftStatus();
    }

    public function down(): void
    {
        // No automatic rollback. Use backups for production data.
    }

    private function createContractTemplates(): void
    {
        if (Schema::connection($this->connection)->hasTable('contract_templates')) {
            return;
        }

        Schema::connection($this->connection)->create('contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('short_name', 50);
            $table->string('docx_template_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['company_id', 'short_name'], 'contract_templates_company_short_unique');
            $table->index(['company_id', 'is_active'], 'contract_templates_company_active_idx');
        });
    }

    private function createContractTemplateProductTypes(): void
    {
        if (Schema::connection($this->connection)->hasTable('contract_template_product_types')) {
            return;
        }

        Schema::connection($this->connection)->create('contract_template_product_types', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('template_id');
            $table->unsignedBigInteger('product_type_id');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['template_id', 'product_type_id'], 'contract_template_product_types_unique');
            $table->index(['template_id'], 'contract_template_product_types_template_idx');

            $table->foreign('template_id', 'contract_template_product_types_template_fk')
                ->references('id')
                ->on('contract_templates')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
            $table->foreign('product_type_id', 'contract_template_product_types_type_fk')
                ->references('id')
                ->on('product_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    private function createContractGroups(): void
    {
        if (Schema::connection($this->connection)->hasTable('contract_groups')) {
            return;
        }

        Schema::connection($this->connection)->create('contract_groups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('estimate_id')->nullable();
            $table->unsignedBigInteger('counterparty_id')->nullable();
            $table->string('counterparty_type', 20)->nullable();
            $table->date('contract_date')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('site_address')->nullable();
            $table->unsignedBigInteger('sale_type_id')->nullable();
            $table->date('installation_date')->nullable();
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->unsignedBigInteger('contract_status_id')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['company_id', 'estimate_id'], 'contract_groups_company_estimate_idx');
            $table->index(['counterparty_id'], 'contract_groups_counterparty_idx');
        });
    }

    private function createContractItems(): void
    {
        if (Schema::connection($this->connection)->hasTable('contract_items')) {
            return;
        }

        Schema::connection($this->connection)->create('contract_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('estimate_item_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_type_id')->nullable();
            $table->unsignedBigInteger('unit_id')->nullable();
            $table->string('scu', 100)->nullable();
            $table->string('name');
            $table->decimal('qty', 14, 3)->default(0);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(100);
            $table->string('group_name')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['contract_id'], 'contract_items_contract_idx');
            $table->index(['product_id'], 'contract_items_product_idx');
        });
    }

    private function createContractDocuments(): void
    {
        if (Schema::connection($this->connection)->hasTable('contract_documents')) {
            return;
        }

        Schema::connection($this->connection)->create('contract_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('file_path');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_current')->default(true);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->index(['contract_id', 'is_current'], 'contract_documents_contract_current_idx');
        });
    }

    private function addContractColumns(): void
    {
        Schema::connection($this->connection)->table('contracts', function (Blueprint $table): void {
            if (!Schema::connection($this->connection)->hasColumn('contracts', 'contract_group_id')) {
                $table->unsignedBigInteger('contract_group_id')->nullable()->after('counterparty_id');
            }
            if (!Schema::connection($this->connection)->hasColumn('contracts', 'contract_template_id')) {
                $table->unsignedBigInteger('contract_template_id')->nullable()->after('contract_group_id');
            }
            if (!Schema::connection($this->connection)->hasColumn('contracts', 'template_name')) {
                $table->string('template_name')->nullable()->after('contract_template_id');
            }
            if (!Schema::connection($this->connection)->hasColumn('contracts', 'template_short_name')) {
                $table->string('template_short_name', 50)->nullable()->after('template_name');
            }
            if (!Schema::connection($this->connection)->hasColumn('contracts', 'template_product_type_ids')) {
                $table->json('template_product_type_ids')->nullable()->after('template_short_name');
            }
            if (!Schema::connection($this->connection)->hasColumn('contracts', 'estimate_id')) {
                $table->unsignedBigInteger('estimate_id')->nullable()->after('template_product_type_ids');
            }
        });

        if ($this->hasColumn('contracts', 'contract_group_id') && !$this->indexExistsOnColumn('contracts', 'contract_group_id')) {
            $this->addIndex('contracts', 'contracts_contract_group_id_idx', ['contract_group_id']);
        }
        if ($this->hasColumn('contracts', 'estimate_id') && !$this->indexExistsOnColumn('contracts', 'estimate_id')) {
            $this->addIndex('contracts', 'contracts_estimate_id_idx', ['estimate_id']);
        }
    }

    private function ensureDraftStatus(): void
    {
        $db = DB::connection($this->connection);
        $exists = $db->table('contract_statuses')
            ->where('code', 'DRAFT')
            ->exists();

        if (!$exists) {
            $db->table('contract_statuses')->insert([
                'name' => 'Черновик',
                'code' => 'DRAFT',
                'color' => '#94a3b8',
                'sort_order' => 10,
                'is_active' => true,
            ]);
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
