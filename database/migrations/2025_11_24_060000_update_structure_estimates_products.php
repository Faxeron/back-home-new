<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('companies', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('companies', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });

        if (!Schema::connection('legacy_new')->hasTable('product_types')) {
            Schema::connection('legacy_new')->create('product_types', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
            });

            DB::connection('legacy_new')->table('product_types')->insert([
                ['id' => 1, 'code' => 'MATERIAL', 'name' => 'Материал'],
                ['id' => 2, 'code' => 'PRODUCT', 'name' => 'Товар'],
                ['id' => 3, 'code' => 'WORK', 'name' => 'Работа'],
                ['id' => 4, 'code' => 'SERVICE', 'name' => 'Услуга'],
                ['id' => 5, 'code' => 'TRANSPORT', 'name' => 'Транспорт'],
                ['id' => 6, 'code' => 'SUBCONTRACT', 'name' => 'Субподряд'],
            ]);
        }

        Schema::connection('legacy_new')->table('products', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('products', 'product_type_id')) {
                $table->unsignedBigInteger('product_type_id')->nullable()->after('name');
            }
        });

        if (!Schema::connection('legacy_new')->hasTable('estimate_items')) {
            Schema::connection('legacy_new')->create('estimate_items', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('estimate_id')->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->decimal('qty', 14, 2)->default(0);
                $table->decimal('price', 14, 2)->default(0);
                $table->decimal('total', 14, 2)->default(0);
                $table->unsignedBigInteger('group_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
            });
        }

        if (!Schema::connection('legacy_new')->hasTable('contract_estimates')) {
            Schema::connection('legacy_new')->create('contract_estimates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->longText('data_json')->nullable();
                $table->decimal('total_sum', 14, 2)->default(0);
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
            });
        }

        if (!Schema::connection('legacy_new')->hasTable('contract_act_estimates')) {
            Schema::connection('legacy_new')->create('contract_act_estimates', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('act_id')->nullable();
                $table->longText('data_json')->nullable();
                $table->decimal('total_sum', 14, 2)->default(0);
                $table->timestamp('created_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('companies', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('companies', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('tenant_id');
            }
        });

        Schema::connection('legacy_new')->table('products', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('products', 'product_type_id')) {
                $table->dropColumn('product_type_id');
            }
        });

        Schema::connection('legacy_new')->dropIfExists('contract_act_estimates');
        Schema::connection('legacy_new')->dropIfExists('contract_estimates');
        Schema::connection('legacy_new')->dropIfExists('estimate_items');
        Schema::connection('legacy_new')->dropIfExists('product_types');
    }
};
