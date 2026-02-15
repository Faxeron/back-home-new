<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection($this->connection)->getDriverName() === 'pgsql') {
            return;
        }

        // legacy placeholder: migration already handled in other scripts
    }

    public function down(): void
    {
        // nothing to rollback
    }
};

