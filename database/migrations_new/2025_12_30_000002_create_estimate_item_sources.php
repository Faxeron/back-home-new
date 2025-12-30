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

        if ($schema->hasTable('estimate_item_sources')) {
            return;
        }

        $schema->create('estimate_item_sources', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('estimate_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('origin_product_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->decimal('qty_per_unit', 14, 2)->default(0);
            $table->decimal('root_qty', 14, 2)->default(0);
            $table->decimal('qty_total', 14, 2)->default(0);
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(
                ['estimate_id', 'product_id', 'origin_product_id', 'template_id'],
                'estimate_item_sources_unique'
            );
            $table->index(['estimate_id'], 'estimate_item_sources_estimate_idx');
            $table->index(['product_id'], 'estimate_item_sources_product_idx');
        });
    }

    public function down(): void
    {
        // Intentionally left blank: no destructive changes on production data.
    }
};
