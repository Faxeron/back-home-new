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
        if (!Schema::connection($this->connection)->hasTable('contract_templates')) {
            return;
        }

        Schema::connection($this->connection)->table('contract_templates', function (Blueprint $table): void {
            if (!Schema::connection($this->connection)->hasColumn('contract_templates', 'advance_mode')) {
                $table->string('advance_mode', 20)->nullable()->after('docx_template_path');
            }
            if (!Schema::connection($this->connection)->hasColumn('contract_templates', 'advance_percent')) {
                $table->decimal('advance_percent', 6, 2)->nullable()->after('advance_mode');
            }
            if (!Schema::connection($this->connection)->hasColumn('contract_templates', 'advance_product_type_ids')) {
                $table->json('advance_product_type_ids')->nullable()->after('advance_percent');
            }
        });
    }

    public function down(): void
    {
        // No automatic rollback. Use backups for production data.
    }
};
