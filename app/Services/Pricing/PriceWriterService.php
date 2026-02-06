<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductCompanyPrice;
use App\Services\Pricing\DTO\PriceDTO;
use Illuminate\Support\Arr;

final class PriceWriterService
{
    /**
     * @param array<string, mixed> $fields
     */
    public function upsertPrices(
        int $tenantId,
        int $companyId,
        int $productId,
        array $fields,
        ?int $userId = null,
        bool $syncLegacy = false,
    ): PriceDTO {
        $allowed = [
            'price',
            'price_sale',
            'price_delivery',
            'montaj',
            'montaj_sebest',
            'currency',
            'is_active',
        ];

        $payload = Arr::only($fields, $allowed);
        $now = now();

        if ($userId) {
            $payload['updated_by'] = $userId;
        }
        $payload['updated_at'] = $now;

        $priceRow = ProductCompanyPrice::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'product_id' => $productId,
            ],
            array_merge($payload, [
                'created_at' => $now,
                'created_by' => $userId,
            ])
        );

        if ($syncLegacy) {
            $legacy = Arr::only($payload, [
                'price',
                'price_sale',
                'price_delivery',
                'montaj',
                'montaj_sebest',
            ]);
            if (!empty($legacy)) {
                if ($userId) {
                    $legacy['updated_by'] = $userId;
                }
                $legacy['updated_at'] = $now;

                Product::query()
                    ->where('id', $productId)
                    ->update($legacy);
            }
        }

        return new PriceDTO(
            price: $priceRow->price !== null ? (float) $priceRow->price : null,
            price_sale: $priceRow->price_sale !== null ? (float) $priceRow->price_sale : null,
            price_delivery: $priceRow->price_delivery !== null ? (float) $priceRow->price_delivery : null,
            montaj: $priceRow->montaj !== null ? (float) $priceRow->montaj : null,
            montaj_sebest: $priceRow->montaj_sebest !== null ? (float) $priceRow->montaj_sebest : null,
            currency: $priceRow->currency ?? 'RUB',
            is_active: (bool) ($priceRow->is_active ?? true),
            is_fallback: false,
        );
    }
}
