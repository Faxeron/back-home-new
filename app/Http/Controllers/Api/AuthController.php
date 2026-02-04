<?php

namespace App\Http\Controllers\Api;

use App\Domain\Common\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $roles = DB::connection('legacy_new')
                ->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $user->id)
                ->pluck('roles.code')
                ->map(fn ($code) => strtolower((string) $code))
                ->unique();

            if ($roles->contains('superadmin')) {
                $role = 'superadmin';
            } elseif ($roles->contains('admin')) {
                $role = 'admin';
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

        $abilityRules = [
            [
                'action' => 'manage',
                'subject' => 'all',
            ],
        ];

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'accessToken' => $token,
            'userData' => $userData,
            'userAbilityRules' => $abilityRules,
        ]);
    }
}
