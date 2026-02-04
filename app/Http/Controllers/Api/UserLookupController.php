<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserLookupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $db = DB::connection('legacy_new');
        $columns = Schema::connection('legacy_new')->getColumnListing('users');
        $nameColumn = in_array('name', $columns, true)
            ? 'name'
            : (in_array('full_name', $columns, true) ? 'full_name' : null);
        $emailColumn = in_array('email', $columns, true) ? 'email' : null;

        $query = $db->table('users');

        if ($tenantId && in_array('tenant_id', $columns, true)) {
            $query->where('users.tenant_id', $tenantId);
        }

        if ($companyId) {
            if (Schema::connection('legacy_new')->hasTable('user_company')) {
                $query->join('user_company', 'user_company.user_id', '=', 'users.id')
                    ->where('user_company.company_id', $companyId);
            } elseif (in_array('company_id', $columns, true)) {
                $query->where('users.company_id', $companyId);
            }
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search, $nameColumn, $emailColumn) {
                if ($nameColumn) {
                    $q->orWhere("users.{$nameColumn}", 'like', "%{$search}%");
                }
                if ($emailColumn) {
                    $q->orWhere('users.email', 'like', "%{$search}%");
                }
            });
        }

        $select = ['users.id'];
        if ($nameColumn) {
            $select[] = "users.{$nameColumn} as name";
        }
        if ($emailColumn) {
            $select[] = 'users.email';
        }

        $rows = $query->select($select)
            ->distinct()
            ->orderBy('users.id')
            ->limit(5000)
            ->get();

        $roleMap = [];
        if ($rows->isNotEmpty()
            && Schema::connection('legacy_new')->hasTable('role_users')
            && Schema::connection('legacy_new')->hasTable('roles')
        ) {
            $userIds = $rows->pluck('id')
                ->filter(fn ($id) => $id !== null)
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (!empty($userIds)) {
                $roleRows = $db->table('role_users')
                    ->join('roles', 'roles.id', '=', 'role_users.role_id')
                    ->whereIn('role_users.user_id', $userIds)
                    ->select(['role_users.user_id', 'roles.code'])
                    ->get();

                foreach ($roleRows as $roleRow) {
                    $userId = (int) $roleRow->user_id;
                    $code = (string) ($roleRow->code ?? '');
                    if ($code === '') {
                        continue;
                    }
                    if (!isset($roleMap[$userId])) {
                        $roleMap[$userId] = [];
                    }
                    $roleMap[$userId][] = $code;
                }
            }
        }

        $users = $rows->map(function ($row) use ($roleMap) {
            $name = $row->name ?? $row->email ?? ('User #' . $row->id);
            $roles = $roleMap[(int) $row->id] ?? [];
            return [
                'id' => (int) $row->id,
                'name' => $name,
                'email' => $row->email ?? null,
                'role_codes' => array_values(array_unique($roles)),
            ];
        });

        return response()->json(['data' => $users]);
    }
}
