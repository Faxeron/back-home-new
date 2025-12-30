<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$conn = DB::connection('legacy_new');

$tenantId = (int) (getArgValue('--tenant') ?? 0);
$companyId = (int) (getArgValue('--company') ?? 0);
$limit = (int) (getArgValue('--limit') ?? 0);
$estimateIdFilter = (int) (getArgValue('--estimate-id') ?? 0);
$overwrite = in_array('--overwrite', $argv, true);
$dryRun = in_array('--dry-run', $argv, true);
$logPath = getArgValue('--log') ?? (__DIR__ . '/../storage/logs/migrate_estimates.log');

$counts = [
    'estimates' => 0,
    'skipped' => 0,
    'items' => 0,
    'missing_products' => 0,
    'price_conflicts' => 0,
    'groups_created' => 0,
];

logLine($logPath, "Start migrate_estimates_data (dry_run=" . ($dryRun ? 'true' : 'false') . ", overwrite=" . ($overwrite ? 'true' : 'false') . ")");

$query = $conn->table('estimates')->orderBy('id');
if ($tenantId > 0) {
    $query->where('tenant_id', $tenantId);
}
if ($companyId > 0) {
    $query->where('company_id', $companyId);
}
if ($estimateIdFilter > 0) {
    $query->where('id', $estimateIdFilter);
}

$query->when($limit > 0, fn ($q) => $q->limit($limit));

$query->chunkById(100, function ($rows) use (
    $conn,
    $dryRun,
    $overwrite,
    &$counts,
    $logPath
): void {
    foreach ($rows as $estimate) {
        $counts['estimates']++;

        $existingCount = (int) $conn->table('estimate_items')->where('estimate_id', $estimate->id)->count();
        if ($existingCount > 0 && !$overwrite) {
            $counts['skipped']++;
            continue;
        }

        $data = decodeJson($estimate->data, $estimate->id, $logPath);
        if (!is_array($data)) {
            $counts['skipped']++;
            continue;
        }

        $parsedItems = parseEstimateItems($data);
        if ($parsedItems === []) {
            $counts['skipped']++;
            continue;
        }

        $productIds = [];
        $productScus = [];
        foreach ($parsedItems as $item) {
            if ($item['product_id']) {
                $productIds[] = $item['product_id'];
            } elseif ($item['scu']) {
                $productScus[] = $item['scu'];
            }
        }

        $productsById = $productIds
            ? $conn->table('products')
                ->whereIn('id', array_values(array_unique($productIds)))
                ->get()
                ->keyBy('id')
            : collect();

        $productsByScu = $productScus
            ? $conn->table('products')
                ->whereIn('scu', array_values(array_unique($productScus)))
                ->get()
                ->keyBy('scu')
            : collect();

        $aggregated = [];
        foreach ($parsedItems as $item) {
            $productId = $item['product_id'];
            $product = $productId ? ($productsById->get($productId) ?? null) : null;

            if (!$product && $item['scu']) {
                $product = $productsByScu->get($item['scu']);
                $productId = $product?->id ?? null;
            }

            if (!$productId) {
                $counts['missing_products']++;
                logLine($logPath, "Estimate {$estimate->id}: missing product for item " . json_encode($item, JSON_UNESCAPED_UNICODE));
                continue;
            }

            $unitPrice = resolveItemPrice($item, $product);

            if (!isset($aggregated[$productId])) {
                $aggregated[$productId] = [
                    'product' => $product,
                    'qty' => 0.0,
                    'price' => $unitPrice,
                ];
            } else {
                if (abs($aggregated[$productId]['price'] - $unitPrice) > 0.0001) {
                    $counts['price_conflicts']++;
                    $aggregated[$productId]['price'] = resolveProductPrice($product);
                }
            }

            $aggregated[$productId]['qty'] += $item['qty'];
        }

        if ($dryRun) {
            $counts['items'] += count($aggregated);
            continue;
        }

        $conn->transaction(function () use ($conn, $estimate, $aggregated, &$counts, $logPath): void {
            $conn->table('estimate_items')->where('estimate_id', $estimate->id)->delete();

            foreach ($aggregated as $productId => $row) {
                $product = $row['product'];
                $groupId = resolveGroupId(
                    $conn,
                    (int) $estimate->tenant_id,
                    (int) $estimate->company_id,
                    $product?->product_type_id,
                    $logPath,
                    $counts
                );

                $qty = $row['qty'];
                $price = $row['price'];
                $conn->table('estimate_items')->insert([
                    'tenant_id' => $estimate->tenant_id,
                    'company_id' => $estimate->company_id,
                    'estimate_id' => $estimate->id,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'qty_auto' => 0,
                    'qty_manual' => $qty,
                    'price' => $price,
                    'total' => $qty * $price,
                    'group_id' => $groupId,
                    'sort_order' => $product?->sort_order ?? 100,
                    'created_at' => $estimate->created_at,
                    'updated_at' => $estimate->updated_at,
                ]);
                $counts['items']++;
            }
        });
    }
});

