<?php

declare(strict_types=1);

namespace App\Services\Catalog;

use App\Domain\Catalog\Models\Product;
use App\Domain\Catalog\Models\ProductAttributeDefinition;
use App\Domain\Catalog\Models\ProductAttributeValue;
use App\Domain\Catalog\Models\ProductBrand;
use App\Domain\Catalog\Models\ProductCategory;
use App\Domain\Catalog\Models\ProductDescription;
use App\Domain\Catalog\Models\ProductKind;
use App\Domain\Catalog\Models\ProductMedia;
use App\Domain\Catalog\Models\ProductRelation;
use App\Domain\Catalog\Models\ProductSubcategory;
use App\Domain\Catalog\Models\ProductType;
use App\Domain\Catalog\Models\ProductUnit;
use App\Exports\Catalog\PricebookExport;
use App\Imports\Catalog\PricebookImport;
use App\Services\Pricing\PriceWriterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use RuntimeException;

final class PricebookService
{
    private const DELETE_ACTIONS = ['DELETE', 'ARCHIVE'];
    private const ACTION_CREATE = 'CREATE';
    private const ACTION_UPDATE = 'UPDATE';

    public function export(int $tenantId, int $companyId): PricebookExport
    {
        return new PricebookExport($this->buildExportSheets($tenantId, $companyId));
    }

    public function template(int $tenantId, int $companyId): string
    {
        $dir = storage_path('app/pricebooks/templates');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $filePath = $dir . DIRECTORY_SEPARATOR . 'pricebook_template.xlsx';
        $sheets = $this->buildTemplateSheets($tenantId, $companyId);
        Excel::store(new PricebookExport($sheets), 'pricebooks/templates/pricebook_template.xlsx');

        return $filePath;
    }

    public function import(int $tenantId, int $companyId, ?int $userId, string $filePath): array
    {
        $errors = [];
        $import = new PricebookImport();

        try {
            Excel::import($import, $filePath);
        } catch (RuntimeException $exception) {
            return [
                'created' => 0,
                'updated' => 0,
                'archived' => 0,
                'errors' => [$exception->getMessage()],
            ];
        }

        [$productHeaders, $productRows] = $this->splitSheet($import->data, PricebookSchema::KEY_PRODUCTS);
        [$descriptionHeaders, $descriptionRows] = $this->splitSheet($import->data, PricebookSchema::KEY_DESCRIPTIONS);
        [$attributeHeaders, $attributeRows] = $this->splitSheet($import->data, PricebookSchema::KEY_ATTRIBUTES);
        [$mediaHeaders, $mediaRows] = $this->splitSheet($import->data, PricebookSchema::KEY_MEDIA);

        $productHeaderKeys = $this->resolveHeaders(
            $productHeaders,
            PricebookSchema::productsHeaderAliases(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_PRODUCTS),
            $errors
        );
        $descriptionHeaderKeys = $this->resolveHeaders(
            $descriptionHeaders,
            PricebookSchema::descriptionsHeaderAliases(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_DESCRIPTIONS),
            $errors
        );
        $attributeHeaderKeys = $this->resolveHeaders(
            $attributeHeaders,
            PricebookSchema::attributesHeaderAliases(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_ATTRIBUTES),
            $errors
        );
        $mediaHeaderKeys = $this->resolveHeaders(
            $mediaHeaders,
            PricebookSchema::mediaHeaderAliases(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_MEDIA),
            $errors
        );

        $this->validateHeaders(
            $productHeaderKeys,
            PricebookSchema::productsKeys(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_PRODUCTS),
            $errors
        );
        $this->validateHeaders(
            $descriptionHeaderKeys,
            PricebookSchema::descriptionsKeys(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_DESCRIPTIONS),
            $errors
        );
        $this->validateHeaders(
            $attributeHeaderKeys,
            PricebookSchema::attributesKeys(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_ATTRIBUTES),
            $errors
        );
        $this->validateHeaders(
            $mediaHeaderKeys,
            PricebookSchema::mediaKeys(),
            PricebookSchema::sheetLabel(PricebookSchema::KEY_MEDIA),
            $errors
        );

        if ($errors) {
            return [
                'created' => 0,
                'updated' => 0,
                'archived' => 0,
                'errors' => $errors,
            ];
        }

        $lookups = $this->loadLookups($tenantId, $companyId);
        $definitions = $this->loadAttributeDefinitions($tenantId, $companyId);
        $pendingDefinitions = [];

        $products = [];
        $deleteScus = [];
        $workProducts = [];
        $workScuMap = [];
        $workLines = [];
        $workLinkOnly = [];
        $relatedScuMap = [];
        $productLines = [];

        foreach ($productRows as $index => $row) {
            $rowNumber = $index + 2;
            $rowData = $this->mapRow($productHeaderKeys, $row);
            if ($this->isRowEmpty($rowData)) {
                continue;
            }

            $scu = $this->normalizeText($rowData['scu'] ?? null);
            if ($scu === '') {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: SCU обязателен.";
                continue;
            }

            if (isset($products[$scu]) || isset($deleteScus[$scu])) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: SCU {$scu} повторяется.";
                continue;
            }

            $action = strtoupper(trim((string) ($rowData['action'] ?? '')));
            if ($action === '') {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: поле action обязательно (CREATE/UPDATE/DELETE).";
                continue;
            }
            if (!in_array($action, [self::ACTION_CREATE, self::ACTION_UPDATE], true) && !in_array($action, self::DELETE_ACTIONS, true)) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: Недопустимое действие {$action}.";
                continue;
            }

