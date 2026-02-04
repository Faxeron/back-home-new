<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('cashboxes', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('cashboxes', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('cashboxes', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('cashboxes', 'logo_path')) {
                $table->dropColumn('logo_path');
            }
        });
    }
};
