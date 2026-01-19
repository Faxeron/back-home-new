<?php

namespace App\Domain\CRM\Services;

use App\Domain\Common\Models\City;
use App\Domain\Common\Models\Company;
use App\Domain\CRM\Models\Contract;
use App\Domain\CRM\Models\ContractDocument;
use App\Domain\CRM\Models\ContractTemplate;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;
use RuntimeException;

class ContractDocumentService
{
    public function generate(
        Contract $contract,
        ?ContractTemplate $template,
        ?ContractDocument $document = null,
        ?int $userId = null,
    ): ContractDocument
    {
        $contract->loadMissing([
            'counterparty.individual',
            'counterparty.company',
            'items.product.unit',
            'template',
            'group',
            'saleType',
        ]);

        $template = $template ?? $document?->template ?? $contract->template;
        if (!$template) {
            throw new RuntimeException('Шаблон договора не найден.');
        }

        if (!$template->docx_template_path) {
            throw new RuntimeException('Для шаблона не указан DOCX файл.');
        }

        $templatePath = Storage::disk('local')->path($template->docx_template_path);
        if (!is_file($templatePath)) {
            throw new RuntimeException('DOCX файл шаблона не найден.');
        }

        $template->loadMissing('productTypes');

        $documentType = $this->resolveDocumentType($template, $document);
        $numberSuffix = $document?->number_suffix ?? $this->resolveNumberSuffix($documentType);

        $items = $contract->items->sortBy(fn ($item) => $item->sort_order ?? 100)->values();
        if ($template->productTypes->isNotEmpty()) {
            $typeIds = $template->productTypes->pluck('id')->map(fn ($id) => (int) $id)->all();
            $items = $items->filter(function ($item) use ($typeIds) {
                $typeId = (int) ($item->product_type_id ?? $item->product?->product_type_id ?? 0);
                return $typeId && in_array($typeId, $typeIds, true);
            })->values();
        }

        $values = $this->buildPlaceholderMap($contract, $template, $items, $documentType, $numberSuffix);

        $processor = new TemplateProcessor($templatePath);
        foreach ($values as $key => $value) {
            $processor->setValue($key, $value ?? '');
        }

        $this->applyItemsTable($processor, $items);

        $versionQuery = ContractDocument::query()
            ->where('contract_id', $contract->id);
        if ($documentType) {
            $versionQuery->where('document_type', $documentType);
        }
        $version = (int) $versionQuery->max('version');
        $version = $version > 0 ? $version + 1 : 1;

        $directory = $this->buildDocumentDirectory($contract);
        Storage::disk('local')->makeDirectory($directory);

        $fileName = sprintf(
            'contract-%s-v%s-%s-%s.docx',
            $contract->id,
            $version,
            now()->format('YmdHis'),
            Str::random(6),
        );
        $relativePath = $directory . '/' . $fileName;
        $fullPath = Storage::disk('local')->path($relativePath);
        $processor->saveAs($fullPath);

        $currentQuery = ContractDocument::query()
            ->where('contract_id', $contract->id);
        if ($documentType) {
            $currentQuery->where('document_type', $documentType);
        }
        $currentQuery->update(['is_current' => false]);

        $payload = [
            'tenant_id' => $contract->tenant_id,
            'company_id' => $contract->company_id,
            'contract_id' => $contract->id,
            'template_id' => $template->id,
            'document_type' => $documentType,
            'number_suffix' => $numberSuffix,
            'file_path' => $relativePath,
            'version' => $version,
            'is_current' => true,
            'generated_at' => now(),
            'created_by' => $userId,
        ];

        if ($document) {
            $document->fill($payload);
            $document->save();
            $document->load('template');

            return $document;
        }

        return ContractDocument::query()->create($payload);
    }

    private function buildDocumentDirectory(Contract $contract): string
    {
        $tenantId = $contract->tenant_id ?? 0;
        $companyId = $contract->company_id ?? 0;

        return "contracts/documents/tenant_{$tenantId}/company_{$companyId}/contract_{$contract->id}";
    }

