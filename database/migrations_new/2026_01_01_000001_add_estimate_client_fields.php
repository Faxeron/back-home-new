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

        if ($schema->hasTable('estimates')) {
            $schema->table('estimates', function (Blueprint $table) use ($schema): void {
                if (!$schema->hasColumn('estimates', 'client_name')) {
                    $table->string('client_name', 255)->nullable()->after('company_id');
                }
                if (!$schema->hasColumn('estimates', 'client_phone')) {
                    $table->string('client_phone', 50)->nullable()->after('client_name');
                }
                if (!$schema->hasColumn('estimates', 'site_address')) {
                    $table->string('site_address', 255)->nullable()->after('client_phone');
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally left blank: no destructive changes on production data.
    }
};
