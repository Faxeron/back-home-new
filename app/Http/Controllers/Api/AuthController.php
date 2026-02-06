<?php

namespace App\Http\Controllers\Api;

use App\Domain\Common\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\Permissions\PermissionRegistry;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'errors' => [
                    'email' => ['Invalid credentials'],
                ],
            ], 401);
        }

        $role = 'admin';
        $roleCodes = collect();
        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $roleCodes = DB::connection('legacy_new')
                ->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $user->id)
                ->pluck('roles.code')
                ->map(fn ($code) => strtolower((string) $code))
                ->unique();

            if ($roleCodes->contains('superadmin')) {
                $role = 'superadmin';
            } elseif ($roleCodes->contains('admin')) {
                $role = 'admin';
            } elseif ($roleCodes->contains('manager')) {
                $role = 'manager';
            } elseif ($roleCodes->contains('measurer')) {
                $role = 'measurer';
            } elseif ($roleCodes->contains('worker')) {
                $role = 'worker';
            } else {
                $role = 'user';
            }
        }

        $userData = [
            'id' => $user->id,
            'fullName' => $user->name,
            'username' => $user->email,
            'email' => $user->email,
            'role' => $role,
            'tenant_id' => $user->tenant_id,
            'company_id' => $user->default_company_id ?? $user->company_id ?? null,
            'avatar' => null,
        ];

        $abilityRules = $this->resolveAbilityRules($user->id, $roleCodes);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'userData' => $userData,
            'userAbilityRules' => $abilityRules,
        ]);
    }

    private function resolveAbilityRules(int $userId, $roleCodes): array
    {
        $codes = collect($roleCodes)->map(fn ($code) => strtolower((string) $code));
        if ($codes->contains('superadmin') || $codes->contains('admin')) {
            return [
                [
                    'action' => 'manage',
                    'subject' => 'all',
                ],
            ];
        }

        if (!Schema::connection('legacy_new')->hasTable('role_permissions') || !Schema::connection('legacy_new')->hasTable('permissions')) {
            return [];
        }

        PermissionRegistry::sync();

        $rows = DB::connection('legacy_new')
            ->table('role_users')
            ->join('roles', 'roles.id', '=', 'role_users.role_id')
            ->join('role_permissions', 'role_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->where('role_users.user_id', $userId)
            ->select(['permissions.action', 'permissions.resource'])
            ->distinct()
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->map(fn ($row) => [
                'action' => $row->action,
                'subject' => $row->resource,
            ])
            ->unique()
            ->values()
            ->all();
    }
}
