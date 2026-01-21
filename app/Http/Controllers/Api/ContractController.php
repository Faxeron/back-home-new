<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContractResource;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use App\Domain\CRM\Models\ContractGroup;
use App\Domain\CRM\Models\ContractItem;
use App\Domain\CRM\Models\ContractStatusChange;
use App\Domain\Estimates\Models\Estimate;
use App\Domain\Finance\Models\PayrollSetting;
use App\Domain\Finance\Models\Spending;
use App\Domain\Finance\ValueObjects\Money;
use App\Domain\Finance\Models\FinanceAuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    private const ANALYSIS_CATEGORY_ORDER = [
        'Септик',
        'Колодец',
        'Дренажный тоннель',
        'Материалы',
        'Доставка до города',
        'Доставка до участка',
        'Спецтехника',
        'ЗП монтажник',
        'ЗП менеджер',
        'ЗП замерщик',
        'Прочее',
    ];

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 25);
        $perPage = $perPage <= 0 ? 10 : min($perPage, 200);
        $page = (int) $request->integer('page', 1);
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()
            ->with(['counterparty.individual', 'counterparty.company', 'status', 'saleType', 'manager', 'measurer'])
            ->withSum('receipts as receipts_total', 'sum')
            ->orderByDesc('id');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhereHas('counterparty', function ($counterpartyQuery) use ($search) {
                        $counterpartyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status_id'))
            $query->where('contract_status_id', $request->integer('status_id'));

        if ($request->filled('counterparty_id'))
            $query->where('counterparty_id', $request->integer('counterparty_id'));

        $contracts = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => collect($contracts->items())->map(
                fn (Contract $contract) => (new ContractResource($contract))->toArray($request),
            ),
            'meta' => [
                'current_page' => $contracts->currentPage(),
                'per_page' => $contracts->perPage(),
                'total' => $contracts->total(),
                'last_page' => $contracts->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $contract): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()
            ->with(['counterparty.individual', 'counterparty.company', 'status', 'saleType', 'manager', 'measurer'])
            ->withSum('receipts as receipts_total', 'sum')
            ->where('id', $contract);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();

        return response()->json([
            'data' => (new ContractResource($model))->toArray($request),
        ]);
    }

    public function analysis(Request $request, int $contract): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        if (!$tenantId || !$companyId) {
            return response()->json(['message' => 'Missing tenant/company context.'], 403);
        }

        $model = Contract::query()
            ->with(['items.product.kind'])
            ->where('id', $contract)
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $plannedTotals = [];
        $clientTotals = [];
        $plannedDirectTotal = 0.0;

        foreach ($model->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $qty = (float) ($item->qty ?? 0);
            $typeId = (int) ($product->product_type_id ?? 0);
            $clientCost = (float) ($item->total ?? 0);
            $cost = (float) ($product->price_zakup ?? 0) * $qty;

            $this->applyClientAmountByType(
                $clientTotals,
                $typeId,
                $product->kind?->name ?? null,
                $product->name ?? null,
                $clientCost
            );

            if ($typeId === 3) {
                $this->addAnalysisAmount($plannedTotals, 'ЗП монтажник', $cost);
                $plannedDirectTotal += $cost;
            } elseif ($typeId === 5) {
                $category = $this->mapTransportCategory($product->name ?? $product->kind?->name ?? null);
                $this->addAnalysisAmount($plannedTotals, $category, $cost);
                $plannedDirectTotal += $cost;
            } elseif ($typeId === 1) {
                $this->addAnalysisAmount($plannedTotals, 'Материалы', $cost);
                $plannedDirectTotal += $cost;
            } else {
                $category = $this->mapProductCategory($product->kind?->name ?? null);
                $this->addAnalysisAmount($plannedTotals, $category, $cost);
                $plannedDirectTotal += $cost;
            }

            $delivery = (float) ($product->price_delivery ?? 0);
            if ($typeId === 2 && $delivery > 0) {
                $deliveryCost = $delivery * $qty;
                $this->addAnalysisAmount($plannedTotals, 'Доставка до города', $deliveryCost);
                $plannedDirectTotal += $deliveryCost;
            }
        }

        if (empty($plannedTotals)) {
            $this->applyEstimateSnapshotPlan($model, $plannedTotals, $plannedDirectTotal, $clientTotals);
        }

        $settings = PayrollSetting::query()->firstOrCreate(
            ['tenant_id' => $tenantId, 'company_id' => $companyId],
            [
                'manager_fixed' => 1000,
                'manager_percent' => 7,
                'measurer_fixed' => 1000,
                'measurer_percent' => 5,
            ],
        );

        $contractTotal = (float) ($model->total_amount ?? 0);
        $margin = $contractTotal - $plannedDirectTotal;

        $managerCost = (float) $settings->manager_fixed + ($margin * ((float) $settings->manager_percent / 100));
        $measurerCost = (float) $settings->measurer_fixed + ($margin * ((float) $settings->measurer_percent / 100));

        $this->addAnalysisAmount($plannedTotals, 'ЗП менеджер', $managerCost);
        $this->addAnalysisAmount($plannedTotals, 'ЗП замерщик', $measurerCost);

        $plannedTotal = array_sum($plannedTotals);
        $clientTotal = array_sum($clientTotals);

        $actualTotals = [];
        $spendings = Spending::query()
            ->with('item')
            ->where('contract_id', $model->id)
            ->where('company_id', $companyId)
            ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->get();

        foreach ($spendings as $spending) {
            $amount = abs($this->moneyToFloat($spending->sum));
            if ($amount <= 0) {
                continue;
            }
            $category = $this->mapSpendingCategory($spending->spending_item_id, $spending->item?->name ?? null);
            $this->addAnalysisAmount($actualTotals, $category, $amount);
        }

        $rows = $this->buildAnalysisRows($plannedTotals, $actualTotals, $clientTotals);
        $actualTotal = array_sum($actualTotals);

        return response()->json([
            'data' => [
                'rows' => $rows,
                'totals' => [
                    'client' => round($clientTotal, 2),
                    'planned' => round($plannedTotal, 2),
                    'actual' => round($actualTotal, 2),
                    'delta' => round($plannedTotal - $actualTotal, 2),
                ],
                'meta' => [
                    'contract_total' => round($contractTotal, 2),
                    'margin' => round($margin, 2),
                    'settings' => [
                        'manager_fixed' => (float) $settings->manager_fixed,
                        'manager_percent' => (float) $settings->manager_percent,
                        'measurer_fixed' => (float) $settings->measurer_fixed,
                        'measurer_percent' => (float) $settings->measurer_percent,
                    ],
                ],
            ],
        ]);
    }

    public function updateStatus(Request $request, int $contract): JsonResponse
    {
        $validated = $request->validate([
            'contract_status_id' => ['required', 'integer', 'exists:legacy_new.contract_statuses,id'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contract);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();

        Gate::authorize('update', $model);

        $nextStatusId = (int) $validated['contract_status_id'];
        $previousStatusId = $model->contract_status_id ? (int) $model->contract_status_id : null;

        if ($previousStatusId !== $nextStatusId) {
            DB::connection('legacy_new')->transaction(function () use ($model, $nextStatusId, $previousStatusId, $tenantId, $companyId, $user) {
                $model->update([
                    'contract_status_id' => $nextStatusId,
                ]);

                ContractStatusChange::create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'contract_id' => $model->id,
                    'previous_status_id' => $previousStatusId,
                    'new_status_id' => $nextStatusId,
                    'changed_by' => $user?->id,
                    'changed_at' => now(),
                ]);
            });
        }

        $model->load(['counterparty', 'status', 'saleType', 'manager', 'measurer']);
        $model->loadSum('receipts as receipts_total', 'sum');

        return response()->json((new ContractResource($model))->toArray($request));
    }

    private function addAnalysisAmount(array &$totals, string $category, float $amount): void
    {
        if (abs($amount) < 0.0001) {
            return;
        }

        $totals[$category] = ($totals[$category] ?? 0) + $amount;
    }

    private function applyEstimateSnapshotPlan(
        Contract $contract,
        array &$plannedTotals,
        float &$plannedDirectTotal,
        array &$clientTotals
    ): void
    {
        $estimateId = $contract->estimate_id ?? null;
        if (!$estimateId && $contract->contract_group_id) {
            $estimateId = ContractGroup::query()
                ->where('id', $contract->contract_group_id)
                ->value('estimate_id');
        }

        if (!$estimateId) {
            return;
        }

        $estimate = Estimate::query()
            ->with(['items.product.kind'])
            ->where('id', $estimateId)
            ->first();

        if (!$estimate) {
            return;
        }

        if ($estimate->items->isNotEmpty()) {
            foreach ($estimate->items as $item) {
                $product = $item->product;
                if (!$product) {
                    continue;
                }

                $qty = (float) ($item->qty ?? 0);
                $typeId = (int) ($product->product_type_id ?? 0);
                $priceZakup = (float) ($product->price_zakup ?? 0);
                $priceDelivery = (float) ($product->price_delivery ?? 0);
                $clientCost = (float) ($item->total ?? 0);

                $this->applyPlannedCostByType(
                    $plannedTotals,
                    $plannedDirectTotal,
                    $typeId,
                    $product->kind?->name ?? null,
                    $product->name ?? null,
                    $qty,
                    $priceZakup,
                    $priceDelivery
                );

                $this->applyClientAmountByType(
                    $clientTotals,
                    $typeId,
                    $product->kind?->name ?? null,
                    $product->name ?? null,
                    $clientCost
                );
            }

            return;
        }

        $snapshot = $estimate->data ?? [];
        if (!is_array($snapshot)) {
            return;
        }

        $productIds = [];
        foreach ($snapshot as $row) {
            $productId = $row['product']['id'] ?? null;
            if ($productId) {
                $productIds[] = (int) $productId;
            }
        }

        $products = [];
        if (!empty($productIds)) {
            $products = \App\Domain\Catalog\Models\Product::query()
                ->with('kind')
                ->whereIn('id', array_unique($productIds))
                ->get()
                ->keyBy('id')
                ->all();
        }

        foreach ($snapshot as $row) {
            $productData = $row['product'] ?? [];
            $productId = $productData['id'] ?? null;
            if (!$productId) {
                continue;
            }

            $product = $products[$productId] ?? null;
            $typeId = $product ? (int) ($product->product_type_id ?? 0) : 0;
            $kindName = $product?->kind?->name ?? null;
            $qty = $this->parseSnapshotNumber($row['count'] ?? null);
            $priceZakup = $this->parseSnapshotNumber($productData['price_zakup'] ?? $productData['price_vendor'] ?? null);
            if ($priceZakup <= 0 && $product) {
                $priceZakup = (float) ($product->price_zakup ?? 0);
            }

            $priceDelivery = $this->parseSnapshotNumber($productData['price_delivery'] ?? $productData['delivery_price'] ?? null);
            if ($priceDelivery <= 0 && $product) {
                $priceDelivery = (float) ($product->price_delivery ?? 0);
            }

            $priceClient = $this->parseSnapshotNumber($productData['price_sale'] ?? $productData['price'] ?? null);
            if ($priceClient <= 0) {
                $priceClient = $this->parseSnapshotNumber($row['price'] ?? null);
            }
            $clientCost = $priceClient * $qty;

            $this->applyPlannedCostByType(
                $plannedTotals,
                $plannedDirectTotal,
                $typeId,
                $kindName,
                $productData['name'] ?? $product?->name ?? null,
                $qty,
                $priceZakup,
                $priceDelivery
            );

            $this->applyClientAmountByType(
                $clientTotals,
                $typeId,
                $kindName,
                $productData['name'] ?? $product?->name ?? null,
                $clientCost
            );
        }
    }

    private function applyClientAmountByType(
        array &$clientTotals,
        int $typeId,
        ?string $kindName,
        ?string $productName,
        float $clientCost
    ): void {
        if ($clientCost <= 0) {
            return;
        }

        if ($typeId === 3) {
            $this->addAnalysisAmount($clientTotals, 'ЗП монтажник', $clientCost);
        } elseif ($typeId === 5) {
            $category = $this->mapTransportCategory($productName ?? $kindName);
            $this->addAnalysisAmount($clientTotals, $category, $clientCost);
        } elseif ($typeId === 1) {
            $this->addAnalysisAmount($clientTotals, 'Материалы', $clientCost);
        } else {
            $category = $this->mapProductCategory($kindName);
            $this->addAnalysisAmount($clientTotals, $category, $clientCost);
        }
    }

    private function applyPlannedCostByType(
        array &$plannedTotals,
        float &$plannedDirectTotal,
        int $typeId,
        ?string $kindName,
        ?string $productName,
        float $qty,
        float $priceZakup,
        float $priceDelivery
    ): void {
        $qty = max(0, $qty);
        $priceZakup = max(0, $priceZakup);
        $priceDelivery = max(0, $priceDelivery);
        $cost = $priceZakup * $qty;

        if ($typeId === 3) {
            $this->addAnalysisAmount($plannedTotals, 'ЗП монтажник', $cost);
            $plannedDirectTotal += $cost;
        } elseif ($typeId === 5) {
            $category = $this->mapTransportCategory($productName ?? $kindName);
            $this->addAnalysisAmount($plannedTotals, $category, $cost);
            $plannedDirectTotal += $cost;
        } elseif ($typeId === 1) {
            $this->addAnalysisAmount($plannedTotals, 'Материалы', $cost);
            $plannedDirectTotal += $cost;
        } else {
            $category = $this->mapProductCategory($kindName);
            $this->addAnalysisAmount($plannedTotals, $category, $cost);
            $plannedDirectTotal += $cost;
        }

        if ($typeId === 2 && $priceDelivery > 0) {
            $deliveryCost = $priceDelivery * $qty;
            $this->addAnalysisAmount($plannedTotals, 'Доставка до города', $deliveryCost);
            $plannedDirectTotal += $deliveryCost;
        }
    }

    private function parseSnapshotNumber(mixed $value): float
    {
        if (is_null($value)) {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $normalized = str_replace(',', '.', (string) $value);
        return (float) $normalized;
    }

    private function mapProductCategory(?string $kindName): string
    {
        $name = mb_strtolower(trim((string) $kindName));

        if ($name === '') {
            return 'Прочее';
        }
        if (str_contains($name, 'септик')) {
            return 'Септик';
        }
        if (str_contains($name, 'колод')) {
            return 'Колодец';
        }
        if (str_contains($name, 'дренаж')) {
            return 'Дренажный тоннель';
        }
        if (str_contains($name, 'комплект')) {
            return 'Материалы';
        }

        return 'Прочее';
    }

    private function mapSpendingCategory(?int $itemId, ?string $itemName): string
    {
        if (in_array($itemId, [71, 78, 79, 80], true)) {
            return 'Спецтехника';
        }

        $name = mb_strtolower(trim((string) $itemName));
        if ($name === '') {
            return 'Прочее';
        }
        if (str_contains($name, 'септик')) {
            return 'Септик';
        }
        if (str_contains($name, 'колод')) {
            return 'Колодец';
        }
        if (str_contains($name, 'дренаж')) {
            return 'Дренажный тоннель';
        }
        if (str_contains($name, 'материал') || str_contains($name, 'комплект')) {
            return 'Материалы';
        }
        if (str_contains($name, 'доставка') && str_contains($name, 'город')) {
            return 'Доставка до города';
        }
        if (str_contains($name, 'доставка') && str_contains($name, 'участ')) {
            return 'Доставка до участка';
        }
        if (str_contains($name, 'замер')) {
            return 'ЗП замерщик';
        }
        if (str_contains($name, 'менедж')) {
            return 'ЗП менеджер';
        }
        if (str_contains($name, 'монтажн') || (str_contains($name, 'зп') && str_contains($name, 'монтаж'))) {
            return 'ЗП монтажник';
        }
        if (str_contains($name, 'спецтех')) {
            return 'Спецтехника';
        }

        return 'Прочее';
    }

    private function mapTransportCategory(?string $name): string
    {
        $value = mb_strtolower(trim((string) $name));
        if ($value === '') {
            return 'Доставка до участка';
        }
        if (str_contains($value, 'спецтех') || str_contains($value, 'экскаватор') || str_contains($value, 'кран')
            || str_contains($value, 'водовоз') || str_contains($value, 'грузов')) {
            return 'Спецтехника';
        }
        if (str_contains($value, 'доставка') && str_contains($value, 'город')) {
            return 'Доставка до города';
        }
        if (str_contains($value, 'доставка') && str_contains($value, 'участ')) {
            return 'Доставка до участка';
        }

        return 'Доставка до участка';
    }

    private function buildAnalysisRows(array $plannedTotals, array $actualTotals, array $clientTotals): array
    {
        $categories = array_values(array_unique(array_merge(
            array_keys($plannedTotals),
            array_keys($actualTotals),
            array_keys($clientTotals)
        )));
        usort($categories, function (string $left, string $right): int {
            $leftIndex = array_search($left, self::ANALYSIS_CATEGORY_ORDER, true);
            $rightIndex = array_search($right, self::ANALYSIS_CATEGORY_ORDER, true);
            $leftRank = $leftIndex === false ? 999 : $leftIndex;
            $rightRank = $rightIndex === false ? 999 : $rightIndex;

            if ($leftRank === $rightRank) {
                return $left <=> $right;
            }

            return $leftRank <=> $rightRank;
        });

        $rows = [];
        foreach ($categories as $category) {
            $planned = (float) ($plannedTotals[$category] ?? 0);
            $actual = (float) ($actualTotals[$category] ?? 0);
            $client = (float) ($clientTotals[$category] ?? 0);
            $rows[] = [
                'category' => $category,
                'client' => round($client, 2),
                'planned' => round($planned, 2),
                'actual' => round($actual, 2),
                'delta' => round($planned - $actual, 2),
            ];
        }

        return $rows;
    }

    private function moneyToFloat(mixed $value): float
    {
        if ($value instanceof Money) {
            return $value->toFloat();
        }
        if (is_array($value) && array_key_exists('amount', $value)) {
            return (float) $value['amount'];
        }
        return (float) $value;
    }

    public function update(Request $request, int $contract): JsonResponse
    {
        $validated = $request->validate([
            'contract_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric'],
            'city_id' => ['nullable', 'integer'],
            'sale_type_id' => ['nullable', 'integer'],
            'work_start_date' => ['nullable', 'date'],
            'work_end_date' => ['nullable', 'date'],
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contract);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();

        $model->fill([
            'contract_date' => $validated['contract_date'] ?? $model->contract_date,
            'address' => $validated['address'] ?? $model->address,
            'total_amount' => $validated['total_amount'] ?? $model->total_amount,
            'city_id' => $validated['city_id'] ?? $model->city_id,
            'sale_type_id' => $validated['sale_type_id'] ?? $model->sale_type_id,
            'work_start_date' => $validated['work_start_date'] ?? $model->work_start_date,
            'work_end_date' => $validated['work_end_date'] ?? $model->work_end_date,
            'updated_by' => $user?->id,
        ]);
        $model->save();

        $changes = $model->getChanges();
        if (!empty($changes)) {
            FinanceAuditLog::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'user_id' => $user?->id,
                'action' => 'contract.updated',
                'payload' => [
                    'contract_id' => $model->id,
                    'changes' => array_keys($changes),
                ],
                'created_at' => now(),
            ]);
        }

        $model->load(['counterparty.individual', 'counterparty.company', 'status', 'saleType', 'manager', 'measurer']);
        $model->loadSum('receipts as receipts_total', 'sum');

        return response()->json((new ContractResource($model))->toArray($request));
    }

    public function destroy(Request $request, int $contract): JsonResponse
    {
        $this->ensureAdmin($request);

        $user = $request->user();
        $tenantId = $user?->tenant_id;
        $companyId = $user?->default_company_id ?? $user?->company_id;

        $query = Contract::query()->where('id', $contract);
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $model = $query->firstOrFail();
        $groupId = $model->contract_group_id;

        DB::connection('legacy_new')->transaction(function () use ($model, $groupId, $tenantId, $companyId): void {
            $documents = ContractDocument::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->get();

            foreach ($documents as $document) {
                if ($document->file_path) {
                    Storage::disk('local')->delete($document->file_path);
                }
            }

            ContractDocument::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->delete();

            ContractItem::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->delete();

            ContractStatusChange::query()
                ->where('contract_id', $model->id)
                ->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->delete();

            $model->delete();

            if ($groupId) {
                $hasContracts = Contract::query()
                    ->where('contract_group_id', $groupId)
                    ->exists();

                if (!$hasContracts) {
                    ContractGroup::query()->where('id', $groupId)->delete();
                }
            }
        });

        return response()->json(['status' => 'ok']);
    }

    private function ensureAdmin(Request $request): void
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Only admins can delete.');
        }

        $userId = (int) $user->id;
        $db = DB::connection('legacy_new');
        $isAdmin = false;

        if (Schema::connection('legacy_new')->hasTable('role_users') && Schema::connection('legacy_new')->hasTable('roles')) {
            $isAdmin = $db->table('role_users')
                ->join('roles', 'roles.id', '=', 'role_users.role_id')
                ->where('role_users.user_id', $userId)
                ->where(function ($query) {
                    $query->where('roles.code', 'admin')
                        ->orWhere('roles.name', 'Админ')
                        ->orWhere('roles.name', 'Admin');
                })
                ->exists();
        }

        $isOwner = false;
        if (Schema::connection('legacy_new')->hasTable('user_company')) {
            $isOwner = $db->table('user_company')
                ->where('user_id', $userId)
                ->where('role', 'owner')
                ->exists();
        }

        if (!$isAdmin && !$isOwner && $userId !== 1) {
            abort(403, 'Only admins can delete.');
        }
    }
}
