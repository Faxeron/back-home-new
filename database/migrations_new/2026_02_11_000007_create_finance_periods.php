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
        Schema::connection($this->connection)->create('finance_periods', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('company_id')->index();
            $table->char('year_month', 7); // YYYY-MM
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'year_month'], 'ix_periods_unique');
            $table->index(['tenant_id', 'company_id', 'status'], 'ix_periods_status');

            $table->foreign('closed_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('finance_periods');
    }
};