    private function buildPlaceholderMap(
        Contract $contract,
        ContractTemplate $template,
        Collection $items,
        ?string $documentType = null,
        string $numberSuffix = '',
    ): array {
        $group = $contract->group;
        $counterparty = $contract->counterparty;
        $individual = $counterparty?->individual;
        $company = $counterparty?->company;
        $seller = $contract->company_id ? Company::query()->find($contract->company_id) : null;
        $city = $group?->city_id ? City::query()->find($group->city_id) : null;

        $totalAmount = $contract->total_amount ?? $group?->total_amount ?? $items->sum('total');
        $contractDate = $contract->contract_date ?? $group?->contract_date;
        $installDate = $group?->installation_date;
        $workStartDate = $contract->work_start_date ?? $installDate;
        $workEndDate = $contract->work_end_date ?? null;
        $totalsByType = $this->buildTotalsByType($items);
        $advanceAmount = $this->resolveAdvanceAmount($template, $items, $totalAmount);
        $remainingAmount = max(0, (float) $totalAmount - $advanceAmount);
        $clientShortName = $this->formatClientShortName($individual, $company, $counterparty);
        $baseNumber = (string) ($contract->number ?? $contract->id);
        $fullNumber = $baseNumber . $numberSuffix;

        return [
            'contract_id' => (string) $contract->id,
            'contract_number' => $fullNumber,
            'contract_number_base' => $baseNumber,
            'contract_number_suffix' => $numberSuffix,
            'document_type' => $documentType ?? '',
            'contract_date' => $this->formatDate($contractDate),
            'installation_date' => $this->formatDate($installDate),
            'work_start_date' => $this->formatDate($workStartDate),
            'work_end_date' => $this->formatDate($workEndDate),
            'city' => $city?->name ?? '',
            'site_address' => $group?->site_address ?? $contract->address ?? '',
            'sale_type' => $contract->saleType?->name ?? '',
            'template_name' => $template->name ?? '',
            'template_short_name' => $template->short_name ?? '',
            'total_sum' => $this->formatMoney($totalAmount),
            'total_sum_raw' => $this->formatNumber($totalAmount, 2),
            'total_sum_words' => $this->formatMoneyWords($totalAmount),
            'items_count' => (string) $items->count(),
            'total_products' => $this->formatMoney($totalsByType['products']),
            'total_materials' => $this->formatMoney($totalsByType['materials']),
            'total_works' => $this->formatMoney($totalsByType['works']),
            'total_services' => $this->formatMoney($totalsByType['services']),
            'total_transport' => $this->formatMoney($totalsByType['transport']),
            'total_subcontracts' => $this->formatMoney($totalsByType['subcontracts']),
            'advance_sum' => $this->formatMoney($advanceAmount),
            'advance_sum_raw' => $this->formatNumber($advanceAmount, 2),
            'advance_sum_words' => $this->formatMoneyWords($advanceAmount),
            'remaining_sum' => $this->formatMoney($remainingAmount),
            'remaining_sum_raw' => $this->formatNumber($remainingAmount, 2),
            'remaining_sum_words' => $this->formatMoneyWords($remainingAmount),

            'client_type' => $group?->counterparty_type ?? '',
            'client_name' => $counterparty?->name ?? '',
            'client_short_name' => $clientShortName,
            'client_phone' => $counterparty?->phone ?? '',
            'client_email' => $counterparty?->email ?? '',

            'client_first_name' => $individual?->first_name ?? '',
            'client_last_name' => $individual?->last_name ?? '',
            'client_patronymic' => $individual?->patronymic ?? '',
            'client_full_name' => trim(implode(' ', array_filter([
                $individual?->last_name,
                $individual?->first_name,
                $individual?->patronymic,
            ]))),
            'client_passport_series' => $individual?->passport_series ?? '',
            'client_passport_number' => $individual?->passport_number ?? '',
            'client_passport_code' => $individual?->passport_code ?? '',
            'client_passport_whom' => $individual?->passport_whom ?? '',
            'client_passport_issued_at' => $this->formatDate($individual?->issued_at ?? null),
            'client_passport_issued_by' => $individual?->issued_by ?? '',
            'client_passport_address' => $individual?->passport_address ?? '',

            'client_legal_name' => $company?->legal_name ?? '',
            'client_short_name' => $company?->short_name ?? '',
            'client_inn' => $company?->inn ?? '',
            'client_kpp' => $company?->kpp ?? '',
            'client_ogrn' => $company?->ogrn ?? '',
            'client_legal_address' => $company?->legal_address ?? '',
            'client_postal_address' => $company?->postal_address ?? '',
            'client_director_name' => $company?->director_name ?? '',
            'client_accountant_name' => $company?->accountant_name ?? '',
            'client_bank_name' => $company?->bank_name ?? '',
            'client_bik' => $company?->bik ?? '',
            'client_account_number' => $company?->account_number ?? '',
            'client_correspondent_account' => $company?->correspondent_account ?? '',

            'seller_name' => $seller?->name ?? '',
            'seller_short_name' => $seller?->short_name ?? '',
            'seller_inn' => $seller?->inn ?? '',
            'seller_kpp' => $seller?->kpp ?? '',
            'seller_ogrn' => $seller?->ogrn ?? '',
            'seller_legal_address' => $seller?->legal_address ?? '',
            'seller_postal_address' => $seller?->postal_address ?? '',
            'seller_director_name' => $seller?->director_name ?? '',
            'seller_accountant_name' => $seller?->accountant_name ?? '',
            'seller_bank_name' => $seller?->bank_name ?? '',
            'seller_bik' => $seller?->bik ?? '',
            'seller_account_number' => $seller?->account_number ?? '',
            'seller_correspondent_account' => $seller?->correspondent_account ?? '',
        ];
    }

