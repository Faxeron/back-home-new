<?php

namespace App\Http\Controllers\Api;

use App\Domain\Common\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

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

        $userData = [
            'id' => $user->id,
            'fullName' => $user->name,
            'username' => $user->email,
            'email' => $user->email,
            'role' => 'admin',
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
