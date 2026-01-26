<?php

namespace App\Http\Controllers\Api\Estimates;

use App\Domain\Catalog\Models\ProductType;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use App\Domain\CRM\Models\ContractGroup;
use App\Domain\CRM\Models\ContractItem;
use App\Domain\CRM\Models\ContractStatus;
use App\Domain\CRM\Models\ContractTemplate;
use App\Domain\CRM\Models\Counterparty;
use App\Domain\CRM\Models\CounterpartyCompany;
use App\Domain\CRM\Models\CounterpartyIndividual;
use App\Domain\Estimates\Models\Estimate;
use App\Domain\Finance\Models\FinanceAuditLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\StoreEstimateContractsRequest;
use App\Http\Resources\ContractResource;
use App\Services\Finance\PayrollService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class EstimateContractController extends Controller
{
    public function store(StoreEstimateContractsRequest $request, int $estimate): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$companyId) {
            return response()->json(['message' => 'Missing company context.'], 403);
        }

        $estimateModel = Estimate::query()
            ->with(['items.product', 'items.group'])
            ->where('id', $estimate)
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('company_id', $companyId)
            ->firstOrFail();

        $existingContract = Contract::query()
            ->where('estimate_id', $estimateModel->id)
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('company_id', $companyId)
            ->first();

        if ($existingContract) {
            return response()->json([
                'message' => 'Договор по этой смете уже создан.',
                'contract_id' => $existingContract->id,
            ], 409);
        }

        $data = $request->validated();
        $templateIds = array_values(array_unique(array_map('intval', $data['template_ids'])));

        $templates = ContractTemplate::query()
            ->with('productTypes')
            ->whereIn('id', $templateIds)
            ->where('company_id', $companyId)
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->get()
            ->keyBy('id');

        if ($templates->count() !== count($templateIds)) {
            return response()->json([
                'message' => 'Шаблон договора не найден.',
            ], 422);
        }

        $estimateTypeIds = $estimateModel->items
            ->pluck('product.product_type_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $coveredTypeIds = $templates->flatMap(function (ContractTemplate $template) {
            return $template->productTypes->pluck('id');
        })->unique()->values()->all();

        $missingTypeIds = array_values(array_diff($estimateTypeIds, $coveredTypeIds));
        if (!empty($missingTypeIds) && !$request->boolean('allow_uncovered')) {
            $missingTypes = ProductType::query()
                ->whereIn('id', $missingTypeIds)
                ->orderBy('id')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'message' => 'Не все типы товаров покрыты выбранными шаблонами.',
                'missing_product_types' => $missingTypes,
            ], 422);
        }

        $counterpartyPayload = $data['counterparty'] ?? [];
        $contractPayload = $data['contract'] ?? [];
        $counterpartyType = $data['counterparty_type'] === 'legal'
            ? 'company'
            : $data['counterparty_type'];

        $result = DB::connection('legacy_new')->transaction(function () use (
            $estimateModel,
            $templates,
            $templateIds,
            $counterpartyPayload,
            $contractPayload,
            $counterpartyType,
            $data,
            $estimateTypeIds,
            $tenantId,
            $companyId,
            $user
        ) {
            $counterparty = $this->upsertCounterparty(
                $estimateModel,
                $counterpartyType,
                $counterpartyPayload,
                $tenantId,
                $companyId,
                $user?->id
            );

            $draftStatusId = ContractStatus::query()
                ->where('code', 'DRAFT')
                ->value('id');

            $estimateModel->fill([
                'client_id' => $counterparty->id,
                'client_name' => $counterparty->name,
                'client_phone' => $counterparty->phone,
                'site_address' => $contractPayload['site_address'] ?? $estimateModel->site_address,
                'updated_by' => $user?->id,
            ]);
            $estimateModel->save();

            $group = ContractGroup::query()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'estimate_id' => $estimateModel->id,
                'counterparty_id' => $counterparty->id,
                'counterparty_type' => $counterpartyType,
                'contract_date' => $contractPayload['contract_date'] ?? null,
                'city_id' => $contractPayload['city_id'] ?? null,
                'site_address' => $contractPayload['site_address'] ?? null,
                'sale_type_id' => $contractPayload['sale_type_id'] ?? null,
                'installation_date' => $contractPayload['work_start_date'] ?? $contractPayload['installation_date'] ?? null,
                'total_amount' => null,
                'contract_status_id' => $draftStatusId,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            $estimateItems = $estimateModel->items;
            $estimateTotal = $estimateItems->sum(fn ($item) => (float) ($item->total ?? 0));

            if (isset($contractPayload['total_amount']) && is_numeric($contractPayload['total_amount'])) {
                $estimateTotal = (float) $contractPayload['total_amount'];
            }

            $templateTitles = $templates->map(function (ContractTemplate $template) {
                return $template->short_name ?: $template->name;
            })->filter()->unique()->values();

            $contractTitle = $templateTitles->isNotEmpty()
                ? $templateTitles->implode(' + ')
                : 'Сделка по смете #' . $estimateModel->id;

            $contract = Contract::query()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'counterparty_id' => $counterparty->id,
                'contract_status_id' => $draftStatusId,
                'title' => $contractTitle,
                'total_amount' => $estimateTotal,
                'contract_date' => $contractPayload['contract_date'] ?? null,
                'sale_type_id' => $contractPayload['sale_type_id'] ?? null,
                'city_id' => $contractPayload['city_id'] ?? null,
                'address' => $contractPayload['site_address'] ?? null,
                'work_start_date' => $contractPayload['work_start_date'] ?? $contractPayload['installation_date'] ?? null,
                'work_end_date' => $contractPayload['work_end_date'] ?? null,
                'contract_group_id' => $group->id,
                'template_product_type_ids' => $estimateTypeIds,
                'estimate_id' => $estimateModel->id,
                'manager_id' => $user?->id,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]);

            foreach ($estimateItems as $item) {
                $product = $item->product;
                ContractItem::query()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'contract_id' => $contract->id,
                    'estimate_item_id' => $item->id,
                    'product_id' => $product?->id,
                    'product_type_id' => $product?->product_type_id,
                    'unit_id' => $product?->unit_id,
                    'scu' => $product?->scu,
                    'name' => $product?->name ?? $item->name ?? 'Без названия',
                    'qty' => $item->qty ?? 0,
                    'price' => $item->price ?? 0,
                    'total' => $item->total ?? 0,
                    'sort_order' => $item->sort_order ?? 100,
                    'group_name' => $item->group?->name,
                    'created_by' => $user?->id,
                    'updated_by' => $user?->id,
                ]);
            }

            foreach ($templateIds as $templateId) {
                /** @var ContractTemplate|null $template */
                $template = $templates->get($templateId);
                if (!$template) {
                    continue;
                }

                $documentType = $template->document_type ?? 'combined';
                $numberSuffix = $this->resolveNumberSuffix($documentType);

                ContractDocument::query()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'contract_id' => $contract->id,
                    'template_id' => $template->id,
                    'document_type' => $documentType,
                    'number_suffix' => $numberSuffix,
                    'file_path' => null,
                    'version' => 0,
                    'is_current' => false,
                    'generated_at' => null,
                    'created_by' => $user?->id,
                ]);
            }

            app(PayrollService::class)->accrueFixedForContract($contract, $user?->id);

            $group->total_amount = $estimateTotal;
            $group->save();

            FinanceAuditLog::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'user_id' => $user?->id,
                'action' => 'contract.created',
                'payload' => [
                    'contract_id' => $contract->id,
                    'status_id' => $draftStatusId,
                ],
                'created_at' => now(),
            ]);

            return [$group, [$contract]];
        });

        [$group, $contracts] = $result;

        return response()->json([
            'data' => [
                'contract_group_id' => $group->id,
                'contracts' => collect($contracts)->map(
                    fn (Contract $contract) => (new ContractResource($contract))->toArray($request),
                ),
            ],
        ]);
    }

    private function resolveNumberSuffix(string $documentType): string
    {
        return match ($documentType) {
            'supply' => '-п',
            'install' => '-м',
            default => '',
        };
    }

    private function upsertCounterparty(
        Estimate $estimate,
        string $type,
        array $payload,
        ?int $tenantId,
        int $companyId,
        ?int $userId
    ): Counterparty {
        $counterparty = null;
        if ($estimate->client_id) {
            $counterparty = Counterparty::query()
                ->where('id', $estimate->client_id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->where('company_id', $companyId)
                ->first();
        }

        if (!$counterparty) {
            $counterparty = Counterparty::query()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'type' => $type,
                'name' => $payload['name'] ?? '',
                'phone' => $payload['phone'] ?? null,
                'email' => $payload['email'] ?? null,
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);
        } else {
            $counterparty->fill([
                'type' => $type,
                'name' => $payload['name'] ?? $counterparty->name,
                'phone' => $payload['phone'] ?? $counterparty->phone,
                'email' => $payload['email'] ?? $counterparty->email,
                'updated_by' => $userId,
            ]);
            $counterparty->save();
        }

        if ($type === 'individual') {
            $fullName = trim(implode(' ', array_filter([
                $payload['last_name'] ?? null,
                $payload['first_name'] ?? null,
                $payload['patronymic'] ?? null,
            ])));
            if ($fullName !== '') {
                $counterparty->name = $fullName;
                $counterparty->save();
            }

            CounterpartyIndividual::query()->updateOrCreate(
                [
                    'counterparty_id' => $counterparty->id,
                ],
                [
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'first_name' => $payload['first_name'] ?? '',
                    'last_name' => $payload['last_name'] ?? '',
                    'patronymic' => $payload['patronymic'] ?? null,
                    'passport_series' => $payload['passport_series'] ?? '',
                    'passport_number' => $payload['passport_number'] ?? '',
                    'passport_code' => $payload['passport_code'] ?? null,
                    'passport_whom' => $payload['passport_whom'] ?? null,
                    'issued_at' => $payload['issued_at'] ?? null,
                    'issued_by' => $payload['issued_by'] ?? ($payload['passport_whom'] ?? null),
                    'updated_by' => $userId,
                    'created_by' => $userId,
                ]
            );
        }

        if ($type === 'company') {
            $counterpartyName = $payload['short_name'] ?? $payload['legal_name'] ?? $counterparty->name;
            if ($counterpartyName) {
                $counterparty->name = $counterpartyName;
                $counterparty->save();
            }

            CounterpartyCompany::query()->updateOrCreate(
                [
                    'counterparty_id' => $counterparty->id,
                ],
                [
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'legal_name' => $payload['legal_name'] ?? '',
                    'short_name' => $payload['short_name'] ?? null,
                    'inn' => $payload['inn'] ?? '',
                    'kpp' => $payload['kpp'] ?? null,
                    'ogrn' => $payload['ogrn'] ?? '',
                    'legal_address' => $payload['legal_address'] ?? '',
                    'postal_address' => $payload['postal_address'] ?? null,
                    'director_name' => $payload['director_name'] ?? null,
                    'accountant_name' => $payload['accountant_name'] ?? null,
                    'bank_name' => $payload['bank_name'] ?? '',
                    'bik' => $payload['bik'] ?? '',
                    'account_number' => $payload['account_number'] ?? '',
                    'correspondent_account' => $payload['correspondent_account'] ?? '',
                    'updated_by' => $userId,
                    'created_by' => $userId,
                ]
            );
        }

        return $counterparty;
    }
}
