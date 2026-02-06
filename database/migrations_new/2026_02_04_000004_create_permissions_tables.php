<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('legacy_new')->create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 190)->unique();
            $table->string('resource', 120);
            $table->string('action', 40);
            $table->string('name', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['resource', 'action'], 'permissions_resource_action_idx');
        });

        Schema::connection('legacy_new')->create('role_permissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id'], 'role_permissions_unique');
            $table->foreign('role_id', 'role_permissions_role_fk')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
            $table->foreign('permission_id', 'role_permissions_permission_fk')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');
        });

        Schema::connection('legacy_new')->create('role_scopes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('resource', 120);
            $table->string('scope', 20)->default('company');
            $table->timestamps();

            $table->unique(['role_id', 'resource'], 'role_scopes_unique');
            $table->index(['role_id'], 'role_scopes_role_idx');
            $table->foreign('role_id', 'role_scopes_role_fk')
                ->references('id')
                ->on('roles')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::connection('legacy_new')->dropIfExists('role_scopes');
        Schema::connection('legacy_new')->dropIfExists('role_permissions');
        Schema::connection('legacy_new')->dropIfExists('permissions');
    }
};
