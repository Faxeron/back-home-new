<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RolesPermissionsController extends Controller
{
    private const FIXED_ROLE_CODES = ['superadmin', 'admin', 'manager', 'measurer', 'worker'];

    public function index(Request $request): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $this->ensureRoles($tenantId);
        PermissionRegistry::sync();

        $roles = $this->fetchRoles($tenantId);
        foreach ($roles as $role) {
            if ($role['is_locked']) {
                $this->syncAdminPermissions($role['id']);
                $this->ensureRoleScopes($role['id'], 'all');
            } else {
                $this->ensureRoleScopes($role['id'], 'company');
            }
        }
        $roleIds = collect($roles)->pluck('id')->all();

        $permissions = $this->fetchPermissions();
        $permissionsById = collect($permissions)->keyBy('id');

        $rolePermissions = $this->fetchRolePermissions($roleIds, $permissionsById);
        $roleScopes = $this->fetchRoleScopes($roleIds);

        $users = $this->fetchUsers($tenantId);

        return response()->json([
            'data' => [
                'roles' => $roles,
                'resources' => PermissionRegistry::resources(),
                'actions' => PermissionRegistry::actions(),
                'permissions' => $permissions,
                'role_permissions' => $rolePermissions,
                'role_scopes' => $roleScopes,
                'scope_options' => [
                    ['key' => 'own', 'label' => 'Свои'],
                    ['key' => 'company', 'label' => 'Компания'],
                    ['key' => 'tenant', 'label' => 'Тенант'],
                    ['key' => 'all', 'label' => 'Все'],
                ],
                'users' => $users,
            ],
        ]);
    }

    public function updateRole(Request $request, int $roleId): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $role = $this->findRole($roleId, $tenantId);
        if (!$role) {
            return response()->json(['message' => 'Role not found.'], 404);
        }

        if (in_array($role->code, ['admin', 'superadmin'], true)) {
            $this->syncAdminPermissions($role->id);
            $this->ensureRoleScopes($role->id, 'all');

            return response()->json(['status' => 'ok']);
        }

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => ['string'],
        ]);

        if (array_key_exists('permissions', $data)) {
            $this->syncRolePermissions($role->id, $data['permissions'] ?? []);
        }

        if (array_key_exists('scopes', $data)) {
            $this->syncRoleScopes($role->id, $data['scopes'] ?? []);
        }

        return response()->json(['status' => 'ok']);
    }

    public function updateUserRoles(Request $request, int $userId): JsonResponse
    {
        $tenantId = $this->resolveTenantId($request);
        $data = $request->validate([
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer'],
        ]);

        $roleIds = collect($data['role_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $db = DB::connection('legacy_new');
        $user = $db->table('users')
            ->where('id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $rolesQuery = $db->table('roles')->whereIn('id', $roleIds);
        if (Schema::connection('legacy_new')->hasColumn('roles', 'tenant_id')) {
            $rolesQuery->where('tenant_id', $tenantId);
        }
        $validRoleIds = $rolesQuery->pluck('id')->all();

        $deleteQuery = $db->table('role_users')->where('user_id', $userId);
        if (Schema::connection('legacy_new')->hasColumn('role_users', 'tenant_id')) {
            $deleteQuery->where('tenant_id', $tenantId);
        }
        $deleteQuery->delete();

        $now = now();
        $pivotHasTenant = Schema::connection('legacy_new')->hasColumn('role_users', 'tenant_id');
        foreach ($validRoleIds as $roleId) {
            $payload = [
                'role_id' => $roleId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($pivotHasTenant) {
                $payload['tenant_id'] = $tenantId;
            }

            $db->table('role_users')->insert($payload);
        }

        return response()->json(['status' => 'ok']);
    }

    private function resolveTenantId(Request $request): int
    {
        return (int) ($request->user()?->tenant_id ?? 1);
    }

    private function ensureRoles(int $tenantId): void
    {
        if (!Schema::connection('legacy_new')->hasTable('roles')) {
            return;
        }

        $now = now();
        $roles = [
            ['code' => 'superadmin', 'name' => 'Суперадмин'],
            ['code' => 'admin', 'name' => 'Админ'],
            ['code' => 'manager', 'name' => 'Менеджер'],
            ['code' => 'measurer', 'name' => 'Замерщик'],
            ['code' => 'worker', 'name' => 'Монтажник'],
        ];

        $db = DB::connection('legacy_new');
        $rolesHasTenant = Schema::connection('legacy_new')->hasColumn('roles', 'tenant_id');

        foreach ($roles as $role) {
            $where = ['code' => $role['code']];
            if ($rolesHasTenant) {
                $where['tenant_id'] = $tenantId;
            }

            $payload = [
                'name' => $role['name'],
                'is_active' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ];
            if ($rolesHasTenant) {
                $payload['tenant_id'] = $tenantId;
            }

            $db->table('roles')->updateOrInsert($where, $payload);
        }
    }

    private function fetchRoles(int $tenantId): array
    {
        $query = DB::connection('legacy_new')->table('roles');
        if (Schema::connection('legacy_new')->hasColumn('roles', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return $query
            ->whereIn('code', self::FIXED_ROLE_CODES)
            ->orderByRaw("FIELD(code, 'superadmin', 'admin', 'manager', 'measurer', 'worker')")
            ->get(['id', 'code', 'name', 'is_active'])
            ->map(fn ($role) => [
                'id' => (int) $role->id,
                'code' => $role->code,
                'name' => $role->name,
                'is_active' => (bool) $role->is_active,
                'is_locked' => in_array($role->code, ['superadmin', 'admin'], true),
            ])
            ->all();
    }

    private function fetchPermissions(): array
    {
        return DB::connection('legacy_new')
            ->table('permissions')
            ->orderBy('resource')
            ->orderBy('action')
            ->get(['id', 'code', 'resource', 'action', 'name'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => $row->code,
                'resource' => $row->resource,
                'action' => $row->action,
                'name' => $row->name,
            ])
            ->all();
    }

    private function fetchRolePermissions(array $roleIds, $permissionsById): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $rows = DB::connection('legacy_new')
            ->table('role_permissions')
            ->whereIn('role_id', $roleIds)
            ->get(['role_id', 'permission_id']);

        $map = [];
        foreach ($rows as $row) {
            $roleId = (int) $row->role_id;
            $permission = $permissionsById->get($row->permission_id);
            if (!$permission) {
                continue;
            }
            $permissionCode = is_array($permission)
                ? ($permission['code'] ?? null)
                : ($permission->code ?? null);

            if (!$permissionCode) {
                continue;
            }

            $map[$roleId][] = $permissionCode;
        }

        return $map;
    }

    private function fetchRoleScopes(array $roleIds): array
    {
        if (empty($roleIds)) {
            return [];
        }

        $rows = DB::connection('legacy_new')
            ->table('role_scopes')
            ->whereIn('role_id', $roleIds)
            ->get(['role_id', 'resource', 'scope']);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->role_id][$row->resource] = $row->scope;
        }

        return $map;
    }

    private function fetchUsers(int $tenantId): array
    {
        $db = DB::connection('legacy_new');
        $users = $db->table('users')
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'default_company_id']);

        $userIds = $users->pluck('id')->all();
        $roles = [];
        if (!empty($userIds)) {
            $rolesQuery = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->whereIn('role_users.user_id', $userIds)
                ->whereIn('roles.code', self::FIXED_ROLE_CODES);
            if (Schema::connection('legacy_new')->hasColumn('roles', 'tenant_id')) {
                $rolesQuery->where('roles.tenant_id', $tenantId);
            }

            $rows = $rolesQuery
                ->select(['role_users.user_id', 'roles.id as role_id', 'roles.code'])
                ->get();

            foreach ($rows as $row) {
                $roles[(int) $row->user_id][] = [
                    'id' => (int) $row->role_id,
                    'code' => $row->code,
                ];
            }
        }

        return $users->map(function ($user) use ($roles) {
            $roleList = $roles[(int) $user->id] ?? [];
            return [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->default_company_id,
                'role_ids' => collect($roleList)->pluck('id')->values()->all(),
                'role_codes' => collect($roleList)->pluck('code')->values()->all(),
            ];
        })->all();
    }

    private function findRole(int $roleId, int $tenantId): ?object
    {
        $query = DB::connection('legacy_new')
            ->table('roles')
            ->where('id', $roleId);

        if (Schema::connection('legacy_new')->hasColumn('roles', 'tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    private function syncRolePermissions(int $roleId, array $permissionCodes): void
    {
        $db = DB::connection('legacy_new');

        $permissionIds = $db->table('permissions')
            ->whereIn('code', $permissionCodes)
            ->pluck('id')
            ->all();

        $db->table('role_permissions')->where('role_id', $roleId)->delete();

        $now = now();
        foreach ($permissionIds as $permissionId) {
            $db->table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function syncRoleScopes(int $roleId, array $scopes): void
    {
        $db = DB::connection('legacy_new');
        $db->table('role_scopes')->where('role_id', $roleId)->delete();

        $now = now();
        foreach ($scopes as $resource => $scope) {
            if (!is_string($resource) || !is_string($scope)) {
                continue;
            }
            $db->table('role_scopes')->insert([
                'role_id' => $roleId,
                'resource' => $resource,
                'scope' => $scope,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function syncAdminPermissions(int $roleId): void
    {
        $db = DB::connection('legacy_new');
        $permissionIds = $db->table('permissions')->pluck('id')->all();

        $db->table('role_permissions')->where('role_id', $roleId)->delete();

        $now = now();
        foreach ($permissionIds as $permissionId) {
            $db->table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function ensureRoleScopes(int $roleId, string $defaultScope): void
    {
        $db = DB::connection('legacy_new');
        $existing = $db->table('role_scopes')
            ->where('role_id', $roleId)
            ->pluck('resource')
            ->all();

        $now = now();
        foreach (PermissionRegistry::RESOURCES as $resource => $label) {
            if (in_array($resource, $existing, true)) {
                continue;
            }
            $db->table('role_scopes')->insert([
                'role_id' => $roleId,
                'resource' => $resource,
                'scope' => $defaultScope,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
