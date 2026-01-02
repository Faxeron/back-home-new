<?php

namespace App\Services\Estimates;

use App\Domain\Catalog\Models\Product;
use App\Domain\Estimates\Models\Estimate;
use App\Domain\Estimates\Models\EstimateGroup;
use App\Domain\Estimates\Models\EstimateItem;
use App\Domain\Estimates\Models\EstimateItemSource;
use App\Domain\Estimates\Models\EstimateTemplateMaterial;
use App\Domain\Estimates\Models\EstimateTemplateSeptik;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EstimateTemplateService
{
    public function applyTemplateBySku(Estimate $estimate, string $rootSku, float $rootQty): void
    {
        $rootQty = max(0.0, $rootQty);

        $rootProduct = Product::query()
            ->where('scu', $rootSku)
            ->when($estimate->tenant_id, fn($query) => $query->where('tenant_id', $estimate->tenant_id))
            ->when($estimate->company_id, fn($query) => $query->where('company_id', $estimate->company_id))
            ->first();

        if (!$rootProduct) {
            return;
        }

        $septikTemplate = EstimateTemplateSeptik::query()
            ->whereJsonContains('data', $rootSku)
            ->first();

        if (!$septikTemplate) {
            $septikTemplate = EstimateTemplateSeptik::all()
                ->first(fn(EstimateTemplateSeptik $row) => in_array($rootSku, $row->data ?? [], true));
        }

        if (!$septikTemplate) {
            return;
        }

        $templateIds = $this->parseTemplateIds($septikTemplate->pattern_ids ?? null);
        if ($templateIds === []) {
            return;
        }

        $materialTemplates = EstimateTemplateMaterial::query()
            ->whereIn('id', $templateIds)
            ->get()
            ->keyBy('id');

        if ($materialTemplates->isEmpty()) {
            return;
        }

        $templatesMap = [];
        $allItems = [];
        foreach ($templateIds as $templateId) {
            $materialTemplate = $materialTemplates->get($templateId);
            if (!$materialTemplate) {
                continue;
            }
            $templateItems = $this->normalizeTemplateItems($materialTemplate->data ?? []);
            if ($templateItems === []) {
                continue;
            }
            $templatesMap[$templateId] = $templateItems;
            foreach ($templateItems as $scu => $qty) {
                $allItems[$scu] = true;
            }
        }

        if ($templatesMap === []) {
            return;
        }

        $products = Product::query()
            ->whereIn('scu', array_keys($allItems))
            ->when($estimate->tenant_id, fn($query) => $query->where('tenant_id', $estimate->tenant_id))
            ->when($estimate->company_id, fn($query) => $query->where('company_id', $estimate->company_id))
            ->get()
            ->keyBy('scu');

        DB::connection('legacy_new')->transaction(function () use (
            $estimate,
            $rootProduct,
            $rootQty,
            $templatesMap,
            $products
        ): void {
            $affectedProductIds = [];

            foreach ($templatesMap as $templateId => $templateItems) {
                foreach ($templateItems as $scu => $qtyPerUnit) {
                    $product = $products->get($scu);
                    if (!$product) {
                        continue;
                    }

                    $qtyTotal = $qtyPerUnit * $rootQty;

                    EstimateItemSource::query()->updateOrCreate(
                        [
                            'estimate_id' => $estimate->id,
                            'product_id' => $product->id,
                            'origin_product_id' => $rootProduct->id,
                            'template_id' => $templateId,
                        ],
                        [
                            'tenant_id' => $estimate->tenant_id,
                            'company_id' => $estimate->company_id,
                            'qty_per_unit' => $qtyPerUnit,
                            'root_qty' => $rootQty,
                            'qty_total' => $qtyTotal,
                        ]
                    );

                    $affectedProductIds[] = $product->id;
                }
            }

            $this->refreshEstimateItems($estimate, array_unique($affectedProductIds), $products);
        });
    }

    public function updateManualQty(EstimateItem $item, float $newQty): EstimateItem
    {
        $qtyAuto = (float) ($item->qty_auto ?? 0);
        $item->qty_manual = $newQty - $qtyAuto;
        $item->qty = $newQty;
        $item->total = $item->qty * (float) ($item->price ?? 0);
        $item->save();

        return $item;
    }

    public function createManualItem(Estimate $estimate, Product $product, float $qty, ?float $price = null): EstimateItem
    {
        $item = EstimateItem::query()
            ->where('estimate_id', $estimate->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$item) {
            $item = new EstimateItem([
                'estimate_id' => $estimate->id,
                'product_id' => $product->id,
            ]);
            $item->tenant_id = $estimate->tenant_id;
            $item->company_id = $estimate->company_id;
            $item->qty_auto = 0;
            $item->qty_manual = 0;
        }

        $item->qty_manual = (float) ($item->qty_manual ?? 0) + $qty;
        $item->qty_auto = (float) ($item->qty_auto ?? 0);
        $item->qty = $item->qty_manual + $item->qty_auto;

        if ($price !== null) {
            $item->price = $price;
        } elseif ($item->price === null) {
            $item->price = $this->resolvePrice($product);
        }

        $item->total = $item->qty * (float) ($item->price ?? 0);
        $item->group_id = $this->resolveGroupId($product->product_type_id, $estimate);
        $item->sort_order = $product->sort_order ?? 100;
        $item->save();

        return $item;
    }

    private function refreshEstimateItems(Estimate $estimate, array $productIds, Collection $productsByScu): void
    {
        if ($productIds === []) {
            return;
        }

        $autoTotals = EstimateItemSource::query()
            ->where('estimate_id', $estimate->id)
            ->whereIn('product_id', $productIds)
            ->selectRaw('product_id, SUM(qty_total) as qty_auto')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $productsById = $productsByScu->keyBy('id');

        foreach ($productIds as $productId) {
            $product = $productsById->get($productId);
            if (!$product) {
                continue;
            }

            $qtyAuto = (float) ($autoTotals->get($productId)->qty_auto ?? 0);

            $items = EstimateItem::query()
                ->where('estimate_id', $estimate->id)
                ->where('product_id', $productId)
                ->orderBy('id')
                ->get();

            $item = $items->first() ?? new EstimateItem([
                'estimate_id' => $estimate->id,
                'product_id' => $productId,
            ]);

            if (!$item->exists) {
                $item->tenant_id = $estimate->tenant_id;
                $item->company_id = $estimate->company_id;
                $item->qty_manual = 0;
            }

            if ($items->count() > 1) {
                $item->qty_manual = $items->sum('qty_manual');
                $item->save();
                $items->slice(1)->each->delete();
            }

            $item->qty_auto = $qtyAuto;
            $item->qty_manual = (float) ($item->qty_manual ?? 0);
            $item->qty = $item->qty_auto + $item->qty_manual;
            $item->price = $this->resolvePrice($product);
            $item->total = $item->qty * $item->price;
            $item->group_id = $this->resolveGroupId($product->product_type_id, $estimate);
            $item->sort_order = $product->sort_order ?? 100;
            $item->save();
        }
    }

    private function resolveGroupId(?int $productTypeId, Estimate $estimate): ?int
    {
        if (!$productTypeId) {
            return null;
        }

        $query = EstimateGroup::query()->where('product_type_id', $productTypeId);
        if ($estimate->tenant_id) {
            $query->where('tenant_id', $estimate->tenant_id);
        }
        if ($estimate->company_id) {
            $query->where('company_id', $estimate->company_id);
        }

        $group = $query->first();
        if ($group) {
            return $group->id;
        }

        $name = DB::connection('legacy_new')->table('product_types')
            ->where('id', $productTypeId)
            ->value('name');

        if ($name) {
            $nameQuery = EstimateGroup::query()->where('name', $name);
            if ($estimate->tenant_id) {
                $nameQuery->where('tenant_id', $estimate->tenant_id);
            }
            if ($estimate->company_id) {
                $nameQuery->where('company_id', $estimate->company_id);
            }
            $existingByName = $nameQuery->first();
            if ($existingByName) {
                $existingByName->product_type_id = $productTypeId;
                $existingByName->save();
                return $existingByName->id;
            }
        }

        $group = EstimateGroup::query()->create([
            'tenant_id' => $estimate->tenant_id,
            'company_id' => $estimate->company_id,
            'product_type_id' => $productTypeId,
            'sort_order' => 100,
            'name' => $name ?: "Type {$productTypeId}",
            'ids' => '[]',
        ]);

        return $group->id;
    }

    private function resolvePrice(Product $product): float
    {
        if ($product->price_sale !== null) {
            return (float) $product->price_sale;
        }

        if ($product->price !== null) {
            return (float) $product->price;
        }

        return 0.0;
    }

    private function normalizeTemplateItems(array $data): array
    {
        $items = [];

        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $scu = $row['scu'] ?? null;
            if (!$scu) {
                continue;
            }

            $count = $row['count'] ?? 0;
            if (!is_numeric($count)) {
                continue;
            }

            $qty = (float) $count;
            if ($qty === 0.0) {
                continue;
            }

            $items[$scu] = ($items[$scu] ?? 0) + $qty;
        }

        return $items;
    }

    private function parseTemplateIds(?string $patternIds): array
    {
        if (!$patternIds) {
            return [];
        }

        if (is_numeric($patternIds)) {
            return [(int) $patternIds];
        }

        $decoded = json_decode($patternIds, true);
        if (is_array($decoded)) {
            return array_values(array_unique(array_map('intval', $decoded)));
        }

        preg_match_all('/\d+/', $patternIds, $matches);
        if (!empty($matches[0])) {
            return array_values(array_unique(array_map('intval', $matches[0])));
        }

        return [];
    }
}
