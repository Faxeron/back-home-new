<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tenants (SaaS accounts)
        Schema::connection('legacy_new')->create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedBigInteger('owner_user_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Roles
        Schema::connection('legacy_new')->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // User profiles
        Schema::connection('legacy_new')->create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('position')->nullable();
            $table->string('avatar')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // Role → User pivot
        Schema::connection('legacy_new')->create('role_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['role_id', 'user_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // User ↔ Company pivot with role
        Schema::connection('legacy_new')->create('user_company', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('company_id');
            $table->string('role')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'company_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
        });

        // Add tenant_id to users and FK
        Schema::connection('legacy_new')->table('users', function (Blueprint $table): void {
            if (!Schema::connection('legacy_new')->hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });

        // Seed master tenant
        DB::connection('legacy_new')->table('tenants')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Основной аккаунт',
                'code' => 'main',
                'owner_user_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Copy users from primary DB (erp_vuexy) into legacy_new
        $sourceUsers = DB::connection('mysql')->table('users')->get();
        foreach ($sourceUsers as $user) {
            DB::connection('legacy_new')->table('users')->updateOrInsert(
                ['id' => $user->id],
                [
                    'tenant_id' => 1,
                    'company_id' => $user->company_id ?? null,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'password' => $user->password,
                    'remember_token' => $user->remember_token ?? null,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ]
            );
        }

        // Link tenant owner if user id=1 exists
        $hasUserOne = DB::connection('legacy_new')->table('users')->where('id', 1)->exists();
        if ($hasUserOne) {
            DB::connection('legacy_new')->table('tenants')->where('id', 1)->update(['owner_user_id' => 1]);
        }

        // Pre-fill user_company pivot based on users.company_id
        $legacyUsers = DB::connection('legacy_new')->table('users')->whereNotNull('company_id')->get();
        foreach ($legacyUsers as $lu) {
            DB::connection('legacy_new')->table('user_company')->updateOrInsert(
                ['user_id' => $lu->id, 'company_id' => $lu->company_id],
                [
                    'role' => 'owner',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // Pivot drops first
        Schema::connection('legacy_new')->dropIfExists('user_company');
        Schema::connection('legacy_new')->dropIfExists('role_users');
        Schema::connection('legacy_new')->dropIfExists('user_profiles');
        Schema::connection('legacy_new')->dropIfExists('roles');

        Schema::connection('legacy_new')->table('users', function (Blueprint $table): void {
            if (Schema::connection('legacy_new')->hasColumn('users', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            }
        });

        Schema::connection('legacy_new')->dropIfExists('tenants');
    }
};
