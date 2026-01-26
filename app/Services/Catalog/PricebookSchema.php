<?php

declare(strict_types=1);

namespace App\Services\Catalog;

final class PricebookSchema
{
    public const KEY_PRODUCTS = 'products';
    public const KEY_DESCRIPTIONS = 'descriptions';
    public const KEY_ATTRIBUTES = 'attributes';
    public const KEY_MEDIA = 'media';
    public const KEY_LOOKUPS = 'lookups';

    public const SHEET_PRODUCTS = 'Товары';
    public const SHEET_DESCRIPTIONS = 'Описания';
    public const SHEET_ATTRIBUTES = 'Характеристики';
    public const SHEET_MEDIA = 'Медиа';
    public const SHEET_LOOKUPS = 'Справочники';

    public const SHEET_PRODUCTS_EN = 'Products';
    public const SHEET_DESCRIPTIONS_EN = 'Descriptions';
    public const SHEET_ATTRIBUTES_EN = 'Attributes';
    public const SHEET_MEDIA_EN = 'Media';
    public const SHEET_LOOKUPS_EN = 'Lookups';

    /**
     * @return array<string, string[]>
     */
    public static function sheetAliases(): array
    {
        return [
            self::KEY_PRODUCTS => [self::SHEET_PRODUCTS, self::SHEET_PRODUCTS_EN],
            self::KEY_DESCRIPTIONS => [self::SHEET_DESCRIPTIONS, self::SHEET_DESCRIPTIONS_EN],
            self::KEY_ATTRIBUTES => [self::SHEET_ATTRIBUTES, self::SHEET_ATTRIBUTES_EN],
            self::KEY_MEDIA => [self::SHEET_MEDIA, self::SHEET_MEDIA_EN],
            self::KEY_LOOKUPS => [self::SHEET_LOOKUPS, self::SHEET_LOOKUPS_EN],
        ];
    }

