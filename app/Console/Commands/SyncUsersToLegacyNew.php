<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncUsersToLegacyNew extends Command
{
    protected $signature = 'users:sync-default-to-legacy';

    protected $description = 'One-off sync of users from default connection to legacy_new (preserves ids)';

    public function handle(): int
    {
        $sourceConnection = config('database.default', 'mysql');
        $destConnection = 'legacy_new';

        if ($sourceConnection === $destConnection) {
            $this->info('Source and destination connections are the same; nothing to do.');
            return self::SUCCESS;
        }

        $source = DB::connection($sourceConnection);
        $dest = DB::connection($destConnection);

        $users = $source->table('users')->get();
        if ($users->isEmpty()) {
            $this->warn('No users found in source connection.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($users as $user) {
            $payload = [
                'id' => $user->id,
                'tenant_id' => $user->tenant_id ?? 1,
                'company_id' => $user->company_id ?? null,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'remember_token' => $user->remember_token ?? null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            $dest->table('users')->updateOrInsert(['id' => $user->id], $payload);
            $count++;
        }

        $this->info("Synced {$count} users to legacy_new.users");

        return self::SUCCESS;
    }
}
