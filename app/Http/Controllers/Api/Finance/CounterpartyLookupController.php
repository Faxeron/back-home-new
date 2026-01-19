<?php

namespace App\Http\Controllers\API\Finance;

use App\Domain\CRM\Models\Counterparty;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CounterpartyLookupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $query = Counterparty::query()->orderBy('name');

        $query->where('tenant_id', $tenantId);
        $query->where('company_id', $companyId);

        $prefix = trim($request->string('phone_prefix')->toString());
        if ($prefix !== '') {
            $digits = preg_replace('/\D+/', '', $prefix) ?? '';
            if ($digits === '') {
                return response()->json(['data' => []]);
            }

            if ($digits[0] === '8') {
                $digits = '7' . substr($digits, 1);
            }

            if ($digits[0] !== '7') {
                $digits = '7' . $digits;
            }

            $digitsNoCountry = $digits;
            if (strlen($digitsNoCountry) > 1 && $digitsNoCountry[0] === '7') {
                $digitsNoCountry = substr($digitsNoCountry, 1);
            }
            $digitsAlt = null;
            if (strlen($digits) > 1 && $digits[0] === '7') {
                $digitsAlt = '8' . substr($digits, 1);
            }

            $pattern = '%' . implode('%', str_split($digits)) . '%';
            $patternAlt = null;
            if (strlen($digits) > 1 && $digits[0] === '7') {
                $alt = '8' . substr($digits, 1);
                $patternAlt = '%' . implode('%', str_split($alt)) . '%';
            }

            $limit = (int) $request->integer('limit', 10);
            $limit = $limit <= 0 ? 10 : min($limit, 50);

            $data = $query
                ->where(function ($builder) use ($digits, $digitsNoCountry, $digitsAlt, $pattern, $patternAlt) {
                    $builder->where('phone_normalized', 'like', "{$digits}%");
                    if ($digitsAlt) {
                        $builder->orWhere('phone_normalized', 'like', "{$digitsAlt}%");
                    }
                    if ($digitsNoCountry !== $digits) {
                        $builder->orWhere('phone_normalized', 'like', "{$digitsNoCountry}%");
                    }
                    $builder->orWhere('phone_normalized', 'like', $pattern);
                    if ($patternAlt) {
                        $builder->orWhere('phone_normalized', 'like', $patternAlt);
                    }
                    $builder->orWhere(function ($phoneQuery) use ($pattern, $patternAlt) {
                        $phoneQuery->where('phone', 'like', $pattern);
                        if ($patternAlt) {
                            $phoneQuery->orWhere('phone', 'like', $patternAlt);
                        }
                    });
                })
                ->limit($limit)
                ->get(['id', 'type', 'name', 'phone', 'phone_normalized', 'email', 'is_active']);

            return response()->json(['data' => $data]);
        }

        $data = $query->get(['id', 'type', 'name', 'phone', 'phone_normalized', 'email', 'is_active']);

        return response()->json(['data' => $data]);
    }

    public function show(Request $request, int $counterparty): JsonResponse
    {
        $tenantId = $request->user()?->tenant_id;
        $companyId = $request->user()?->default_company_id ?? $request->user()?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $model = Counterparty::query()
            ->with(['individual', 'company'])
            ->where('id', $counterparty)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        return response()->json([
            'data' => [
                'id' => $model->id,
                'type' => $model->type,
                'name' => $model->name,
                'phone' => $model->phone,
                'email' => $model->email,
                'individual' => $model->individual ? [
                    'first_name' => $model->individual->first_name,
                    'last_name' => $model->individual->last_name,
                    'patronymic' => $model->individual->patronymic,
                    'passport_series' => $model->individual->passport_series,
                    'passport_number' => $model->individual->passport_number,
                    'passport_code' => $model->individual->passport_code,
                    'passport_whom' => $model->individual->passport_whom,
                    'issued_at' => $model->individual->issued_at,
                    'issued_by' => $model->individual->issued_by,
                    'passport_address' => $model->individual->passport_address,
                ] : null,
                'company' => $model->company ? [
                    'legal_name' => $model->company->legal_name,
                    'short_name' => $model->company->short_name,
                    'inn' => $model->company->inn,
                    'kpp' => $model->company->kpp,
                    'ogrn' => $model->company->ogrn,
                    'legal_address' => $model->company->legal_address,
                    'postal_address' => $model->company->postal_address,
                    'director_name' => $model->company->director_name,
                    'accountant_name' => $model->company->accountant_name,
                    'bank_name' => $model->company->bank_name,
                    'bik' => $model->company->bik,
                    'account_number' => $model->company->account_number,
                    'correspondent_account' => $model->company->correspondent_account,
                ] : null,
            ],
        ]);
    }
}
