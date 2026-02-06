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

        if ($schema->hasTable('product_company_prices')) {
            return;
        }

        $schema->create('product_company_prices', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('product_id');

            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('price_sale', 14, 2)->nullable();
            $table->decimal('price_delivery', 14, 2)->nullable();
            $table->decimal('montaj', 14, 2)->nullable();
            $table->decimal('montaj_sebest', 14, 2)->nullable();

            $table->string('currency', 3)->default('RUB');
            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->unique(['tenant_id', 'company_id', 'product_id'], 'prod_company_prices_unique');
            $table->index(['tenant_id', 'company_id'], 'prod_company_prices_tenant_company_idx');
            $table->index(['tenant_id', 'product_id'], 'prod_company_prices_tenant_product_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('product_company_prices');
    }
};
