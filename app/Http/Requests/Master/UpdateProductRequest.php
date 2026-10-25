<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('product.manage') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($product->id)],
            'barcode' => ['nullable', 'string', 'max:50', Rule::unique('products', 'barcode')->ignore($product->id)],
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
