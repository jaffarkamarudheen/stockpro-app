<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productId = $this->route('product') ? ($this->route('product')->id ?? $this->route('product')) : null;

        return [
            'product_number' => ['required', 'string', 'max:100', 'unique:products,product_number,'.$productId],
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            'photo_url_input' => ['nullable', 'url', 'max:2048'],
            'quality' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'purchase_rate' => ['required', 'numeric', 'min:0'],
            'sale_rate' => ['required', 'numeric', 'min:0'],
            'other_rate' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
        ];
    }
}
