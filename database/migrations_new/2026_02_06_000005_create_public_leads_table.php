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

        if ($schema->hasTable('public_leads')) {
            return;
        }

        $schema->create('public_leads', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();

            $table->string('name', 255);
            $table->string('phone', 64);
            $table->string('email', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('page_url', 500)->nullable();
            $table->string('source', 64)->default('public_api');

            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();

            $table->json('payload')->nullable();

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['tenant_id', 'company_id'], 'public_leads_tenant_company_idx');
            $table->index(['tenant_id', 'created_at'], 'public_leads_tenant_created_idx');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('public_leads');
    }
};
