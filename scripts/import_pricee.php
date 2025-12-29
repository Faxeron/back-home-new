<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tenantId = 1;
$companyId = 1;
$filePath = __DIR__ . '/../pricee.xlsx';
$insertOnly = in_array('--insert-only', $argv, true);
$logPath = __DIR__ . '/../storage/logs/import_pricee.log';
$logEntries = [];

if (!file_exists($filePath)) {
    fwrite(STDERR, "File not found: {$filePath}\n");
    exit(1);
}

$conn = DB::connection('legacy_new');
$now = now();

$categoryMap = $conn->table('product_categories')
    ->where('tenant_id', $tenantId)
    ->pluck('id', 'name')
    ->mapWithKeys(fn ($id, $name) => [normalizeText((string) $name) => (int) $id])
    ->all();

$brandMap = $conn->table('product_brands')
    ->where('tenant_id', $tenantId)
    ->pluck('id', 'name')
    ->mapWithKeys(fn ($id, $name) => [normalizeText((string) $name) => (int) $id])
    ->all();

$subCategoryRows = $conn->table('product_subcategories')
    ->leftJoin('product_categories', 'product_categories.id', '=', 'product_subcategories.category_id')
    ->where('product_subcategories.tenant_id', $tenantId)
    ->select([
        'product_subcategories.id',
        'product_subcategories.name as sub_name',
        'product_categories.name as cat_name',
    ])
    ->get();

$subCategoryByPair = [];
$subCategoryByName = [];
foreach ($subCategoryRows as $row) {
    $sub = normalizeText((string) $row->sub_name);
    $cat = normalizeText((string) $row->cat_name);
    if ($sub !== '' && $cat !== '') {
        $subCategoryByPair["{$cat}|{$sub}"] = (int) $row->id;
    }
    if ($sub !== '' && !isset($subCategoryByName[$sub])) {
        $subCategoryByName[$sub] = (int) $row->id;
    }
}

$unitMap = $conn->table('product_units')
    ->pluck('id', 'name')
    ->mapWithKeys(fn ($id, $name) => [normalizeText((string) $name) => (int) $id])
    ->all();

$kindIds = $conn->table('product_kinds')
    ->where('tenant_id', $tenantId)
    ->pluck('id')
    ->map(fn ($id) => (int) $id)
    ->all();
$kindIdSet = array_fill_keys($kindIds, true);

$productIdBySku = $conn->table('products')
    ->where('tenant_id', $tenantId)
    ->pluck('id', 'scu')
    ->all();

$attributeDefs = $conn->table('product_attribute_definitions')
    ->where('tenant_id', $tenantId)
    ->select(['id', 'name', 'value_type'])
    ->get()
    ->mapWithKeys(fn ($row) => [normalizeText((string) $row->name) => [
        'id' => (int) $row->id,
        'value_type' => (string) $row->value_type,
    ]])
    ->all();

[$headers, $rows] = readSheet($filePath);
if (!$headers) {
    fwrite(STDERR, "No headers found in file.\n");
    exit(1);
}

$headerMap = buildHeaderMap($headers);
$attributePairs = buildAttributePairs($headerMap);

$pendingRelated = [];
$missingRelated = [];
$counts = [
    'main_inserted' => 0,
    'main_updated' => 0,
    'work_inserted' => 0,
    'work_updated' => 0,
    'relations' => 0,
    'related' => 0,
    'media' => 0,
    'attributes' => 0,
    'skipped' => 0,
];

logLine($logEntries, "Import start. insert_only=" . ($insertOnly ? 'true' : 'false'));

