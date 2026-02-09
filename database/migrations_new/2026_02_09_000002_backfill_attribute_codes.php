<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    protected $connection = 'legacy_new';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (!$schema->hasTable('product_attribute_definitions')) {
            return;
        }

        if (!$schema->hasColumn('product_attribute_definitions', 'code')) {
            $schema->table('product_attribute_definitions', function (Blueprint $table): void {
                $table->string('code', 100)->nullable()->after('unit');
            });
        }

        DB::connection($this->connection)
            ->table('product_attribute_definitions')
            ->select(['id', 'tenant_id', 'company_id', 'name', 'code'])
            ->where(function ($q) {
                $q->whereNull('code')->orWhere('code', '');
            })
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $code = $this->makeUniqueCode(
                        (string) ($row->name ?? ''),
                        $row->tenant_id === null ? null : (int) $row->tenant_id,
                        $row->company_id === null ? null : (int) $row->company_id,
                        (int) $row->id,
                    );

                    DB::connection($this->connection)
                        ->table('product_attribute_definitions')
                        ->where('id', $row->id)
                        ->update(['code' => $code]);
                }
            });
    }

    public function down(): void
    {
        // forward-only migration
    }

    private function makeUniqueCode(string $name, ?int $tenantId, ?int $companyId, int $id): string
    {
        $base = Str::slug(trim($name), '-', 'ru');
        if ($base === '') {
            $base = "attr-{$id}";
        }

        $code = $base;
        $suffix = 2;
        while ($this->codeExists($code, $tenantId, $companyId, $id)) {
            $code = "{$base}-{$suffix}";
            $suffix++;
        }

        return $code;
    }

    private function codeExists(string $code, ?int $tenantId, ?int $companyId, int $excludeId): bool
    {
        $query = DB::connection($this->connection)
            ->table('product_attribute_definitions')
            ->where('code', $code)
            ->where('id', '!=', $excludeId);

        if ($tenantId === null) {
            $query->whereNull('tenant_id');
        } else {
            $query->where('tenant_id', $tenantId);
        }

        if ($companyId === null) {
            $query->whereNull('company_id');
        } else {
            $query->where('company_id', $companyId);
        }

        return $query->exists();
    }
};
