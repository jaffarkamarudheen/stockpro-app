<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'product_number' => $this->product_number,
            'name' => $this->name,
            'photo_url' => $this->photo_url,
            'quality' => $this->quality,
            'price' => (float) $this->price,
            'purchase_rate' => (float) $this->purchase_rate,
            'sale_rate' => (float) $this->sale_rate,
            'other_rate' => (float) $this->other_rate,
            'profit_per_unit' => (float) $this->profit_per_unit,
            'stock_quantity' => (int) $this->stock_quantity,
            'low_stock_threshold' => (int) $this->low_stock_threshold,
            'stock_status' => $this->stock_status,
            'description' => $this->description,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
