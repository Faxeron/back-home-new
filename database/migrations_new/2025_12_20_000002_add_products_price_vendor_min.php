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
        Schema::connection($this->connection)->table('products', function (Blueprint $table): void {
            if (!Schema::connection($this->connection)->hasColumn('products', 'price_vendor_min')) {
                $table->float('price_vendor_min')->nullable()->after('price_vendor');
            }
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('products', function (Blueprint $table): void {
            if (Schema::connection($this->connection)->hasColumn('products', 'price_vendor_min')) {
                $table->dropColumn('price_vendor_min');
            }
        });
    }
};