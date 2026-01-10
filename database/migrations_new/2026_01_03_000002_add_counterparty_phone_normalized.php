<?php

declare(strict_types=1);

use App\Domain\CRM\Models\Counterparty;
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

        if ($schema->hasTable('counterparties')) {
            $schema->table('counterparties', function (Blueprint $table) use ($schema): void {
                if (!$schema->hasColumn('counterparties', 'phone_normalized')) {
                    $table->string('phone_normalized', 20)->nullable()->after('phone');
                }
            });

            if ($schema->hasColumn('counterparties', 'phone_normalized')) {
                DB::connection($this->connection)
                    ->table('counterparties')
                    ->select('id', 'phone')
                    ->whereNotNull('phone')
                    ->orderBy('id')
                    ->chunkById(200, function ($rows): void {
                        foreach ($rows as $row) {
                            $normalized = Counterparty::normalizePhone((string) $row->phone);
                            DB::connection('legacy_new')
                                ->table('counterparties')
                                ->where('id', $row->id)
                                ->update(['phone_normalized' => $normalized]);
                        }
                    });

            }
        }
    }

    public function down(): void
    {
        // Intentionally left blank: no destructive changes on production data.
    }
};
