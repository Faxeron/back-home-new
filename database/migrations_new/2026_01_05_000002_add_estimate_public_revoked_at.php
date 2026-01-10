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

        if ($schema->hasTable('estimates') && !$schema->hasColumn('estimates', 'public_revoked_at')) {
            $schema->table('estimates', function (Blueprint $table): void {
                $table->timestamp('public_revoked_at')->nullable()->after('public_expires_at');
            });
        }

        if ($schema->hasTable('estimates') && $schema->hasColumn('estimates', 'public_revoked_at')) {
            DB::connection($this->connection)
                ->table('estimates')
                ->whereNotNull('public_expires_at')
                ->whereNull('public_revoked_at')
                ->update(['public_revoked_at' => now()]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('estimates') && $schema->hasColumn('estimates', 'public_revoked_at')) {
            $schema->table('estimates', function (Blueprint $table): void {
                $table->dropColumn('public_revoked_at');
            });
        }
    }
};
