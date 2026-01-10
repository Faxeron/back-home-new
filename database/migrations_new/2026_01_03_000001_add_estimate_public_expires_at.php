<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('estimates')) {
            $schema->table('estimates', function (Blueprint $table) use ($schema): void {
                if (!$schema->hasColumn('estimates', 'public_expires_at')) {
                    $table->timestamp('public_expires_at')->nullable()->after('link_montaj');
                }
            });

            if ($schema->hasColumn('estimates', 'public_expires_at')) {
                DB::connection($this->connection)
                    ->table('estimates')
                    ->update(['public_expires_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank: no destructive changes on production data.
    }
};