echo "Done.\n";
foreach ($counts as $key => $value) {
    echo "{$key}: {$value}\n";
}
echo "Log: {$logPath}\n";

function parseEstimateItems(array $data): array
{
    $items = [];

    foreach ($data as $row) {
        if (!is_array($row)) {
            continue;
        }

        $product = is_array($row['product'] ?? null) ? $row['product'] : [];
        $qty = normalizeNumber($row['count'] ?? $row['qty'] ?? 0);
        if ($qty === null || $qty === 0.0) {
            continue;
        }

        $items[] = [
            'product_id' => normalizeInt($product['id'] ?? $row['product_id'] ?? null),
            'scu' => normalizeText($product['scu'] ?? $row['scu'] ?? ''),
            'qty' => $qty,
            'price' => normalizeNumber($row['price'] ?? null),
            'price_type' => $row['price_type'] ?? null,
            'product_price' => normalizeNumber($product['price'] ?? null),
            'product_price_sale' => normalizeNumber($product['price_sale'] ?? null),
        ];
    }

    return $items;
}

function resolveItemPrice(array $item, $product): float
{
    if ($item['price'] !== null) {
        return (float) $item['price'];
    }

    $priceType = $item['price_type'];
    $priceSale = $item['product_price_sale'] ?? null;
    $price = $item['product_price'] ?? null;

    if ($priceType && $priceSale !== null) {
        return (float) $priceSale;
    }

    if ($price !== null) {
        return (float) $price;
    }

    if ($priceSale !== null) {
        return (float) $priceSale;
    }

    return resolveProductPrice($product);
}

function resolveProductPrice($product): float
{
    if (!$product) {
        return 0.0;
    }

    if ($product->price_sale !== null) {
        return (float) $product->price_sale;
    }

    if ($product->price !== null) {
        return (float) $product->price;
    }

    return 0.0;
}

function resolveGroupId($conn, int $tenantId, int $companyId, $productTypeId, string $logPath, array &$counts): ?int
{
    if (!$productTypeId) {
        return null;
    }

    $query = $conn->table('estimate_groups')->where('product_type_id', $productTypeId);
    if ($tenantId > 0) {
        $query->where('tenant_id', $tenantId);
    }
    if ($companyId > 0) {
        $query->where('company_id', $companyId);
    }

    $groupId = $query->value('id');
    if ($groupId) {
        return (int) $groupId;
    }

    $name = $conn->table('product_types')->where('id', $productTypeId)->value('name');
    if ($name) {
        $nameQuery = $conn->table('estimate_groups')->where('name', $name);
        if ($tenantId > 0) {
            $nameQuery->where('tenant_id', $tenantId);
        }
        if ($companyId > 0) {
            $nameQuery->where('company_id', $companyId);
        }
        $existingByName = $nameQuery->value('id');
        if ($existingByName) {
            $conn->table('estimate_groups')
                ->where('id', $existingByName)
                ->update(['product_type_id' => $productTypeId, 'updated_at' => now()]);
            return (int) $existingByName;
        }
    }
    $groupId = (int) $conn->table('estimate_groups')->insertGetId([
        'tenant_id' => $tenantId ?: null,
        'company_id' => $companyId ?: null,
        'product_type_id' => $productTypeId,
        'sort_order' => 100,
        'name' => $name ?: "Type {$productTypeId}",
        'ids' => '[]',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $counts['groups_created']++;
    logLine($logPath, "Created estimate_group id={$groupId} product_type_id={$productTypeId}");

    return $groupId;
}

function decodeJson(?string $data, int $estimateId, string $logPath): mixed
{
    if (!$data) {
        return null;
    }

    try {
        return json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        logLine($logPath, "Estimate {$estimateId}: invalid JSON ({$e->getMessage()})");
        return null;
    }
}

function normalizeText(?string $value): string
{
    if ($value === null) {
        return '';
    }
    $value = preg_replace('/\\s+/u', ' ', trim($value));
    return $value ?? '';
}

function normalizeInt($value): ?int
{
    if ($value === null) {
        return null;
    }
    if (is_numeric($value)) {
        return (int) $value;
    }
    return null;
}

function normalizeNumber($value): ?float
{
    if ($value === null) {
        return null;
    }
    if (is_string($value)) {
        $value = str_replace(',', '.', normalizeText($value));
    }
    if (!is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

function getArgValue(string $key): ?string
{
    global $argv;
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $key . '=')) {
            return substr($arg, strlen($key) + 1);
        }
    }
    return null;
}

function logLine(string $path, string $message): void
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, '[' . date('Y-m-d H:i:s') . "] {$message}" . PHP_EOL, FILE_APPEND);
}
