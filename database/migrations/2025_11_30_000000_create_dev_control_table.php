<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('dev_control', function (Blueprint $table): void {
            $table->id();
            $table->string('module')->unique();
            $table->string('er_status')->default('TODO');
            $table->string('model_status')->default('TODO');
            $table->string('list_api_status')->default('TODO');
            $table->string('crud_api_status')->default('TODO');
            $table->string('filters_status')->default('TODO');
            $table->string('list_ui_status')->default('TODO');
            $table->string('form_ui_status')->default('TODO');
            $table->string('tests_status')->default('TODO');
            $table->string('docs_status')->default('TODO');
            $table->string('deploy_status')->default('TODO');
            $table->unsignedInteger('sort_index')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('dev_control');
    }
};
