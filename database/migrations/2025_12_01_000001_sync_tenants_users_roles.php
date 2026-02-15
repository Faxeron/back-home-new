<?php

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
        $db = DB::connection($this->connection);

        if (!$schema->hasTable('tenants')) {
            $schema->create('tenants', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->unsignedBigInteger('owner_user_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('roles')) {
            $schema->create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('user_profiles')) {
            $schema->create('user_profiles', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('position')->nullable();
                $table->string('avatar')->nullable();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('role_users')) {
            $schema->create('role_users', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('role_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();

                $table->unique(['role_id', 'user_id']);
            });
        }

        if (!$schema->hasTable('user_company')) {
            $schema->create('user_company', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('company_id');
                $table->string('role')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'company_id']);
            });
        }

        if ($schema->hasTable('users')) {
            $schema->table('users', function (Blueprint $table) use ($schema): void {
                if (!$schema->hasColumn('users', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                }

                if (!$schema->hasColumn('users', 'company_id')) {
                    $table->unsignedBigInteger('company_id')->nullable()->after('tenant_id');
                }
            });
        }

        $this->addForeignIfMissing('users', 'tenant_id', 'tenants', nullable: true);
        $this->addForeignIfMissing('user_profiles', 'user_id', 'users', onDelete: 'cascade');
        $this->addForeignIfMissing('role_users', 'role_id', 'roles', onDelete: 'cascade');
        $this->addForeignIfMissing('role_users', 'user_id', 'users', onDelete: 'cascade');
        $this->addForeignIfMissing('user_company', 'user_id', 'users', onDelete: 'cascade');
        $this->addForeignIfMissing('user_company', 'company_id', 'companies', onDelete: 'cascade');

        $db->table('tenants')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Main Tenant',
                'code' => 'main',
                'owner_user_id' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);

        $schema->dropIfExists('user_company');
        $schema->dropIfExists('role_users');
        $schema->dropIfExists('user_profiles');
        $schema->dropIfExists('roles');

        if ($schema->hasTable('users') && $schema->hasColumn('users', 'tenant_id')) {
            $schema->table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        $schema->dropIfExists('tenants');
    }

    private function addForeignIfMissing(
        string $table,
        string $column,
        string $referenceTable,
        string $referenceColumn = 'id',
        bool $nullable = false,
        ?string $onDelete = null
    ): void {
        $schema = Schema::connection($this->connection);
        $db = DB::connection($this->connection);

        if (
            !$schema->hasTable($table)
            || !$schema->hasColumn($table, $column)
            || !$schema->hasTable($referenceTable)
        ) {
            return;
        }

        $schemaName = $db->getDriverName() === 'pgsql'
            ? 'public'
            : $db->getDatabaseName();

        $exists = $db->table('information_schema.table_constraints as tc')
            ->join('information_schema.key_column_usage as kcu', function ($join): void {
                $join->on('kcu.constraint_name', '=', 'tc.constraint_name')
                    ->on('kcu.table_schema', '=', 'tc.table_schema');
            })
            ->where('tc.constraint_type', 'FOREIGN KEY')
            ->where('tc.table_schema', $schemaName)
            ->where('kcu.table_schema', $schemaName)
            ->where('tc.table_name', $table)
            ->where('kcu.column_name', $column)
            ->exists();

        if ($exists) {
            return;
        }

        $constraint = "{$table}_{$column}_fk";

        $schema->table($table, function (Blueprint $table) use (
            $column,
            $referenceTable,
            $referenceColumn,
            $nullable,
            $constraint,
            $onDelete
        ): void {
            $fk = $table->foreign($column, $constraint)
                ->references($referenceColumn)
                ->on($referenceTable)
                ->onUpdate('cascade');

            if ($onDelete === 'cascade') {
                $fk->cascadeOnDelete();
                return;
            }

            if ($nullable) {
                $fk->nullOnDelete();
                return;
            }

            $fk->restrictOnDelete();
        });
    }
};
