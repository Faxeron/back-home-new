<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'scu' => ['sometimes', 'string', 'max:255'],
            'product_type_id' => ['sometimes', 'integer', Rule::exists('legacy_new.product_types', 'id')],
            'product_kind_id' => ['sometimes', 'nullable', 'integer', Rule::exists('legacy_new.product_kinds', 'id')],
            'category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('legacy_new.product_categories', 'id')],
            'sub_category_id' => ['sometimes', 'nullable', 'integer', Rule::exists('legacy_new.product_subcategories', 'id')],
            'brand_id' => ['sometimes', 'nullable', 'integer', Rule::exists('legacy_new.product_brands', 'id')],
            'unit_id' => ['sometimes', 'nullable', 'integer', Rule::exists('legacy_new.product_units', 'id')],
            'sort_order' => ['sometimes', 'integer'],
            'price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_sale' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_vendor' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_vendor_min' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_zakup' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'price_delivery' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'montaj' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'montaj_sebest' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_top' => ['sometimes', 'boolean'],
            'is_new' => ['sometimes', 'boolean'],
        ];
    }
}
