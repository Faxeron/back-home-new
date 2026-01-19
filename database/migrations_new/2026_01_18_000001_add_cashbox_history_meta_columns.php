<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('cashbox_history')) {
            return;
        }

        $addTenant = ! $schema->hasColumn('cashbox_history', 'tenant_id');
        $addCompany = ! $schema->hasColumn('cashbox_history', 'company_id');
        $addCreatedBy = ! $schema->hasColumn('cashbox_history', 'created_by');

        if (! $addTenant && ! $addCompany && ! $addCreatedBy) {
            return;
        }

        $schema->table('cashbox_history', function (Blueprint $table) use ($addTenant, $addCompany, $addCreatedBy): void {
            if ($addTenant) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }

            if ($addCompany) {
                $table->unsignedBigInteger('company_id')->nullable()->after('tenant_id');
            }

            if ($addCreatedBy) {
                $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('cashbox_history')) {
            return;
        }

        $schema->table('cashbox_history', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('cashbox_history', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::connection($this->connection)->hasColumn('cashbox_history', 'company_id')) {
                $table->dropColumn('company_id');
            }

            if (Schema::connection($this->connection)->hasColumn('cashbox_history', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });
    }
};
