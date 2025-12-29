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

        if ($schema->hasTable('products') && $schema->hasColumn('products', 'contract_type_default')) {
            $schema->table('products', function (Blueprint $table): void {
                $table->dropColumn('contract_type_default');
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('products') && !$schema->hasColumn('products', 'contract_type_default')) {
            $schema->table('products', function (Blueprint $table): void {
                $table->string('contract_type_default', 20)->nullable()->after('unit_id');
            });
        }
    }
};
