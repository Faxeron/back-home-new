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

        if (!$this->tableExists('contract_status_changes')) {
            $schema->create('contract_status_changes', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('contract_id');
                $table->unsignedBigInteger('previous_status_id')->nullable();
                $table->unsignedBigInteger('new_status_id');
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->timestamp('changed_at')->nullable();
                $table->timestamps();

                $table->index(['contract_id', 'changed_at'], 'contract_status_changes_contract_idx');
                $table->index(['company_id', 'contract_id'], 'contract_status_changes_company_contract_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('contract_status_changes');
    }

    private function tableExists(string $table): bool
    {
        $db = DB::connection($this->connection)->getDatabaseName();

        return DB::connection($this->connection)
            ->table('information_schema.tables')
            ->where('table_schema', $db)
            ->where('table_name', $table)
            ->exists();
    }
};
