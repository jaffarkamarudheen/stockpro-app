<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_number' => 'SKU-'.fake()->unique()->numerify('#####'),
            'name' => fake()->words(2, true),
            'quality' => 'Standard',
            'price' => 200.00,
            'purchase_rate' => 100.00,
            'sale_rate' => 200.00,
            'other_rate' => 20.00,
            'profit_per_unit' => 80.00,
            'stock_quantity' => 25,
            'low_stock_threshold' => 5,
        ];
    }
}
