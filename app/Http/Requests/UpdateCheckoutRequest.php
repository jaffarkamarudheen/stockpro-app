<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_promotion')) {
            $this->merge([
                'is_promotion' => filter_var($this->input('is_promotion'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['required', 'string'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'enquiry_from' => ['required', 'string', 'max:100'],
            'is_promotion' => ['nullable', 'boolean'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:ordered,waiting_for_delivery,delivered,received,cancelled'],
            'ordered_at' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'delivered_at' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_sale_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_other_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
