<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Domain\Finance\Enums\FinanceObjectStatus;
use App\Domain\Finance\Enums\FinanceObjectType;
use App\Services\Finance\FinanceObjectTypeService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use RuntimeException;

class UpdateFinanceObjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = (int) ($this->user()?->tenant_id ?? 0);
        $companyId = (int) ($this->user()?->default_company_id ?? $this->user()?->company_id ?? 0);
        $objectId = (int) ($this->route('financeObject')?->id ?? $this->route('financeObject'));

        return [
            'type' => ['sometimes', Rule::in(FinanceObjectType::values())],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'nullable',
                'string',
                'max:120',
                Rule::unique('legacy_new.finance_objects', 'code')
                    ->ignore($objectId)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)),
            ],
            'status' => ['sometimes', Rule::in(FinanceObjectStatus::values())],
            'date_from' => ['sometimes', 'required', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'counterparty_id' => ['sometimes', 'nullable', 'integer', 'exists:legacy_new.counterparties,id'],
            'legal_contract_id' => ['sometimes', 'nullable', 'integer', 'exists:legacy_new.contracts,id'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantId = (int) ($this->user()?->tenant_id ?? 0);
            $companyId = (int) ($this->user()?->default_company_id ?? $this->user()?->company_id ?? 0);

            $type = (string) ($this->input('type') ?? $this->route('financeObject')?->type?->value ?? $this->route('financeObject')?->type ?? '');
            if ($this->has('type')) {
                try {
                    app(FinanceObjectTypeService::class)->assertTypeEnabledForCreation(
                        $type,
                        $tenantId,
                        $companyId,
                    );
                } catch (RuntimeException $exception) {
                    $validator->errors()->add('type', $exception->getMessage());
                }
            }

            $counterpartyId = $this->input('counterparty_id', $this->route('financeObject')?->counterparty_id);
            $dateTo = $this->input('date_to', $this->route('financeObject')?->date_to?->toDateString());

            if (in_array($type, ['CONTRACT', 'ORDER', 'SUBSCRIPTION'], true) && !$counterpartyId) {
                $validator->errors()->add('counterparty_id', 'Counterparty is required for selected type.');
            }

            if (in_array($type, ['EVENT', 'SUBSCRIPTION'], true) && !$dateTo) {
                $validator->errors()->add('date_to', 'date_to is required for selected type.');
            }

            if ($this->filled('legal_contract_id')) {
                $contract = DB::connection('legacy_new')->table('contracts')
                    ->where('id', (int) $this->input('legal_contract_id'))
                    ->first(['id', 'tenant_id', 'company_id']);

                if (!$contract || (int) $contract->tenant_id !== $tenantId || (int) $contract->company_id !== $companyId) {
                    $validator->errors()->add('legal_contract_id', 'Contract must belong to current tenant/company.');
                }
            }
        });
    }
}
