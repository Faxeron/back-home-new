<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('margin_settings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->decimal('red_max', 5, 2)->default(10);
            $table->decimal('orange_max', 5, 2)->default(20);
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id'], 'margin_settings_tenant_company_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('margin_settings');
    }
};
