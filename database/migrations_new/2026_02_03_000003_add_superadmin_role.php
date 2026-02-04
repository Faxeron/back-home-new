<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection('legacy_new');

        $db->table('roles')->updateOrInsert(
            ['code' => 'superadmin'],
            [
                'name' => 'Суперадмин',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::connection('legacy_new')
            ->table('roles')
            ->where('code', 'superadmin')
            ->delete();
    }
};
