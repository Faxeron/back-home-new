<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('mysql')->statement("ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        if (!Schema::connection('mysql')->hasTable('user_profiles')) {
            Schema::connection('mysql')->create('user_profiles', function (Blueprint $table): void {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('middle_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('phone_alt')->nullable();
                $table->string('registration_address')->nullable();
                $table->string('passport_series', 10)->nullable();
                $table->string('passport_number', 20)->nullable();
                $table->string('passport_issued_by')->nullable();
                $table->date('passport_issued_at')->nullable();
                $table->string('passport_department_code', 20)->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (!Schema::connection('mysql')->hasTable('roles')) {
            Schema::connection('mysql')->create('roles', function (Blueprint $table): void {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->string('name')->unique();
                $table->timestamps();
            });
        }

        if (!Schema::connection('mysql')->hasTable('role_user')) {
            Schema::connection('mysql')->create('role_user', function (Blueprint $table): void {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->primary(['role_id', 'user_id']);
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        $users = [
            ['id' => 1, 'name' => 'admin', 'email' => 'test@test.com'],
            ['id' => 2, 'name' => 'Анастасия', 'email' => 'manager@septik-sever.ru'],
            ['id' => 3, 'name' => 'Сурхай', 'email' => 'surhai@surhai.ru'],
            ['id' => 4, 'name' => 'Сергей', 'email' => 'savin@savi.ru'],
        ];

        foreach ($users as $user) {
            DB::connection('mysql')->table('users')->updateOrInsert(
                ['id' => $user['id']],
                [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        foreach ($users as $user) {
            DB::connection('mysql')->table('user_profiles')->updateOrInsert(
                ['user_id' => $user['id']],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }

        $roles = [
            ['id' => 1, 'name' => 'Админ'],
            ['id' => 2, 'name' => 'Менеджер'],
            ['id' => 3, 'name' => 'Монтажник'],
            ['id' => 4, 'name' => 'Замерщик'],
        ];

        foreach ($roles as $role) {
            DB::connection('mysql')->table('roles')->updateOrInsert(
                ['id' => $role['id']],
                ['name' => $role['name'], 'updated_at' => now(), 'created_at' => now()]
            );
        }

        $roleUser = [
            ['user_id' => 1, 'role_id' => 1],
            ['user_id' => 1, 'role_id' => 2],
            ['user_id' => 1, 'role_id' => 3],
            ['user_id' => 1, 'role_id' => 4],
            ['user_id' => 2, 'role_id' => 2],
            ['user_id' => 3, 'role_id' => 3],
            ['user_id' => 3, 'role_id' => 4],
            ['user_id' => 4, 'role_id' => 4],
        ];

        foreach ($roleUser as $link) {
            DB::connection('mysql')->table('role_user')->updateOrInsert(
                ['user_id' => $link['user_id'], 'role_id' => $link['role_id']],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::connection('mysql')->dropIfExists('role_user');
        Schema::connection('mysql')->dropIfExists('roles');
        Schema::connection('mysql')->dropIfExists('user_profiles');
    }
};