            if (in_array($action, self::DELETE_ACTIONS, true)) {
                $deleteScus[$scu] = true;
                $productLines[$scu] = $rowNumber;
                continue;
            }

            $requiredFields = [
                'name',
                'product_type_id',
                'product_kind_id',
                'unit_id',
                'category_id',
                'subcategory_id',
                'brand_id',
                'is_visible',
                'is_top',
                'is_new',
                'price',
                'price_sale',
                'price_vendor',
                'price_vendor_min',
                'price_zakup',
                'price_delivery',
                'montaj',
                'montaj_sebest',
            ];

            foreach ($requiredFields as $field) {
                if ($this->isMissing($rowData[$field] ?? null)) {
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: поле {$field} обязательно.";
                }
            }

            $payload = $this->buildProductPayload($rowData, $tenantId, $companyId, $userId, $errors, $rowNumber, $lookups);
            if ($payload === null) {
                continue;
            }

            $payload['__action'] = $action;
            $products[$scu] = $payload;
            $productLines[$scu] = $rowNumber;
            $relatedScuMap[$scu] = $this->splitList($rowData['related_scu'] ?? null);

            $workScu = $this->normalizeText($rowData['work_scu'] ?? $rowData['installation_work_scu'] ?? null);
            $hasWorkDetails = $this->hasWorkDetails($rowData);
            if ($workScu !== '') {
                if ($workScu === $scu) {
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: work_scu не может совпадать с SCU товара.";
                    continue;
                }
                if (isset($products[$workScu]) || isset($deleteScus[$workScu])) {
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: work_scu {$workScu} конфликтует с другим товаром.";
                    continue;
                }
                if (isset($workProducts[$workScu])) {
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: work_scu {$workScu} повторяется.";
                    continue;
                }

                $workScuMap[$scu] = $workScu;
                $workLines[$workScu] = $rowNumber;

                if ($hasWorkDetails) {
                    $workPayload = $this->buildWorkPayload(
                        $rowData,
                        $payload,
                        $tenantId,
                        $companyId,
                        $userId,
                        $errors,
                        $rowNumber,
                        $lookups
                    );
                    if ($workPayload !== null) {
                        $workProducts[$workScu] = $workPayload;
                        if ($workPayload['price_zakup'] !== null) {
                            $products[$scu]['montaj_sebest'] = $workPayload['price_zakup'];
                        }
                    }
                } else {
                    $workLinkOnly[$scu] = true;
                }
            }
        }
        $descriptions = [];
        foreach ($descriptionRows as $index => $row) {
            $rowNumber = $index + 2;
            $rowData = $this->mapRow($descriptionHeaderKeys, $row);
            if ($this->isRowEmpty($rowData)) {
                continue;
            }

            $scu = $this->normalizeText($rowData['scu'] ?? null);
            if ($scu === '') {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_DESCRIPTIONS, $rowNumber)}: SCU обязателен.";
                continue;
            }

            if (!isset($products[$scu]) && !isset($deleteScus[$scu])) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_DESCRIPTIONS, $rowNumber)}: SCU {$scu} отсутствует на листе Products.";
                continue;
            }

            if (isset($descriptions[$scu])) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_DESCRIPTIONS, $rowNumber)}: SCU {$scu} повторяется.";
                continue;
            }

            $descriptions[$scu] = [
                'description_short' => (string) ($rowData['description_short'] ?? ''),
                'description_long' => (string) ($rowData['description_long'] ?? ''),
                'dignities' => (string) ($rowData['dignities'] ?? ''),
                'constructive' => (string) ($rowData['constructive'] ?? ''),
                'avito1' => (string) ($rowData['avito1'] ?? ''),
                'avito2' => (string) ($rowData['avito2'] ?? ''),
            ];
        }

        foreach (array_keys($products) as $scu) {
            if (!isset($descriptions[$scu])) {
                $rowNumber = $productLines[$scu] ?? 0;
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_DESCRIPTIONS, $rowNumber)}: для SCU {$scu} отсутствует строка описания.";
            }
        }

        $attributes = [];
        foreach ($attributeRows as $index => $row) {
            $rowNumber = $index + 2;
            $rowData = $this->mapRow($attributeHeaderKeys, $row);
            if ($this->isRowEmpty($rowData)) {
                continue;
            }

            $scu = $this->normalizeText($rowData['scu'] ?? null);
            $attributeName = $this->normalizeText($rowData['attribute_name'] ?? null);
            if ($scu === '' || $attributeName === '') {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_ATTRIBUTES, $rowNumber)}: SCU и attribute_name обязательны.";
                continue;
            }

            if (!isset($products[$scu]) && !isset($deleteScus[$scu])) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_ATTRIBUTES, $rowNumber)}: SCU {$scu} отсутствует на листе Products.";
                continue;
            }
            if (isset($deleteScus[$scu])) {
                continue;
            }

            $definition = $this->findDefinition($definitions, $attributeName, $products[$scu]['product_kind_id'] ?? null);
            $valueString = $rowData['value_string'] ?? null;
            $valueNumber = $rowData['value_number'] ?? null;
            $hasNumber = !$this->isMissing($valueNumber);
            $hasString = !$this->isMissing($valueString);

            if (!$hasNumber && !$hasString) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_ATTRIBUTES, $rowNumber)}: значение для {$attributeName} обязательно.";
                continue;
            }

            $valueType = $definition?->value_type ?? ($hasNumber ? 'number' : 'string');
            if ($definition === null) {
                $defKey = $this->normalizeHeader($attributeName) . '|' . (string) ($products[$scu]['product_kind_id'] ?? 0);
                if (!isset($pendingDefinitions[$defKey])) {
                    $pendingDefinitions[$defKey] = [
                        'name' => $attributeName,
                        'product_kind_id' => $products[$scu]['product_kind_id'] ?? null,
                        'value_type' => $valueType,
                    ];
                }
            }

            if ($valueType === 'number') {
                $parsed = $this->parseNumber($hasNumber ? $valueNumber : $valueString);
                if ($parsed === null) {
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_ATTRIBUTES, $rowNumber)}: значение для {$attributeName} должно быть числом.";
                    continue;
                }
                $payload = [
                    'value_string' => null,
                    'value_number' => $parsed,
                ];
            } else {
                $parsed = $this->normalizeText($hasString ? $valueString : $valueNumber);
                if ($parsed === '') {
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_ATTRIBUTES, $rowNumber)}: значение для {$attributeName} обязательно.";
                    continue;
                }
                $payload = [
                    'value_string' => $parsed,
                    'value_number' => null,
                ];
            }

            if ($definition) {
                $attributes[$scu][] = array_merge($payload, [
                    'attribute_id' => $definition->id,
                ]);
            } else {
                $defKey = $this->normalizeHeader($attributeName) . '|' . (string) ($products[$scu]['product_kind_id'] ?? 0);
                $attributes[$scu][] = array_merge($payload, [
                    'attribute_key' => $defKey,
                ]);
            }
        }

        $media = [];
        foreach ($mediaRows as $index => $row) {
            $rowNumber = $index + 2;
            $rowData = $this->mapRow($mediaHeaderKeys, $row);
            if ($this->isRowEmpty($rowData)) {
                continue;
            }

            $scu = $this->normalizeText($rowData['scu'] ?? null);
            $type = strtolower($this->normalizeText($rowData['type'] ?? null));
            $path = $this->normalizeText($rowData['path'] ?? null);
            if ($scu === '' || $type === '' || $path === '') {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_MEDIA, $rowNumber)}: SCU, type и path обязательны.";
                continue;
            }

            if (!isset($products[$scu]) && !isset($deleteScus[$scu])) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_MEDIA, $rowNumber)}: SCU {$scu} отсутствует на листе Products.";
                continue;
            }

            if (!in_array($type, ['image', 'video'], true)) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_MEDIA, $rowNumber)}: type должен быть image или video.";
                continue;
            }

            $sortOrder = $this->parseInt($rowData['sort_order'] ?? null);
            if ($sortOrder === null) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_MEDIA, $rowNumber)}: sort_order обязателен.";
                continue;
            }

            $media[$scu][] = [
                'type' => $type,
                'path' => $path,
                'sort_order' => $sortOrder,
            ];
        }

        $allScus = array_unique(array_merge(
            array_keys($products),
            array_keys($workProducts),
            array_values($workScuMap),
            array_keys($deleteScus)
        ));

        if ($errors) {
            return [
                'created' => 0,
                'updated' => 0,
                'archived' => 0,
                'errors' => $errors,
            ];
        }

        $existing = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereIn('scu', $allScus)
            ->get()
            ->keyBy('scu');

        $availableScus = array_unique(array_merge($allScus, $existing->keys()->all()));

        foreach ($products as $scu => $payload) {
            $action = $payload['__action'] ?? self::ACTION_UPDATE;
            $existingProduct = $existing->get($scu);
            if ($action === self::ACTION_CREATE && $existingProduct) {
                $rowNumber = $productLines[$scu] ?? 0;
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: SCU {$scu} уже существует, используйте UPDATE.";
            }
            if ($action === self::ACTION_UPDATE && !$existingProduct) {
                $rowNumber = $productLines[$scu] ?? 0;
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: SCU {$scu} не найден для UPDATE.";
            }
            $installationScu = $workScuMap[$scu] ?? null;
            if ($installationScu && !in_array($installationScu, $availableScus, true)) {
                $rowNumber = $productLines[$scu] ?? 0;
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: work_scu {$installationScu} не найден.";
            }
            foreach ($relatedScuMap[$scu] ?? [] as $relatedScu) {
                if (!in_array($relatedScu, $availableScus, true)) {
                    $rowNumber = $productLines[$scu] ?? 0;
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: related_scu {$relatedScu} не найден.";
                }
                if (isset($deleteScus[$relatedScu])) {
                    $rowNumber = $productLines[$scu] ?? 0;
                    $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: related_scu {$relatedScu} помечен на удаление.";
                }
            }

            if ($existingProduct && $existingProduct->is_global) {
                $rowNumber = $productLines[$scu] ?? 0;
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: глобальный товар {$scu} нельзя редактировать.";
            }
        }

        foreach (array_keys($deleteScus) as $scu) {
            if (!$existing->has($scu)) {
                $rowNumber = $productLines[$scu] ?? 0;
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: SCU {$scu} не найден для удаления.";
            }
        }

        if ($errors) {
            return [
                'created' => 0,
                'updated' => 0,
                'archived' => 0,
                'errors' => $errors,
            ];
        }

        $created = 0;
        $updated = 0;
        $archived = 0;

        DB::transaction(function () use (
            $tenantId,
            $companyId,
            $userId,
            $products,
            $deleteScus,
            $existing,
            $descriptions,
            $attributes,
            $media,
            $workProducts,
            $workScuMap,
            $workLines,
            $workLinkOnly,
            $relatedScuMap,
            $pendingDefinitions,
            &$created,
            &$updated,
            &$archived
        ): void {
            $productIds = [];
            $timestamp = now();
            $definitionIds = [];

            foreach ($pendingDefinitions as $key => $definition) {
                $model = ProductAttributeDefinition::query()->create([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'product_kind_id' => $definition['product_kind_id'],
                    'name' => $definition['name'],
                    'value_type' => $definition['value_type'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);
                $definitionIds[$key] = $model->id;
            }

            foreach (array_keys($deleteScus) as $scu) {
                $model = $existing->get($scu);
                if (!$model) {
                    continue;
                }
                $model->archived_at = $timestamp;
                $model->is_visible = false;
                if ($userId) {
                    $model->updated_by = $userId;
                }
                $model->save();
                $archived++;
            }

            foreach ($products as $scu => $payload) {
                $action = $payload['__action'] ?? self::ACTION_UPDATE;
                unset($payload['__action']);
                $model = $existing->get($scu);
                if ($model && $action === self::ACTION_UPDATE) {
                    $payload['archived_at'] = null;
                    $payload['updated_at'] = $timestamp;
                    if ($userId) {
                        $payload['updated_by'] = $userId;
                    }
                    $model->fill($payload);
                    $model->save();
                    $updated++;
                } elseif (!$model && $action === self::ACTION_CREATE) {
                    $payload['tenant_id'] = $tenantId;
                    $payload['company_id'] = $companyId;
                    $payload['created_at'] = $timestamp;
                    $payload['updated_at'] = $timestamp;
                    if ($userId) {
                        $payload['created_by'] = $userId;
                        $payload['updated_by'] = $userId;
                    }
                    $model = Product::query()->create($payload);
                    $created++;
                } else {
                    continue;
                }
                $productIds[$scu] = $model->id;
                $this->syncOperationalPrices($tenantId, $companyId, $model, $userId);

                $descriptionPayload = $descriptions[$scu] ?? null;
                if ($descriptionPayload) {
                    $descriptionPayload['tenant_id'] = $tenantId;
                    $descriptionPayload['company_id'] = $companyId;
                    if ($userId) {
                        $descriptionPayload['updated_by'] = $userId;
                    }
                    ProductDescription::query()->updateOrCreate(
                        ['product_id' => $model->id],
                        $descriptionPayload
                    );
                }

                ProductAttributeValue::query()
                    ->where('product_id', $model->id)
                    ->delete();
                if (!empty($attributes[$scu])) {
                    $rows = [];
                    foreach ($attributes[$scu] as $attributeRow) {
                        $attributeId = $attributeRow['attribute_id']
                            ?? ($definitionIds[$attributeRow['attribute_key'] ?? ''] ?? null);
                        if (!$attributeId) {
                            continue;
                        }
                        unset($attributeRow['attribute_id'], $attributeRow['attribute_key']);
                        $rows[] = array_merge($attributeRow, [
                            'attribute_id' => $attributeId,
                            'tenant_id' => $tenantId,
                            'company_id' => $companyId,
                            'product_id' => $model->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                    }
                    ProductAttributeValue::query()->insert($rows);
                }

                ProductMedia::query()
                    ->where('product_id', $model->id)
                    ->delete();
                if (!empty($media[$scu])) {
                    $rows = [];
                    foreach ($media[$scu] as $mediaRow) {
                        $rows[] = array_merge($mediaRow, [
                            'tenant_id' => $tenantId,
                            'company_id' => $companyId,
                            'product_id' => $model->id,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                    }
                    ProductMedia::query()->insert($rows);
                }

                $installationScu = $workScuMap[$scu] ?? null;
                if ($installationScu && isset($workProducts[$installationScu]) && !isset($productIds[$installationScu])) {
                    $workPayload = $workProducts[$installationScu];
                    $workDefaults = $workPayload['__defaults'] ?? [];
                    unset($workPayload['__defaults']);

                    $workModel = $existing->get($installationScu);
                    if ($workModel) {
                        $workPayload['archived_at'] = null;
                        $workPayload['updated_at'] = $timestamp;
                        if ($userId) {
                            $workPayload['updated_by'] = $userId;
                        }
                        $workModel->fill($workPayload);
                        $workModel->save();
                        $updated++;
                    } else {
                        $createPayload = array_merge($workDefaults, $workPayload);
                        $createPayload['tenant_id'] = $tenantId;
                        $createPayload['company_id'] = $companyId;
                        $createPayload['created_at'] = $timestamp;
                        $createPayload['updated_at'] = $timestamp;
                        if ($userId) {
                            $createPayload['created_by'] = $userId;
                            $createPayload['updated_by'] = $userId;
                        }
                        $workModel = Product::query()->create($createPayload);
                        $created++;
                    }
                    $productIds[$installationScu] = $workModel->id;
                    $this->syncOperationalPrices($tenantId, $companyId, $workModel, $userId);
                }

                ProductRelation::query()
                    ->where('product_id', $model->id)
                    ->whereIn('relation_type', ['RELATED', 'INSTALLATION_WORK'])
                    ->delete();

                $relations = [];
                if ($installationScu) {
                    $installationId = $productIds[$installationScu] ?? ($existing[$installationScu]->id ?? null);
                    if ($installationId) {
                        $relations[] = [
                            'tenant_id' => $tenantId,
                            'company_id' => $companyId,
                            'product_id' => $model->id,
                            'related_product_id' => $installationId,
                            'relation_type' => 'INSTALLATION_WORK',
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ];
                    }
                }

                foreach ($relatedScuMap[$scu] ?? [] as $relatedScu) {
                    $relatedId = $productIds[$relatedScu] ?? ($existing[$relatedScu]->id ?? null);
                    if (!$relatedId) {
                        continue;
                    }
                    $relations[] = [
                        'tenant_id' => $tenantId,
                        'company_id' => $companyId,
                        'product_id' => $model->id,
                        'related_product_id' => $relatedId,
                        'relation_type' => 'RELATED',
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ];
                }

                if ($relations) {
                    ProductRelation::query()->insert($relations);
                }

                if ($installationScu && isset($workLinkOnly[$scu]) && array_key_exists('montaj_sebest', $payload)) {
                    $this->syncInstallationWorkPrice(
                        $tenantId,
                        $companyId,
                        $installationScu,
                        $payload['montaj_sebest'],
                        $userId,
                        $timestamp
                    );
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'archived' => $archived,
            'errors' => [],
        ];
    }

    private function buildLookupRows(): array
    {
        return array_merge([PricebookSchema::lookupsColumns()], [
            ['product_types', 'id', 'name'],
            ['product_kinds', 'id', 'name'],
            ['product_units', 'id', 'name'],
            ['product_categories', 'id', 'name'],
            ['product_subcategories', 'id', 'name'],
            ['product_brands', 'id', 'name'],
        ]);
    }

    /**
     * @return array<int, string|null>
     */
    private function resolveHeaders(array $headers, array $aliases, string $sheetLabel, array &$errors): array
    {
        $resolved = [];
        foreach ($headers as $header) {
            $normalized = $this->normalizeHeader($header);
            $key = $aliases[$normalized] ?? null;
            if ($key === null) {
                $errors[] = "\u{041B}\u{0438}\u{0441}\u{0442} {$sheetLabel}: \u{043D}\u{0435}\u{0438}\u{0437}\u{0432}\u{0435}\u{0441}\u{0442}\u{043D}\u{044B}\u{0439} \u{0441}\u{0442}\u{043E}\u{043B}\u{0431}\u{0435}\u{0446} {$header}.";
            }
            $resolved[] = $key;
        }
        return $resolved;
    }

    private function validateHeaders(array $headers, array $expected, string $sheetLabel, array &$errors): void
    {
        if ($headers !== $expected) {
            $errors[] = "\u{041B}\u{0438}\u{0441}\u{0442} {$sheetLabel}: \u{043D}\u{0435}\u{0432}\u{0435}\u{0440}\u{043D}\u{044B}\u{0439} \u{043F}\u{043E}\u{0440}\u{044F}\u{0434}\u{043E}\u{043A} \u{0438}\u{043B}\u{0438} \u{043D}\u{0430}\u{0437}\u{0432}\u{0430}\u{043D}\u{0438}\u{0435} \u{043A}\u{043E}\u{043B}\u{043E}\u{043D}\u{043E}\u{043A}.";
        }
    }

private function loadLookups(int $tenantId, int $companyId): array
    {
        return [
            'product_types' => ProductType::query()->pluck('id')->all(),
            'product_kinds' => ProductKind::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->all(),
            'product_units' => ProductUnit::query()->pluck('id')->all(),
            'product_categories' => ProductCategory::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->all(),
            'product_subcategories' => ProductSubcategory::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->all(),
            'product_brands' => ProductBrand::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->pluck('id')
                ->all(),
        ];
    }

    private function loadAttributeDefinitions(int $tenantId, int $companyId): Collection
    {
        return ProductAttributeDefinition::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->get();
    }

    private function buildProductPayload(array $row, int $tenantId, int $companyId, ?int $userId, array &$errors, int $rowNumber, array $lookups): ?array
    {
        $scu = $this->normalizeText($row['scu'] ?? null);
        $name = $this->normalizeText($row['name'] ?? null);

        $payload = [
            'scu' => $scu,
            'name' => $name,
            'product_type_id' => $this->parseInt($row['product_type_id'] ?? null),
            'product_kind_id' => $this->parseInt($row['product_kind_id'] ?? null),
            'unit_id' => $this->parseInt($row['unit_id'] ?? null),
            'category_id' => $this->parseInt($row['category_id'] ?? null),
            'sub_category_id' => $this->parseInt($row['subcategory_id'] ?? null),
            'brand_id' => $this->parseInt($row['brand_id'] ?? null),
            'is_visible' => $this->parseBool($row['is_visible'] ?? null),
            'is_top' => $this->parseBool($row['is_top'] ?? null),
            'is_new' => $this->parseBool($row['is_new'] ?? null),
            'price' => $this->parseNumber($row['price'] ?? null),
            'price_sale' => $this->parseNumber($row['price_sale'] ?? null),
            'price_vendor' => $this->parseNumber($row['price_vendor'] ?? null),
            'price_vendor_min' => $this->parseNumber($row['price_vendor_min'] ?? null),
            'price_zakup' => $this->parseNumber($row['price_zakup'] ?? null),
            'price_delivery' => $this->parseNumber($row['price_delivery'] ?? null),
            'montaj' => $this->parseNumber($row['montaj'] ?? null),
            'montaj_sebest' => $this->parseNumber($row['montaj_sebest'] ?? null),
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
        ];

        if ($userId) {
            $payload['updated_by'] = $userId;
        }

        $numericFields = [
            'product_type_id',
            'product_kind_id',
            'unit_id',
            'category_id',
            'sub_category_id',
            'brand_id',
            'price',
            'price_sale',
            'price_vendor',
            'price_vendor_min',
            'price_zakup',
            'price_delivery',
            'montaj',
            'montaj_sebest',
        ];
        foreach ($numericFields as $field) {
            if ($payload[$field] === null) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: поле {$field} должно быть числом.";
            }
        }

        if ($payload['is_visible'] === null || $payload['is_top'] === null || $payload['is_new'] === null) {
            $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: поля is_visible/is_top/is_new должны быть 0 или 1.";
        }

        $lookupMap = [
            'product_type_id' => 'product_types',
            'product_kind_id' => 'product_kinds',
            'unit_id' => 'product_units',
            'category_id' => 'product_categories',
            'sub_category_id' => 'product_subcategories',
            'brand_id' => 'product_brands',
        ];
        foreach ($lookupMap as $field => $listName) {
            $value = $payload[$field];
            if ($value === null) {
                continue;
            }
            if (!in_array($value, $lookups[$listName] ?? [], true)) {
                $errors[] = "{$this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber)}: {$field}={$value} не найден.";
            }
        }

        if ($errors) {
            return null;
        }

        return $payload;
    }

        private function hasWorkDetails(array $row): bool
    {
        $fields = [
            'work_name',
            'work_product_type_id',
            'work_category_id',
            'work_price',
            'work_price_sale',
            'work_price_vendor',
            'work_price_vendor_min',
            'work_price_zakup',
        ];

        foreach ($fields as $field) {
            if (!$this->isMissing($row[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function buildWorkPayload(
        array $row,
        array $basePayload,
        int $tenantId,
        int $companyId,
        ?int $userId,
        array &$errors,
        int $rowNumber,
        array $lookups
    ): ?array {
        $workScu = $this->normalizeText($row['work_scu'] ?? $row['installation_work_scu'] ?? null);
        $workName = $this->normalizeText($row['work_name'] ?? null);
        $workTypeId = $this->parseInt($row['work_product_type_id'] ?? null);
        $workCategoryId = $this->parseInt($row['work_category_id'] ?? null);
        $workPrice = $this->parseNumber($row['work_price'] ?? null);
        $workPriceSale = $this->parseNumber($row['work_price_sale'] ?? null);
        $workPriceVendor = $this->parseNumber($row['work_price_vendor'] ?? null);
        $workPriceVendorMin = $this->parseNumber($row['work_price_vendor_min'] ?? null);
        $workPriceZakup = $this->parseNumber($row['work_price_zakup'] ?? null);

        $rowLabel = $this->sheetRowLabel(PricebookSchema::KEY_PRODUCTS, $rowNumber);
        $hasError = false;
        $required = [
            'work_name' => $workName,
            'work_product_type_id' => $workTypeId,
            'work_category_id' => $workCategoryId,
            'work_price' => $workPrice,
            'work_price_sale' => $workPriceSale,
            'work_price_vendor' => $workPriceVendor,
            'work_price_vendor_min' => $workPriceVendorMin,
            'work_price_zakup' => $workPriceZakup,
        ];

        foreach ($required as $field => $value) {
            if ($value === null || (is_string($value) && trim($value) === '')) {
                $errors[] = "{$rowLabel}: \u{043F}\u{043E}\u{043B}\u{0435} {$field} \u{043E}\u{0431}\u{044F}\u{0437}\u{0430}\u{0442}\u{0435}\u{043B}\u{044C}\u{043D}\u{043E} \u{0434}\u{043B}\u{044F} \u{0440}\u{0430}\u{0431}\u{043E}\u{0442}\u{044B}.";
                $hasError = true;
            }
        }

        if ($workTypeId !== null && !in_array($workTypeId, $lookups['product_types'] ?? [], true)) {
            $errors[] = "{$rowLabel}: work_product_type_id={$workTypeId} \u{043D}\u{0435} \u{043D}\u{0430}\u{0439}\u{0434}\u{0435}\u{043D}.";
            $hasError = true;
        }

        if ($workCategoryId !== null && !in_array($workCategoryId, $lookups['product_categories'] ?? [], true)) {
            $errors[] = "{$rowLabel}: work_category_id={$workCategoryId} \u{043D}\u{0435} \u{043D}\u{0430}\u{0439}\u{0434}\u{0435}\u{043D}.";
            $hasError = true;
        }

        if ($hasError) {
            return null;
        }

        $payload = [
            'scu' => $workScu,
            'name' => $workName,
            'product_type_id' => $workTypeId,
            'category_id' => $workCategoryId,
            'price' => $workPrice,
            'price_sale' => $workPriceSale,
            'price_vendor' => $workPriceVendor,
            'price_vendor_min' => $workPriceVendorMin,
            'price_zakup' => $workPriceZakup,
            'work_kind' => 'installation_linked',
        ];

        if ($userId) {
            $payload['updated_by'] = $userId;
        }

        $payload['__defaults'] = [
            'product_kind_id' => $basePayload['product_kind_id'] ?? null,
            'unit_id' => $basePayload['unit_id'] ?? null,
            'sub_category_id' => $basePayload['sub_category_id'] ?? null,
            'brand_id' => $basePayload['brand_id'] ?? null,
            'is_visible' => true,
            'is_top' => false,
            'is_new' => false,
            'price_delivery' => 0,
            'montaj' => 0,
            'montaj_sebest' => $workPriceZakup ?? 0,
        ];

        return $payload;
    }

private function findDefinition(Collection $definitions, string $name, ?int $productKindId): ?ProductAttributeDefinition
    {
        $normalized = $this->normalizeHeader($name);
        $matches = $definitions->filter(function (ProductAttributeDefinition $definition) use ($normalized) {
            return $this->normalizeHeader($definition->name ?? '') === $normalized;
        });

        if ($matches->isEmpty()) {
            return null;
        }

        if ($productKindId !== null) {
            $byKind = $matches->firstWhere('product_kind_id', $productKindId);
            if ($byKind) {
                return $byKind;
            }
        }

        return $matches->first();
    }

    private function syncInstallationWorkPrice(int $tenantId, int $companyId, string $installationScu, float $montajSebest, ?int $userId, $timestamp): void
    {
        $update = [
            'price_zakup' => $montajSebest,
            'work_kind' => 'installation_linked',
            'updated_at' => $timestamp,
        ];

        if ($userId) {
            $update['updated_by'] = $userId;
        }

        Product::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('scu', $installationScu)
            ->update($update);
    }

    private function syncOperationalPrices(int $tenantId, int $companyId, Product $product, ?int $userId): void
    {
        app(PriceWriterService::class)->upsertPrices(
            tenantId: $tenantId,
            companyId: $companyId,
            productId: (int) $product->id,
            fields: [
                'price' => $product->price,
                'price_sale' => $product->price_sale,
                'price_delivery' => $product->price_delivery,
                'montaj' => $product->montaj,
                'montaj_sebest' => $product->montaj_sebest,
            ],
            userId: $userId,
            syncLegacy: false,
        );
    }

    /**
     * @return array<int, array{name: string, rows: array}>
     */
    private function buildExportSheets(int $tenantId, int $companyId): array
    {
        $products = Product::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereNull('archived_at')
            ->with(['description', 'relations.relatedProduct', 'attributeValues.definition', 'media'])
            ->orderBy('scu')
            ->get();

        $relationGroups = $products->pluck('relations')->flatten(1)->groupBy('product_id');

        $productRows = [];
        $productRows[] = PricebookSchema::productsColumns();
        foreach ($products as $product) {
            $relations = $relationGroups->get($product->id, collect());
            $workProduct = $relations
                ->firstWhere('relation_type', 'INSTALLATION_WORK')
                ?->relatedProduct;
            $relatedScu = $relations
                ->where('relation_type', 'RELATED')
                ->map(fn ($relation) => $relation->relatedProduct?->scu)
                ->filter()
                ->unique()
                ->implode(', ');

            $productRows[] = [
                'UPDATE',
                $product->scu,
                $product->name,
                $product->product_type_id,
                $product->product_kind_id,
                $product->unit_id,
                $product->category_id,
                $product->sub_category_id,
                $product->brand_id,
                $product->is_visible ? 1 : 0,
                $product->is_top ? 1 : 0,
                $product->is_new ? 1 : 0,
                $product->price,
                $product->price_sale,
                $product->price_vendor,
                $product->price_vendor_min,
                $product->price_zakup,
                $product->price_delivery,
                $product->montaj,
                $product->montaj_sebest,
                $relatedScu,
                $workProduct?->scu,
                $workProduct?->name,
                $workProduct?->product_type_id,
                $workProduct?->category_id,
                $workProduct?->price,
                $workProduct?->price_sale,
                $workProduct?->price_vendor,
                $workProduct?->price_vendor_min,
                $workProduct?->price_zakup,
            ];
        }

        $descriptionRows = [];
        $descriptionRows[] = PricebookSchema::descriptionsColumns();
        foreach ($products as $product) {
            $desc = $product->description;
            $descriptionRows[] = [
                $product->scu,
                $product->name,
                $desc?->description_short ?? '',
                $desc?->description_long ?? '',
                $desc?->dignities ?? '',
                $desc?->constructive ?? '',
                $desc?->avito1 ?? '',
                $desc?->avito2 ?? '',
            ];
        }

        $attributeRows = [];
        $attributeRows[] = PricebookSchema::attributesColumns();
        foreach ($products as $product) {
            foreach ($product->attributeValues as $value) {
                $attributeRows[] = [
                    $product->scu,
                    $value->definition?->name ?? '',
                    $value->value_string,
                    $value->value_number,
                ];
            }
        }

        $mediaRows = [];
        $mediaRows[] = PricebookSchema::mediaColumns();
        foreach ($products as $product) {
            foreach ($product->media as $media) {
                $mediaRows[] = [
                    $product->scu,
                    $media->type,
                    $media->url ?? $media->path ?? '',
                    $media->sort_order,
                ];
            }
        }

        $lookupRows = $this->buildLookupRows();

        return [
            ['name' => PricebookSchema::SHEET_PRODUCTS, 'rows' => $productRows],
            ['name' => PricebookSchema::SHEET_DESCRIPTIONS, 'rows' => $descriptionRows],
            ['name' => PricebookSchema::SHEET_ATTRIBUTES, 'rows' => $attributeRows],
            ['name' => PricebookSchema::SHEET_MEDIA, 'rows' => $mediaRows],
            ['name' => PricebookSchema::SHEET_LOOKUPS, 'rows' => $lookupRows],
        ];
    }

    /**
     * @return array<int, array{name: string, rows: array}>
     */
    private function buildTemplateSheets(int $tenantId, int $companyId): array
    {
        return [
            ['name' => PricebookSchema::SHEET_PRODUCTS, 'rows' => [PricebookSchema::productsColumns()]],
            ['name' => PricebookSchema::SHEET_DESCRIPTIONS, 'rows' => [PricebookSchema::descriptionsColumns()]],
            ['name' => PricebookSchema::SHEET_ATTRIBUTES, 'rows' => [PricebookSchema::attributesColumns()]],
            ['name' => PricebookSchema::SHEET_MEDIA, 'rows' => [PricebookSchema::mediaColumns()]],
            ['name' => PricebookSchema::SHEET_LOOKUPS, 'rows' => $this->buildLookupRows()],
        ];
    }

    /**
     * @param array<string, array> $data
     * @return array{0: array, 1: array}
     */
    private function splitSheet(array $data, string $sheetKey): array
    {
        $rows = $data[$sheetKey] ?? [];
        $rows = array_values($rows);

        if (!$rows) {
            $label = PricebookSchema::sheetLabel($sheetKey);
            throw new RuntimeException("\u{041B}\u{0438}\u{0441}\u{0442} {$label} \u{043D}\u{0435} \u{043D}\u{0430}\u{0439}\u{0434}\u{0435}\u{043D} \u{0438}\u{043B}\u{0438} \u{043F}\u{0443}\u{0441}\u{0442}.");
        }

        $headers = array_map(fn ($value) => trim((string) $value), $rows[0] ?? []);
        $body = array_slice($rows, 1);

        return [$headers, $body];
    }

private function isValidXlsx(string $path): bool
    {
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }
        $zip = new \ZipArchive();
        $result = $zip->open($path);
        if ($result === true) {
            $zip->close();
            return true;
        }
        return false;
    }

    private function normalizeHeader(?string $value): string
    {
        return Str::lower(trim((string) $value));
    }

    private function normalizeText(?string $value): string
    {
        return trim((string) $value);
    }

    private function parseNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $raw = str_replace(["\u{00A0}", ' '], '', (string) $value);
        if ($raw === '') {
            return null;
        }
        if (str_contains($raw, ',') && !str_contains($raw, '.')) {
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }
        if (!is_numeric($raw)) {
            return null;
        }
        return (float) $raw;
    }

    private function parseInt($value): ?int
    {
        $number = $this->parseNumber($value);
        if ($number === null) {
            return null;
        }
        return (int) round($number);
    }

    private function parseBool($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = Str::lower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'yes', 'y', 'да'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'no', 'n', 'нет'], true)) {
            return false;
        }
        return null;
    }

    private function mapRow(array $headers, array $row): array
    {
        $mapped = [];
        foreach ($headers as $index => $header) {
            if ($header === null || $header === '') {
                continue;
            }
            $mapped[$header] = $row[$index] ?? null;
        }
        return $mapped;
    }

private function splitList($value): array
    {
        $text = $this->normalizeText($value);
        if ($text === '') {
            return [];
        }
        $parts = preg_split('/[;,]+/', $text) ?: [];
        $list = [];
        foreach ($parts as $part) {
            $clean = $this->normalizeText($part);
            if ($clean !== '') {
                $list[] = $clean;
            }
        }
        return array_values(array_unique($list));
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function isMissing($value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function sheetRowLabel(string $sheetKey, int $rowNumber): string
    {
        $sheet = PricebookSchema::sheetLabel($sheetKey);
        if ($rowNumber <= 0) {
            return "\u{041B}\u{0438}\u{0441}\u{0442} {$sheet}";
        }
        return "\u{041B}\u{0438}\u{0441}\u{0442} {$sheet}, \u{0441}\u{0442}\u{0440}\u{043E}\u{043A}\u{0430} {$rowNumber}";
    }
}