    public static function sheetLabel(string $key): string
    {
        return match ($key) {
            self::KEY_PRODUCTS => self::SHEET_PRODUCTS,
            self::KEY_DESCRIPTIONS => self::SHEET_DESCRIPTIONS,
            self::KEY_ATTRIBUTES => self::SHEET_ATTRIBUTES,
            self::KEY_MEDIA => self::SHEET_MEDIA,
            self::KEY_LOOKUPS => self::SHEET_LOOKUPS,
            default => $key,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function productsHeaderMap(): array
    {
        return [
            'action' => 'Действие',
            'scu' => 'SCU',
            'name' => 'Название',
            'product_type_id' => 'Тип товара ID',
            'product_kind_id' => 'Вид товара ID',
            'unit_id' => 'Ед. изм. ID',
            'category_id' => 'Категория ID',
            'subcategory_id' => 'Подкатегория ID',
            'brand_id' => 'Бренд ID',
            'is_visible' => 'Видимость (0/1)',
            'is_top' => 'ТОП (0/1)',
            'is_new' => 'Новинка (0/1)',
            'price' => 'Цена',
            'price_sale' => 'Цена акция',
            'price_vendor' => 'Цена производ.',
            'price_vendor_min' => 'Мин. цена произв.',
            'price_zakup' => 'Закуп',
            'price_delivery' => 'Доставка до города',
            'montaj' => 'Монтаж',
            'montaj_sebest' => 'Монтаж с/с',
            'related_scu' => 'Связанные SCU',
            'work_scu' => 'Работа SCU',
            'work_name' => 'Работа название',
            'work_product_type_id' => 'Работа тип товара ID',
            'work_category_id' => 'Работа категория ID',
            'work_price' => 'Работа цена',
            'work_price_sale' => 'Работа цена акция',
            'work_price_vendor' => 'Работа цена произв.',
            'work_price_vendor_min' => 'Работа мин. цена произв.',
            'work_price_zakup' => 'Работа закуп',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function productsColumns(): array
    {
        return array_values(self::productsHeaderMap());
    }

    /**
     * @return array<int, string>
     */
    public static function productsKeys(): array
    {
        return array_keys(self::productsHeaderMap());
    }

    /**
     * @return array<string, string>
     */
    public static function productsHeaderAliases(): array
    {
        return [
            'действие' => 'action',
            'action' => 'action',
            'scu' => 'scu',
            'sku' => 'scu',
            'код товара' => 'scu',
            'название' => 'name',
            'наименование' => 'name',
            'name' => 'name',
            'тип товара id' => 'product_type_id',
            'тип товара' => 'product_type_id',
            'product_type_id' => 'product_type_id',
            'вид товара id' => 'product_kind_id',
            'вид товара' => 'product_kind_id',
            'product_kind_id' => 'product_kind_id',
            'ед. изм. id' => 'unit_id',
            'ед изм id' => 'unit_id',
            'unit_id' => 'unit_id',
            'категория id' => 'category_id',
            'category_id' => 'category_id',
            'подкатегория id' => 'subcategory_id',
            'subcategory_id' => 'subcategory_id',
            'sub_category_id' => 'subcategory_id',
            'бренд id' => 'brand_id',
            'brand_id' => 'brand_id',
            'видимость (0/1)' => 'is_visible',
            'is_visible' => 'is_visible',
            'топ (0/1)' => 'is_top',
            'is_top' => 'is_top',
            'новинка (0/1)' => 'is_new',
            'is_new' => 'is_new',
            'цена' => 'price',
            'price' => 'price',
            'цена акция' => 'price_sale',
            'price_sale' => 'price_sale',
            'цена производ.' => 'price_vendor',
            'цена производ' => 'price_vendor',
            'price_vendor' => 'price_vendor',
            'мин. цена произв.' => 'price_vendor_min',
            'мин цена произв' => 'price_vendor_min',
            'price_vendor_min' => 'price_vendor_min',
            'закуп' => 'price_zakup',
            'price_zakup' => 'price_zakup',
            'доставка до города' => 'price_delivery',
            'price_delivery' => 'price_delivery',
            'монтаж' => 'montaj',
            'montaj' => 'montaj',
            'монтаж с/с' => 'montaj_sebest',
            'montaj_sebest' => 'montaj_sebest',
            'связанные scu' => 'related_scu',
            'related_scu' => 'related_scu',
            'работа scu' => 'work_scu',
            'work_scu' => 'work_scu',
            'installation_work_scu' => 'work_scu',
            'работа название' => 'work_name',
            'work_name' => 'work_name',
            'работа тип товара id' => 'work_product_type_id',
            'work_product_type_id' => 'work_product_type_id',
            'работа категория id' => 'work_category_id',
            'work_category_id' => 'work_category_id',
            'работа цена' => 'work_price',
            'work_price' => 'work_price',
            'работа цена акция' => 'work_price_sale',
            'work_price_sale' => 'work_price_sale',
            'работа цена произв.' => 'work_price_vendor',
            'работа цена производ' => 'work_price_vendor',
            'work_price_vendor' => 'work_price_vendor',
            'работа мин. цена произв.' => 'work_price_vendor_min',
            'work_price_vendor_min' => 'work_price_vendor_min',
            'работа закуп' => 'work_price_zakup',
            'work_price_zakup' => 'work_price_zakup',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function descriptionsHeaderMap(): array
    {
        return [
            'scu' => 'SCU',
            'name' => 'Название',
            'description_short' => 'Описание краткое',
            'description_long' => 'Описание полное',
            'dignities' => 'Достоинства',
            'constructive' => 'Конструктив',
            'avito1' => 'Avito 1',
            'avito2' => 'Avito 2',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function descriptionsColumns(): array
    {
        return array_values(self::descriptionsHeaderMap());
    }

    /**
     * @return array<int, string>
     */
    public static function descriptionsKeys(): array
    {
        return array_keys(self::descriptionsHeaderMap());
    }

    /**
     * @return array<string, string>
     */
    public static function descriptionsHeaderAliases(): array
    {
        return [
            'scu' => 'scu',
            'sku' => 'scu',
            'название' => 'name',
            'name' => 'name',
            'описание краткое' => 'description_short',
            'description_short' => 'description_short',
            'описание полное' => 'description_long',
            'description_long' => 'description_long',
            'достоинства' => 'dignities',
            'dignities' => 'dignities',
            'конструктив' => 'constructive',
            'constructive' => 'constructive',
            'avito 1' => 'avito1',
            'avito1' => 'avito1',
            'avito 2' => 'avito2',
            'avito2' => 'avito2',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function attributesHeaderMap(): array
    {
        return [
            'scu' => 'SCU',
            'attribute_name' => 'Атрибут',
            'value_string' => 'Значение (текст)',
            'value_number' => 'Значение (число)',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function attributesColumns(): array
    {
        return array_values(self::attributesHeaderMap());
    }

    /**
     * @return array<int, string>
     */
    public static function attributesKeys(): array
    {
        return array_keys(self::attributesHeaderMap());
    }

    /**
     * @return array<string, string>
     */
    public static function attributesHeaderAliases(): array
    {
        return [
            'scu' => 'scu',
            'sku' => 'scu',
            'атрибут' => 'attribute_name',
            'attribute_name' => 'attribute_name',
            'значение (текст)' => 'value_string',
            'value_string' => 'value_string',
            'значение (число)' => 'value_number',
            'value_number' => 'value_number',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function mediaHeaderMap(): array
    {
        return [
            'scu' => 'SCU',
            'type' => 'Тип',
            'path' => 'Путь',
            'sort_order' => 'Порядок',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function mediaColumns(): array
    {
        return array_values(self::mediaHeaderMap());
    }

    /**
     * @return array<int, string>
     */
    public static function mediaKeys(): array
    {
        return array_keys(self::mediaHeaderMap());
    }

    /**
     * @return array<string, string>
     */
    public static function mediaHeaderAliases(): array
    {
        return [
            'scu' => 'scu',
            'sku' => 'scu',
            'тип' => 'type',
            'type' => 'type',
            'путь' => 'path',
            'path' => 'path',
            'порядок' => 'sort_order',
            'sort_order' => 'sort_order',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function lookupsColumns(): array
    {
        return ['Справочник', 'ID', 'Название'];
    }
}
