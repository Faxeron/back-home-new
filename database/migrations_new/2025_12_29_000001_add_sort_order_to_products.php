<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        Schema::connection($this->connection)->table('products', function (Blueprint $table): void {
            if (!Schema::connection($this->connection)->hasColumn('products', 'sort_order')) {
                $table->integer('sort_order')->default(1000)->after('scu');
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty: no drops in production migrations.
    }
};
