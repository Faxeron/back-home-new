<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection('legacy_new');

        $admin = $db->table('roles')->where('code', 'admin')->first();
        $candidates = $db->table('roles')
            ->whereIn('name', ['Админ', 'Admin'])
            ->orWhereIn('code', ['ADMIN', 'Admin'])
            ->get();

        if (!$admin) {
            $admin = $candidates->first();
            if ($admin) {
                $db->table('roles')
                    ->where('id', $admin->id)
                    ->update([
                        'code' => 'admin',
                        'name' => 'Админ',
                    ]);
            }
        }

        if (!$admin) {
            return;
        }

        $duplicateIds = $candidates
            ->pluck('id')
            ->filter(fn ($id) => (int) $id !== (int) $admin->id)
            ->values()
            ->all();

        if (!empty($duplicateIds)) {
            $db->table('role_users')
                ->whereIn('role_id', $duplicateIds)
                ->update(['role_id' => $admin->id]);

            $db->table('roles')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }

        $db->table('roles')
            ->where('id', $admin->id)
            ->update([
                'code' => 'admin',
                'name' => 'Админ',
            ]);
    }

    public function down(): void
    {
        // No rollback needed.
    }
};
