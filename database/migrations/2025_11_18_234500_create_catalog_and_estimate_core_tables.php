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

        if (!$schema->hasTable('product_categories')) {
            $schema->create('product_categories', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('slug');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->string('h1')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->boolean('is_global')->default(false);

                $table->unique(['tenant_id', 'company_id', 'slug'], 'product_categories_tenant_company_slug_unique');
            });
        }

        if (!$schema->hasTable('product_subcategories')) {
            $schema->create('product_subcategories', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('slug');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->string('seo_title')->nullable();
                $table->text('seo_description')->nullable();
                $table->string('h1')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->boolean('is_global')->default(false);

                $table->unique(['tenant_id', 'company_id', 'slug'], 'product_subcategories_tenant_company_slug_unique');
            });
        }

        if (!$schema->hasTable('product_brands')) {
            $schema->create('product_brands', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('slug');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->boolean('is_global')->default(false);

                $table->unique(['tenant_id', 'company_id', 'slug'], 'product_brands_tenant_company_slug_unique');
            });
        }

        if (!$schema->hasTable('products')) {
            $schema->create('products', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name');
                $table->string('slug', 191)->nullable();
                $table->unsignedBigInteger('product_type_id')->nullable();
                $table->string('work_kind', 30)->nullable();
                $table->unsignedBigInteger('product_kind_id')->nullable();
                $table->unsignedBigInteger('unit_id')->nullable();
                $table->boolean('is_visible')->default(true);
                $table->boolean('is_top')->default(false);
                $table->boolean('is_new')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->unsignedBigInteger('spending_item_id')->nullable();
                $table->string('scu');
                $table->integer('sort_order')->default(1000);
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('sub_category_id')->nullable();
                $table->unsignedBigInteger('brand_id')->nullable();
                $table->decimal('price_vendor', 15, 2)->nullable();
                $table->decimal('price_vendor_min', 15, 2)->nullable();
                $table->decimal('price_zakup', 15, 2)->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->boolean('is_global')->default(false);

                $table->unique(['tenant_id', 'scu'], 'products_tenant_id_scu_unique');
                $table->index('product_type_id', 'products_product_type_id_idx');
                $table->index('slug', 'products_slug_index');
            });
        }

        if (!$schema->hasTable('estimates')) {
            $schema->create('estimates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('client_name')->nullable();
                $table->string('client_phone', 50)->nullable();
                $table->string('site_address')->nullable();
                $table->text('data');
                $table->string('link');
                $table->string('random_id');
                $table->integer('lead_id')->nullable();
                $table->integer('amo_lead_id')->nullable();
                $table->string('link_montaj');
                $table->timestamp('public_expires_at')->nullable();
                $table->timestamp('public_revoked_at')->nullable();
                $table->integer('client_id')->nullable();
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->integer('sms_sent')->default(0);
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->unique('random_id', 'estimates_random_id_unique');
                $table->index('contract_id', 'estimates_contract_id_idx');
            });
        }

        if (!$schema->hasTable('estimate_groups')) {
            $schema->create('estimate_groups', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('product_type_id')->nullable();
                $table->integer('sort_order')->default(100);
                $table->string('name');
                $table->text('ids');
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->index('product_type_id', 'estimate_groups_product_type_idx');
            });
        }

        if (!$schema->hasTable('estimate_template_materials')) {
            $schema->create('estimate_template_materials', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('title');
                $table->json('data');
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
            });
        }

        if (!$schema->hasTable('estimate_template_septiks')) {
            $schema->create('estimate_template_septiks', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('title');
                $table->json('data');
                $table->string('pattern_ids');
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
            });
        }

        if (!$schema->hasTable('projects')) {
            $schema->create('projects', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();

                $table->index('company_id', 'projects_company_id_idx');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->dropIfExists('estimate_template_septiks');
        $schema->dropIfExists('estimate_template_materials');
        $schema->dropIfExists('estimate_groups');
        $schema->dropIfExists('estimates');
        $schema->dropIfExists('projects');
        $schema->dropIfExists('products');
        $schema->dropIfExists('product_brands');
        $schema->dropIfExists('product_subcategories');
        $schema->dropIfExists('product_categories');
    }
};