    private function applyItemsTable(TemplateProcessor $processor, Collection $items): void
    {
        if ($items->isEmpty()) {
            $processor->setValue('item_index', '');
            $processor->setValue('item_scu', '');
            $processor->setValue('item_name', '');
            $processor->setValue('item_qty', '');
            $processor->setValue('item_unit', '');
            $processor->setValue('item_price', '');
            $processor->setValue('item_sum', '');
            $processor->setValue('item_total', '');
            $processor->setValue('item_amount', '');
            $processor->setValue('item_group', '');
            return;
        }

        $processor->cloneRow('item_name', $items->count() + 1);

        $index = 1;
        $totalSum = 0.0;
        foreach ($items as $item) {
            $processor->setValue("item_index#{$index}", (string) $index);
            $processor->setValue("item_scu#{$index}", $item->scu ?? $item->product?->scu ?? '');
            $processor->setValue("item_name#{$index}", $item->name ?? $item->product?->name ?? '');
            $processor->setValue("item_qty#{$index}", $this->formatQty($item->qty));
            $processor->setValue("item_unit#{$index}", $item->product?->unit?->name ?? '');
            $processor->setValue("item_price#{$index}", $this->formatMoney($item->price));
            $rawSum = (float) ($item->total ?? ((float) $item->qty * (float) $item->price));
            $sum = $this->formatMoney($rawSum);
            $processor->setValue("item_sum#{$index}", $sum);
            $processor->setValue("item_total#{$index}", $sum);
            $processor->setValue("item_amount#{$index}", $sum);
            $processor->setValue("item_group#{$index}", $item->group_name ?? '');
            $totalSum += $rawSum;
            $index++;
        }

        $totalIndex = $index;
        $totalFormatted = $this->formatMoney($totalSum);
        $processor->setValue("item_index#{$totalIndex}", '');
        $processor->setValue("item_scu#{$totalIndex}", '');
        $processor->setValue("item_name#{$totalIndex}", "Итого");
        $processor->setValue("item_qty#{$totalIndex}", '');
        $processor->setValue("item_unit#{$totalIndex}", '');
        $processor->setValue("item_price#{$totalIndex}", '');
        $processor->setValue("item_sum#{$totalIndex}", $totalFormatted);
        $processor->setValue("item_total#{$totalIndex}", $totalFormatted);
        $processor->setValue("item_amount#{$totalIndex}", $totalFormatted);
        $processor->setValue("item_group#{$totalIndex}", '');
    }

    private function formatDate($value): string
    {
        if (!$value) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d.m.Y');
        }

        if (is_string($value)) {
            try {
                return Carbon::parse($value)->format('d.m.Y');
            } catch (\Throwable $e) {
                return $value;
            }
        }

