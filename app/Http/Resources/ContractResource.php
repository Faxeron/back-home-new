<?php

namespace App\Http\Resources;

use App\Domain\Finance\Enums\FinanceObjectStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\CRM\Models\Contract
 */
class ContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $total = (float) ($this->total_amount ?? 0);
        $paidValue = $this->receipts_total ?? $this->paid_amount ?? 0;
        $paid = (float) $paidValue;
        $debt = $total - $paid;

        return [
            'id' => $this->id,
            'finance_object_id' => $this->finance_object_id,
            'counterparty_id' => $this->counterparty_id,
            'counterparty' => $this->whenLoaded('counterparty', fn () => $this->counterparty ? [
                'id' => $this->counterparty->id,
                'name' => $this->counterparty->name,
                'phone' => $this->counterparty->phone,
                'email' => $this->counterparty->email,
                'type' => $this->counterparty->type,
                'individual' => $this->counterparty->relationLoaded('individual') && $this->counterparty->individual ? [
                    'first_name' => $this->counterparty->individual->first_name,
                    'last_name' => $this->counterparty->individual->last_name,
                    'patronymic' => $this->counterparty->individual->patronymic,
                    'passport_series' => $this->counterparty->individual->passport_series,
                    'passport_number' => $this->counterparty->individual->passport_number,
                    'passport_code' => $this->counterparty->individual->passport_code,
                    'passport_whom' => $this->counterparty->individual->passport_whom,
                    'issued_at' => $this->counterparty->individual->issued_at?->toDateString(),
                    'passport_address' => $this->counterparty->individual->passport_address,
                ] : null,
                'company' => $this->counterparty->relationLoaded('company') && $this->counterparty->company ? [
                    'legal_name' => $this->counterparty->company->legal_name,
                    'short_name' => $this->counterparty->company->short_name,
                    'inn' => $this->counterparty->company->inn,
                    'kpp' => $this->counterparty->company->kpp,
                    'ogrn' => $this->counterparty->company->ogrn,
                    'legal_address' => $this->counterparty->company->legal_address,
                    'postal_address' => $this->counterparty->company->postal_address,
                    'director_name' => $this->counterparty->company->director_name,
                    'accountant_name' => $this->counterparty->company->accountant_name,
                    'bank_name' => $this->counterparty->company->bank_name,
                    'bik' => $this->counterparty->company->bik,
                    'account_number' => $this->counterparty->company->account_number,
                    'correspondent_account' => $this->counterparty->company->correspondent_account,
                ] : null,
            ] : null),
            'address' => $this->address,
            'title' => $this->title,
            'contract_date' => $this->contract_date?->toDateString(),
            'city_id' => $this->city_id,
            'sale_type_id' => $this->sale_type_id,
            'sale_type' => $this->whenLoaded('saleType', fn () => $this->saleType ? [
                'id' => $this->saleType->id,
                'name' => $this->saleType->name,
            ] : null),
            'total_amount' => $total,
            'paid_amount' => $paid,
            'debt' => $debt,
            'system_status_code' => $this->system_status_code,
            'contract_status_id' => $this->contract_status_id,
            'status' => $this->whenLoaded('status', fn () => $this->status ? [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'color' => $this->status->color,
            ] : null),
            'manager_id' => $this->manager_id,
            'manager' => $this->whenLoaded('manager', fn () => $this->manager ? [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
            ] : null),
            'measurer_id' => $this->measurer_id,
            'measurer' => $this->whenLoaded('measurer', fn () => $this->measurer ? [
                'id' => $this->measurer->id,
                'name' => $this->measurer->name,
            ] : null),
            'work_start_date' => $this->work_start_date?->toDateString(),
            'work_end_date' => $this->work_end_date?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'finance_object' => $this->whenLoaded('financeObject', fn () => [
                'id' => $this->financeObject->id,
                'type' => $this->financeObject->type?->value ?? $this->financeObject->type,
                'name' => $this->financeObject->name,
                'status' => $this->financeObject->status?->value ?? $this->financeObject->status,
                'status_name_ru' => $this->financeObject->status instanceof FinanceObjectStatus
                    ? $this->financeObject->status->labelRu()
                    : null,
                'code' => $this->financeObject->code,
            ]),
        ];
    }
}
