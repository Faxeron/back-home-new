<?php

namespace Database\Seeders;

use App\Domain\Common\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test@test.com'],
            [
                'name' => 'Admin',
                'password' => '$2y$10$nYxxvEnvnP4.kXqtuXUSK.rSEmG0VFC0MpAeWwQngwp0thF7eE1BC', // admin
                'tenant_id' => 1,
                'company_id' => null,
            ]
        );
    }
}