        return (string) $value;
    }

    private function buildTotalsByType(Collection $items): array
    {
        $totals = [
            'materials' => 0.0,
            'products' => 0.0,
            'works' => 0.0,
            'services' => 0.0,
            'transport' => 0.0,
            'subcontracts' => 0.0,
        ];

        foreach ($items as $item) {
            $typeId = (int) ($item->product_type_id ?? $item->product?->product_type_id ?? 0);
            $sum = (float) ($item->total ?? ((float) $item->qty * (float) $item->price));

            switch ($typeId) {
                case 1:
                    $totals['materials'] += $sum;
                    break;
                case 2:
                    $totals['products'] += $sum;
                    break;
                case 3:
                    $totals['works'] += $sum;
                    break;
                case 4:
                    $totals['services'] += $sum;
                    break;
                case 5:
                    $totals['transport'] += $sum;
                    break;
                case 6:
                    $totals['subcontracts'] += $sum;
                    break;
                default:
                    break;
            }
        }

        return $totals;
    }

    private function resolveAdvanceAmount(ContractTemplate $template, Collection $items, float $totalAmount): float
    {
        $mode = data_get($template, 'advance_mode');
        if ($mode === 'none') {
            return 0.0;
        }

        $advancePercent = data_get($template, 'advance_percent');
        if (($mode === 'percent' || !$mode) && is_numeric($advancePercent)) {
            return round($totalAmount * ((float) $advancePercent / 100), 2);
        }

        $advanceTypeIds = data_get($template, 'advance_product_type_ids');
        if ($mode && $mode !== 'product_types') {
            $advanceTypeIds = null;
        }
        if (is_string($advanceTypeIds)) {
            $decoded = json_decode($advanceTypeIds, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $advanceTypeIds = $decoded;
            }
        }

        if (is_array($advanceTypeIds) && $advanceTypeIds) {
            return (float) $items->filter(
                fn ($item) => in_array((int) ($item->product_type_id ?? $item->product?->product_type_id ?? 0), $advanceTypeIds, true)
            )->sum(function ($item) {
                return (float) ($item->total ?? ((float) $item->qty * (float) $item->price));
            });
        }

        return 0.0;
    }

    private function formatClientShortName($individual, $company, $counterparty): string
    {
        if ($individual) {
            $lastName = $individual->last_name ?? '';
            $first = $individual->first_name ?? '';
            $patronymic = $individual->patronymic ?? '';
            $initials = trim(implode('', array_filter([
                $first ? mb_substr($first, 0, 1) . '.' : '',
                $patronymic ? mb_substr($patronymic, 0, 1) . '.' : '',
            ])));
            return trim($lastName . ' ' . $initials);
        }

        if ($company) {
            return $company->short_name ?? $company->legal_name ?? '';
        }

        return $counterparty?->name ?? '';
    }

    private function resolveDocumentType(?ContractTemplate $template, ?ContractDocument $document): string
    {
        if ($document?->document_type) {
            return $document->document_type;
        }

        if ($template?->document_type) {
            return $template->document_type;
        }

        return 'combined';
    }

    private function resolveNumberSuffix(string $documentType): string
    {
        return match ($documentType) {
            'supply' => '-п',
            'install' => '-м',
            default => '',
        };
    }

    private function formatMoney($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, 2, '.', ' ');
    }

    private function formatNumber($value, int $decimals = 2): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float) $value, $decimals, '.', '');
    }

    private function formatMoneyWords($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = round((float) $value, 2);
        $rubles = (int) floor($number);
        $kopeks = (int) round(($number - $rubles) * 100);

        if ($kopeks === 100) {
            $rubles += 1;
            $kopeks = 0;
        }

        $words = $this->numberToWordsRu($rubles, ['рубль', 'рубля', 'рублей']);
        $kopeksWord = $this->pluralizeRu($kopeks, 'копейка', 'копейки', 'копеек');

        return trim(sprintf('%s %02d %s', $words, $kopeks, $kopeksWord));
    }

    private function numberToWordsRu(int $number, array $forms): string
    {
        if ($number === 0) {
            return 'ноль ' . $forms[2];
        }

        $units = [
            ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
            ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
        ];
        $teens = ['десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать'];
        $tens = ['', '', 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто'];
        $hundreds = ['', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот'];

        $orders = [
            ['form' => $forms, 'gender' => 0],
            ['form' => ['тысяча', 'тысячи', 'тысяч'], 'gender' => 1],
            ['form' => ['миллион', 'миллиона', 'миллионов'], 'gender' => 0],
            ['form' => ['миллиард', 'миллиарда', 'миллиардов'], 'gender' => 0],
        ];

        $parts = [];
        $orderIndex = 0;
        $remaining = $number;

        while ($remaining > 0 && $orderIndex < count($orders)) {
            $chunk = $remaining % 1000;
            if ($chunk > 0) {
                $chunkWords = [];
                $chunkWords[] = $hundreds[intdiv($chunk, 100)];

                $rest = $chunk % 100;
                if ($rest >= 10 && $rest < 20) {
                    $chunkWords[] = $teens[$rest - 10];
                } else {
                    $chunkWords[] = $tens[intdiv($rest, 10)];
                    $unit = $rest % 10;
                    if ($unit > 0) {
                        $gender = $orders[$orderIndex]['gender'];
                        $chunkWords[] = $units[$gender][$unit];
                    }
                }

                $chunkWords = array_filter($chunkWords, fn ($word) => $word !== '');
                $form = $this->pluralizeRu($chunk, ...$orders[$orderIndex]['form']);
                $chunkWords[] = $form;

                $parts[] = implode(' ', $chunkWords);
            }

            $remaining = intdiv($remaining, 1000);
            $orderIndex++;
        }

        return implode(' ', array_reverse($parts));
    }
    private function pluralizeRu(int $number, string $one, string $few, string $many): string
    {
        $mod100 = $number % 100;
        if ($mod100 >= 11 && $mod100 <= 19) {
            return $many;
        }
        $mod10 = $number % 10;
        if ($mod10 === 1) {
            return $one;
        }
        if ($mod10 >= 2 && $mod10 <= 4) {
            return $few;
        }
        return $many;
    }

    private function formatQty($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatted = number_format((float) $value, 3, '.', ' ');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted;
    }
}

