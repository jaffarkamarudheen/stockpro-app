<?php

namespace Database\Factories;

use App\Models\Checkout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Checkout>
 */
class CheckoutFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.strtoupper(fake()->unique()->bothify('??####')),
            'customer_name' => fake()->name(),
            'customer_address' => fake()->address(),
            'customer_phone' => fake()->phoneNumber(),
            'enquiry_from' => 'Instagram',
            'ordered_at' => now(),
            'status' => 'ordered',
            'is_promotion' => false,
            'total_purchase_cost' => 100.00,
            'total_other_cost' => 20.00,
            'total_sale_amount' => 200.00,
            'total_profit' => 80.00,
            'discount_amount' => 0.00,
        ];
    }
}
