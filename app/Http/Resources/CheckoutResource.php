<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customer_name,
            'customer_address' => $this->customer_address,
            'customer_phone' => $this->customer_phone,
            'enquiry_from' => $this->enquiry_from,
            'total_quantity' => (int) $this->total_quantity,
            'total_sale_amount' => (float) $this->total_sale_amount,
            'total_purchase_cost' => (float) $this->total_purchase_cost,
            'total_other_cost' => (float) $this->total_other_cost,
            'total_profit' => (float) $this->total_profit,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'product_name' => $item->product?->name,
                        'product_number' => $item->product?->product_number,
                        'quantity' => (int) $item->quantity,
                        'unit_purchase_rate' => (float) $item->unit_purchase_rate,
                        'unit_sale_rate' => (float) $item->unit_sale_rate,
                        'unit_other_rate' => (float) $item->unit_other_rate,
                        'unit_profit' => (float) $item->unit_profit,
                        'subtotal_sale' => (float) $item->subtotal_sale,
                        'subtotal_profit' => (float) $item->subtotal_profit,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
