<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->table('cashboxes', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('cashboxes', 'logo_source')) {
                $table->string('logo_source')->nullable()->after('logo_path');
            }
            if (!Schema::connection('legacy_new')->hasColumn('cashboxes', 'logo_preset_id')) {
                $table->unsignedBigInteger('logo_preset_id')->nullable()->after('logo_source');
                $table->index('logo_preset_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->table('cashboxes', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('cashboxes', 'logo_preset_id')) {
                $table->dropIndex(['logo_preset_id']);
                $table->dropColumn('logo_preset_id');
            }
            if (Schema::connection('legacy_new')->hasColumn('cashboxes', 'logo_source')) {
                $table->dropColumn('logo_source');
            }
        });
    }
};
