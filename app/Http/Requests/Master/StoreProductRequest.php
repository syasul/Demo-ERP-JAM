<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product.manage') ?? false;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'sku' => ['nullable', 'string', 'max:50', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:50', 'unique:products,barcode'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'cost_price' => ['required', 'integer', 'min:0'],
            'sell_price' => ['required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'track_batch' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string'],
        ];
    }
}
