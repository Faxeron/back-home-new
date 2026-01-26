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

        $users = $query->select($select)
            ->distinct()
            ->orderBy('users.id')
            ->limit(5000)
            ->get()
            ->map(function ($row) {
                $name = $row->name ?? $row->email ?? ('User #' . $row->id);
                return [
                    'id' => (int) $row->id,
                    'name' => $name,
                    'email' => $row->email ?? null,
                ];
            });

        return response()->json(['data' => $users]);
    }
}
