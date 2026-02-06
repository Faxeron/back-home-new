<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $action, ?string $resource = null): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $resource = $resource ?: $request->route()?->getName();
        if (!$resource) {
            return response()->json(['message' => 'Permission resource is not configured.'], 500);
        }

        if ($this->hasAdminRole((int) $user->id)) {
            return $next($request);
        }

        if (!$this->hasPermissionTables()) {
            return $next($request);
        }

        $code = "{$resource}.{$action}";
        $allowed = DB::connection('legacy_new')
            ->table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_users.user_id', $user->id)
            ->where('permissions.code', $code)
            ->exists();

        if (!$allowed) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }

    private function hasAdminRole(int $userId): bool
    {
        if (!Schema::connection('legacy_new')->hasTable('role_users') || !Schema::connection('legacy_new')->hasTable('roles')) {
            return false;
        }

        return DB::connection('legacy_new')
            ->table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->where('role_users.user_id', $userId)
            ->whereIn('roles.code', ['superadmin', 'admin'])
            ->exists();
    }

    private function hasPermissionTables(): bool
    {
        $schema = Schema::connection('legacy_new');

        return $schema->hasTable('permissions')
            && $schema->hasTable('role_permissions')
            && $schema->hasTable('role_users')
            && $schema->hasTable('roles');
    }
}
