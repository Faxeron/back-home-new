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

        if ($schema->hasTable('products') && !$schema->hasColumn('products', 'archived_at')) {
            $schema->table('products', function (Blueprint $table): void {
                $table->timestamp('archived_at')->nullable()->after('is_new');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('products') && $schema->hasColumn('products', 'archived_at')) {
            $schema->table('products', function (Blueprint $table): void {
                $table->dropColumn('archived_at');
            });
        }
    }
};