foreach ($rows as $row) {
    $mainScu = normalizeText(getCell($row, $headerMap, 'scu', 0));
    if ($mainScu === '') {
        continue;
    }

    $mainTypeId = normalizeInt(getCell($row, $headerMap, 'product_type_id', 0));
    $mainCategoryName = normalizeText(getCell($row, $headerMap, 'Категория', 0));
    $mainSubCategoryName = normalizeText(getCell($row, $headerMap, 'Подкатегория', 0));
    $mainBrandName = normalizeText(getCell($row, $headerMap, 'Брэнд', 0));

    $mainCategoryId = $mainCategoryName !== '' ? ($categoryMap[$mainCategoryName] ?? null) : null;
    if ($mainCategoryName !== '' && $mainCategoryId === null) {
        logLine($logEntries, "Missing category: {$mainCategoryName} (scu={$mainScu})");
    }
    $mainSubCategoryId = findSubCategoryId($mainCategoryName, $mainSubCategoryName, $subCategoryByPair, $subCategoryByName);
    if ($mainSubCategoryName !== '' && $mainSubCategoryId === null) {
        logLine($logEntries, "Missing subcategory: {$mainSubCategoryName} (scu={$mainScu})");
    }
    $mainBrandId = $mainBrandName !== '' ? ($brandMap[$mainBrandName] ?? null) : null;
    if ($mainBrandName !== '' && $mainBrandId === null) {
        logLine($logEntries, "Missing brand: {$mainBrandName} (scu={$mainScu})");
    }

    $mainUnitName = normalizeText(getCell($row, $headerMap, 'product_units', 0));
    $mainUnitId = $mainUnitName !== '' ? ($unitMap[$mainUnitName] ?? null) : null;
    if ($mainUnitName !== '' && $mainUnitId === null) {
        logLine($logEntries, "Missing unit: {$mainUnitName} (scu={$mainScu})");
    }

    $mainKindId = normalizeInt(getCell($row, $headerMap, 'product_kinds.id', 0));
    if ($mainKindId !== null && !isset($kindIdSet[$mainKindId])) {
        logLine($logEntries, "Missing product_kind_id: {$mainKindId} (scu={$mainScu})");
    }

    $mainExists = isset($productIdBySku[$mainScu]);

    $mainData = filterNulls([
        'tenant_id' => $tenantId,
        'company_id' => $companyId,
        'name' => normalizeText(getCell($row, $headerMap, 'name', 0)),
        'product_type_id' => $mainTypeId,
        'product_kind_id' => $mainKindId,
        'scu' => $mainScu,
        'category_id' => $mainCategoryId,
        'sub_category_id' => $mainSubCategoryId,
        'brand_id' => $mainBrandId,
        'unit_id' => $mainUnitId,
        'price' => normalizeNumber(getCell($row, $headerMap, 'price', 0)),
        'price_sale' => normalizeNumber(getCell($row, $headerMap, 'price_sale', 0)),
        'price_vendor' => normalizeNumber(getCell($row, $headerMap, 'price_vendor', 0)),
        'price_vendor_min' => normalizeNumber(getCell($row, $headerMap, 'price_vendor_min', 0)),
        'price_zakup' => normalizeNumber(getCell($row, $headerMap, 'price_zakup', 0)),
        'price_delivery' => normalizeNumber(getCell($row, $headerMap, 'price_delivery', 0)),
        'is_visible' => normalizeBool(getCell($row, $headerMap, 'is_visible', 0)),
        'is_top' => normalizeBool(getCell($row, $headerMap, 'is_top', 0)),
        'is_new' => normalizeBool(getCell($row, $headerMap, 'is_new', 0)),
        'updated_at' => $now,
    ]);

    $mainId = upsertProduct($conn, $productIdBySku, $mainScu, $mainData, $now, $counts, 'main', $insertOnly);

    if (!($insertOnly && $mainExists)) {
        $descData = filterNulls([
        'tenant_id' => $tenantId,
        'company_id' => $companyId,
        'product_id' => $mainId,
        'description_short' => normalizeText(getCell($row, $headerMap, 'description_short', 0)),
        'description_long' => normalizeText(getCell($row, $headerMap, 'description_long', 0)),
        'dignities' => normalizeText(getCell($row, $headerMap, 'dignites', 0)),
        'constructive' => normalizeText(getCell($row, $headerMap, 'constructive', 0)),
        'avito1' => normalizeText(getCell($row, $headerMap, 'avito1', 0)),
        'avito2' => normalizeText(getCell($row, $headerMap, 'Avito2', 0)),
        'updated_at' => $now,
    ]);
    if (hasMeaningfulData($descData, ['description_short', 'description_long', 'dignities', 'constructive', 'avito1', 'avito2'])) {
        upsertOneToOne($conn, 'product_descriptions', 'product_id', $mainId, $descData, $now);
    }

        foreach ($attributePairs as $pair) {
            $attrName = normalizeText(getCellByIndex($row, $pair['name_idx']));
            $attrValue = normalizeText(getCellByIndex($row, $pair['value_idx']));
            if ($attrName === '' || $attrValue === '') {
                continue;
            }
            $attrKey = normalizeText($attrName);
            $valueType = isNumericValue($attrValue) ? 'number' : 'string';

            if (!isset($attributeDefs[$attrKey])) {
                $defId = (int) $conn->table('product_attribute_definitions')->insertGetId([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'name' => $attrName,
                    'value_type' => $valueType,
                    'product_type_id' => $mainTypeId,
                    'product_kind_id' => $mainKindId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $attributeDefs[$attrKey] = ['id' => $defId, 'value_type' => $valueType];
            } elseif ($attributeDefs[$attrKey]['value_type'] === 'number' && $valueType === 'string') {
                $conn->table('product_attribute_definitions')
                    ->where('id', $attributeDefs[$attrKey]['id'])
                    ->update(['value_type' => 'string', 'updated_at' => $now]);
                $attributeDefs[$attrKey]['value_type'] = 'string';
            }

            $def = $attributeDefs[$attrKey];
            $valueData = [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'product_id' => $mainId,
                'attribute_id' => $def['id'],
                'updated_at' => $now,
            ];

            if ($def['value_type'] === 'number' && isNumericValue($attrValue)) {
                $valueData['value_number'] = normalizeNumber($attrValue);
                $valueData['value_string'] = null;
            } else {
                $valueData['value_string'] = $attrValue;
                $valueData['value_number'] = null;
            }

            upsertAttributeValue($conn, $valueData, $now);
            $counts['attributes']++;
        }

        $mediaUrls = [];
        foreach (['ФОТО', 'ФОТО2', 'ФОТО3', 'ФОТО4'] as $photoKey) {
            $url = normalizeText(getCell($row, $headerMap, $photoKey, 0));
            if ($url !== '') {
                $mediaUrls[] = ['type' => 'image', 'url' => $url];
            }
        }
        $videoUrl = normalizeText(getCell($row, $headerMap, 'Видео', 0));
        if ($videoUrl !== '') {
            $mediaUrls[] = ['type' => 'video', 'url' => $videoUrl];
        }
        if ($mediaUrls) {
            $conn->table('product_media')->where('product_id', $mainId)->delete();
            $order = 10;
            foreach ($mediaUrls as $media) {
                $conn->table('product_media')->insert([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'product_id' => $mainId,
                    'type' => $media['type'],
                    'url' => $media['url'],
                    'sort_order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $order += 10;
                $counts['media']++;
            }
        }

        $relatedScuRaw = normalizeText(getCell($row, $headerMap, 'Сопутствующие товары', 0));
        if ($relatedScuRaw !== '') {
            $relatedList = array_values(array_filter(array_map('normalizeText', preg_split('/[,;]+/u', $relatedScuRaw))));
            if ($relatedList) {
                $pendingRelated[] = ['main_scu' => $mainScu, 'related' => $relatedList];
            }
        }
    } else {
        $counts['skipped']++;
    }

    $workScu = normalizeText(getCell($row, $headerMap, 'SCU', 0));
    if ($workScu !== '') {
        $workExists = isset($productIdBySku[$workScu]);
        $workTypeId = normalizeInt(getCell($row, $headerMap, 'product_type_id', 1));
        $workCategoryName = normalizeText(getCell($row, $headerMap, 'Категория', 1));
        $workCategoryId = $workCategoryName !== '' ? ($categoryMap[$workCategoryName] ?? null) : null;
        if ($workCategoryName !== '' && $workCategoryId === null) {
            logLine($logEntries, "Missing work category: {$workCategoryName} (scu={$workScu})");
        }

        $workData = filterNulls([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'name' => normalizeText(getCell($row, $headerMap, 'name', 1)),
            'product_type_id' => $workTypeId,
            'scu' => $workScu,
            'category_id' => $workCategoryId,
            'price_sale' => normalizeNumber(getCell($row, $headerMap, 'price_sale', 1)),
            'price_vendor_min' => normalizeNumber(getCell($row, $headerMap, 'price_vendor_min', 1)),
            'price_zakup' => normalizeNumber(getCell($row, $headerMap, 'price_zakup', 1)),
            'is_visible' => normalizeBool(getCell($row, $headerMap, 'Видимость на сайте', 0)),
            'updated_at' => $now,
        ]);

        $workId = upsertProduct($conn, $productIdBySku, $workScu, $workData, $now, $counts, 'work', $insertOnly);
        if (!($insertOnly && $workExists && $mainExists)) {
            upsertRelation($conn, $tenantId, $companyId, $mainId, $workId, 'INSTALLATION_WORK', $now);
            $counts['relations']++;
        }
    }
}

foreach ($pendingRelated as $rel) {
    $mainId = $productIdBySku[$rel['main_scu']] ?? null;
    if (!$mainId) {
        continue;
    }

    if (!$insertOnly) {
        $conn->table('product_relations')
            ->where('product_id', $mainId)
            ->where('relation_type', 'RELATED')
            ->delete();
    }

    $unique = array_values(array_unique($rel['related']));
    foreach ($unique as $relatedScu) {
        $relatedId = $productIdBySku[$relatedScu] ?? null;
        if (!$relatedId && str_contains($relatedScu, ' ')) {
            $normalized = normalizeText(str_replace(' ', '-', $relatedScu));
            $relatedId = $productIdBySku[$normalized] ?? null;
            if ($relatedId) {
                $relatedScu = $normalized;
                logLine($logEntries, "Normalized related SCU: {$relatedScu}");
            }
        }
        if (!$relatedId) {
            $missingRelated[$relatedScu] = true;
            continue;
        }
        if ($relatedId === $mainId) {
            continue;
        }
        upsertRelation($conn, $tenantId, $companyId, $mainId, $relatedId, 'RELATED', $now);
        $counts['related']++;
    }
}

echo "Import complete.\n";
foreach ($counts as $key => $value) {
    echo "{$key}: {$value}\n";
}
if ($missingRelated) {
    $missing = implode(', ', array_keys($missingRelated));
    echo "Missing related SCU: {$missing}\n";
    logLine($logEntries, "Missing related SCU: {$missing}");
}

flushLog($logPath, $logEntries);
echo "Log: {$logPath}\n";

function readSheet(string $filePath): array
{
    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new RuntimeException("Unable to open xlsx file: {$filePath}");
    }

    $sharedStrings = readSharedStrings($zip);
    $sheetPath = getFirstSheetPath($zip);
    if ($sheetPath === null) {
        throw new RuntimeException('No worksheets found in xlsx.');
    }

    $sheetXml = $zip->getFromName($sheetPath);
    if ($sheetXml === false) {
        throw new RuntimeException("Worksheet not found: {$sheetPath}");
    }

    $sheet = simplexml_load_string($sheetXml);
    $sheet->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

    $headers = [];
    $rows = [];
    $rowIndex = 0;

    foreach ($sheet->sheetData->row as $row) {
        $rowIndex++;
        $cells = [];
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $col = preg_replace('/[^A-Z]/', '', strtoupper($ref));
            if ($col === '') {
                continue;
            }
            $idx = colToIndex($col);
            $cells[$idx] = readCellValue($cell, $sharedStrings);
        }
        if (!$cells) {
            continue;
        }
        $max = max(array_keys($cells));
        $rowValues = [];
        for ($i = 0; $i <= $max; $i++) {
            $rowValues[$i] = $cells[$i] ?? null;
        }
        if ($rowIndex === 1) {
            $headers = $rowValues;
        } else {
            $rows[] = $rowValues;
        }
    }

    $zip->close();

    return [$headers, $rows];
}

function readSharedStrings(ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return [];
    }

    $doc = simplexml_load_string($xml);
    $doc->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $strings = [];

    foreach ($doc->si as $si) {
        $text = '';
        foreach ($si->t as $t) {
            $text .= (string) $t;
        }
        if ($text === '') {
            foreach ($si->r as $run) {
                $text .= (string) $run->t;
            }
        }
        $strings[] = $text;
    }

    return $strings;
}

function getFirstSheetPath(ZipArchive $zip): ?string
{
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml === false || $relsXml === false) {
        return null;
    }

    $workbook = simplexml_load_string($workbookXml);
    $workbook->registerXPathNamespace('main', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
    $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

    $rels = simplexml_load_string($relsXml);
    $rels->registerXPathNamespace('rel', 'http://schemas.openxmlformats.org/package/2006/relationships');

    $relMap = [];
    foreach ($rels->Relationship as $rel) {
        $relMap[(string) $rel['Id']] = (string) $rel['Target'];
    }

    foreach ($workbook->sheets->sheet as $sheet) {
        $rid = (string) $sheet->attributes('r', true)->id;
        $target = $relMap[$rid] ?? null;
        if ($target) {
            return 'xl/' . ltrim($target, '/');
        }
    }

    return null;
}

function readCellValue(SimpleXMLElement $cell, array $sharedStrings): ?string
{
    $type = (string) $cell['t'];
    if ($type === 's') {
        $idx = (int) $cell->v;
        return $sharedStrings[$idx] ?? null;
    }
    if ($type === 'inlineStr') {
        return isset($cell->is->t) ? (string) $cell->is->t : null;
    }
    if (isset($cell->v)) {
        return (string) $cell->v;
    }
    return null;
}

function colToIndex(string $col): int
{
    $idx = 0;
    $len = strlen($col);
    for ($i = 0; $i < $len; $i++) {
        $idx = $idx * 26 + (ord($col[$i]) - ord('A') + 1);
    }
    return $idx - 1;
}

function buildHeaderMap(array $headers): array
{
    $map = [];
    foreach ($headers as $idx => $name) {
        $key = normalizeText((string) $name);
        if ($key === '') {
            continue;
        }
        $map[$key][] = (int) $idx;
    }
    return $map;
}

function buildAttributePairs(array $headerMap): array
{
    $pairs = [];
    foreach ($headerMap as $key => $indices) {
        if (preg_match('/^свойство\\s+(\\d+)$/ui', $key, $m)) {
            $num = $m[1];
            $nameIdx = $indices[0];
            $valueKey = normalizeText("Свойство {$num} значение");
            if (!isset($headerMap[$valueKey])) {
                continue;
            }
            $pairs[] = [
                'name_idx' => $nameIdx,
                'value_idx' => $headerMap[$valueKey][0],
            ];
        }
    }
    return $pairs;
}

function getCell(array $row, array $headerMap, string $name, int $occurrence): ?string
{
    $key = normalizeText($name);
    if (!isset($headerMap[$key][$occurrence])) {
        return null;
    }
    return $row[$headerMap[$key][$occurrence]] ?? null;
}

function getCellByIndex(array $row, int $idx): ?string
{
    return $row[$idx] ?? null;
}

function normalizeText(?string $value): string
{
    if ($value === null) {
        return '';
    }
    $value = preg_replace('/\\s+/u', ' ', trim($value));
    return $value ?? '';
}

function normalizeBool(?string $value): ?int
{
    $value = normalizeText($value);
    if ($value === '') {
        return null;
    }
    $lower = mb_strtolower($value);
    $truthy = ['1', 'yes', 'true', 'да'];
    $falsy = ['0', 'no', 'false', 'нет'];
    if (in_array($lower, $truthy, true)) {
        return 1;
    }
    if (in_array($lower, $falsy, true)) {
        return 0;
    }
    if (is_numeric($value)) {
        return ((float) $value) > 0 ? 1 : 0;
    }
    return null;
}

function normalizeInt(?string $value): ?int
{
    $value = normalizeText($value);
    if ($value === '') {
        return null;
    }
    return (int) $value;
}

function normalizeNumber(?string $value): ?float
{
    $value = normalizeText($value);
    if ($value === '') {
        return null;
    }
    $value = str_replace(',', '.', $value);
    if (!is_numeric($value)) {
        return null;
    }
    return (float) $value;
}

function isNumericValue(string $value): bool
{
    $value = normalizeText($value);
    if ($value === '') {
        return false;
    }
    $value = str_replace(',', '.', $value);
    return is_numeric($value);
}

function filterNulls(array $data): array
{
    return array_filter($data, static fn ($value) => $value !== null);
}

function hasMeaningfulData(array $data, array $fields): bool
{
    foreach ($fields as $field) {
        if (!empty($data[$field])) {
            return true;
        }
    }
    return false;
}

function findSubCategoryId(
    string $categoryName,
    string $subName,
    array $subCategoryByPair,
    array $subCategoryByName
): ?int {
    if ($subName === '') {
        return null;
    }
    if ($categoryName !== '') {
        $key = "{$categoryName}|{$subName}";
        if (isset($subCategoryByPair[$key])) {
            return $subCategoryByPair[$key];
        }
    }
    return $subCategoryByName[$subName] ?? null;
}

function upsertProduct(
    $conn,
    array &$productIdBySku,
    string $scu,
    array $data,
    $now,
    array &$counts,
    string $type,
    bool $insertOnly
): int {
    if (isset($productIdBySku[$scu])) {
        $id = (int) $productIdBySku[$scu];
        if (!$insertOnly) {
            $conn->table('products')->where('id', $id)->update($data);
            $counts["{$type}_updated"]++;
        }
        return $id;
    }

    $data['created_at'] = $now;
    $conn->table('products')->insert($data);
    $id = (int) $conn->getPdo()->lastInsertId();
    $productIdBySku[$scu] = $id;
    $counts["{$type}_inserted"]++;
    return $id;
}

function upsertOneToOne($conn, string $table, string $key, int $value, array $data, $now): void
{
    $existing = $conn->table($table)->where($key, $value)->value('id');
    if ($existing) {
        $conn->table($table)->where('id', $existing)->update($data);
        return;
    }
    $data['created_at'] = $now;
    $conn->table($table)->insert($data);
}

function upsertAttributeValue($conn, array $data, $now): void
{
    $existing = $conn->table('product_attribute_values')
        ->where('product_id', $data['product_id'])
        ->where('attribute_id', $data['attribute_id'])
        ->value('id');
    if ($existing) {
        $conn->table('product_attribute_values')->where('id', $existing)->update($data);
        return;
    }
    $data['created_at'] = $now;
    $conn->table('product_attribute_values')->insert($data);
}

function upsertRelation($conn, int $tenantId, int $companyId, int $productId, int $relatedId, string $type, $now): void
{
    $existing = $conn->table('product_relations')
        ->where('product_id', $productId)
        ->where('related_product_id', $relatedId)
        ->where('relation_type', $type)
        ->value('id');
    if ($existing) {
        return;
    }
    $conn->table('product_relations')->insert([
        'tenant_id' => $tenantId,
        'company_id' => $companyId,
        'product_id' => $productId,
        'related_product_id' => $relatedId,
        'relation_type' => $type,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function logLine(array &$logEntries, string $message): void
{
    $logEntries[] = '[' . date('Y-m-d H:i:s') . "] {$message}";
}

function flushLog(string $path, array $logEntries): void
{
    if (!$logEntries) {
        return;
    }
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($path, implode(PHP_EOL, $logEntries) . PHP_EOL, FILE_APPEND);
}
