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

        if ($schema->hasTable('products') && !$schema->hasColumn('products', 'work_kind')) {
            $schema->table('products', function (Blueprint $table): void {
                $table->string('work_kind', 30)->nullable()->after('product_type_id');
            });
        }

        if (!$schema->hasTable('product_relations')) {
            return;
        }

        $linkedIds = DB::connection($this->connection)
            ->table('product_relations')
            ->where('relation_type', 'INSTALLATION_WORK')
            ->pluck('related_product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (!empty($linkedIds)) {
            foreach (array_chunk($linkedIds, 500) as $chunk) {
                DB::connection($this->connection)
                    ->table('products')
                    ->whereIn('id', $chunk)
                    ->update([
                        'work_kind' => 'installation_linked',
                        'updated_at' => now(),
                    ]);
            }
        }

        $workTypeId = null;
        if ($schema->hasTable('product_types')) {
            if ($schema->hasColumn('product_types', 'code')) {
                $workTypeId = DB::connection($this->connection)
                    ->table('product_types')
                    ->where('code', 'WORK')
                    ->value('id');
            }
        }

        if ($workTypeId) {
            DB::connection($this->connection)
                ->table('products')
                ->where('product_type_id', $workTypeId)
                ->whereNull('work_kind')
                ->update([
                    'work_kind' => 'work_standalone',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        if ($schema->hasTable('products') && $schema->hasColumn('products', 'work_kind')) {
            $schema->table('products', function (Blueprint $table): void {
                $table->dropColumn('work_kind');
            });
        }
    }
};
