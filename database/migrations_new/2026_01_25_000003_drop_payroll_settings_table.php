<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->dropIfExists('payroll_settings');
    }

    public function down(): void
    {
        // no-op: settings table removed permanently
    }
};
