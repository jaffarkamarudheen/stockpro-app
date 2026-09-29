<?php

namespace Database\Seeders;

use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Default Admin & Operator Users
        User::firstOrCreate(
            ['email' => 'admin@stockpro.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'can_access_admin' => true,
                'can_access_app' => true,
                'is_active' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'operator@stockpro.com'],
            [
                'name' => 'Store Operator',
                'password' => Hash::make('operator123'),
                'role' => 'operator',
                'can_access_admin' => false,
                'can_access_app' => true,
                'is_active' => true,
            ]
        );

        $productsData = [
            [
                'product_number' => 'PRD-1001-A',
                'name' => 'Pro ANC Wireless Headphones',
                'quality' => 'Grade A+ Premium',
                'price' => 2999.00,
                'purchase_rate' => 1400.00,
                'sale_rate' => 2699.00,
                'other_rate' => 150.00,
                'stock_quantity' => 45,
                'low_stock_threshold' => 10,
                'description' => 'Active Noise Cancellation, 40h Battery, Hi-Res Audio certification.',
            ],
            [
                'product_number' => 'PRD-1002-B',
                'name' => 'Smart Fitness Tracker Watch',
                'quality' => 'Standard Edition',
                'price' => 1899.00,
                'purchase_rate' => 850.00,
                'sale_rate' => 1699.00,
                'other_rate' => 100.00,
                'stock_quantity' => 4, // low stock
                'low_stock_threshold' => 8,
                'description' => 'Heart rate, SpO2, sleep tracking, waterproof 5ATM.',
            ],
            [
                'product_number' => 'PRD-1003-C',
                'name' => 'Ultra-Slim 65W GaN Charger',
                'quality' => 'OEM Super Fast',
                'price' => 999.00,
                'purchase_rate' => 420.00,
                'sale_rate' => 899.00,
                'other_rate' => 50.00,
                'stock_quantity' => 0, // out of stock
                'low_stock_threshold' => 15,
                'description' => 'Triple port Type-C and USB fast charging for laptops & phones.',
            ],
            [
                'product_number' => 'PRD-1004-D',
                'name' => 'Ergonomic Vertical Mouse',
                'quality' => 'Premium Ergonomic',
                'price' => 1299.00,
                'purchase_rate' => 550.00,
                'sale_rate' => 1099.00,
                'other_rate' => 80.00,
                'stock_quantity' => 28,
                'low_stock_threshold' => 5,
                'description' => 'Rechargeable wireless ergonomic mouse with 4000 DPI sensor.',
            ],
            [
                'product_number' => 'PRD-1005-E',
                'name' => 'Mechanical Gaming Keyboard RGB',
                'quality' => 'Custom Switch Grade',
                'price' => 3499.00,
                'purchase_rate' => 1650.00,
                'sale_rate' => 3199.00,
                'other_rate' => 200.00,
                'stock_quantity' => 18,
                'low_stock_threshold' => 6,
                'description' => 'Hot-swappable tactile brown switches with PBT keycaps.',
            ],
        ];

        $createdProducts = [];
        foreach ($productsData as $data) {
            $createdProducts[] = Product::firstOrCreate(['product_number' => $data['product_number']], $data);
        }

        // Seed checkouts across past months to populate reports
        $customers = [
            [
                'name' => 'Rajesh Sharma',
                'address' => 'Flat 402, Shanti Heights, MG Road, Bengaluru, Karnataka 560001',
                'phone' => '+91 98450 12345',
                'source' => 'WhatsApp',
                'days_ago' => 2,
            ],
            [
                'name' => 'Priya Patel',
                'address' => '12/B Nilgiri Enclave, Bandra West, Mumbai, Maharashtra 400050',
                'phone' => '+91 98201 98765',
                'source' => 'Instagram',
                'days_ago' => 12,
            ],
            [
                'name' => 'Amit Verma',
                'address' => 'Shop 18, Block C, Connaught Place, New Delhi 110001',
                'phone' => '+91 98110 54321',
                'source' => 'Store Walk-in',
                'days_ago' => 25,
            ],
            [
                'name' => 'Ananya Iyer',
                'address' => 'Plot 55, 4th Main Road, Anna Nagar, Chennai, Tamil Nadu 600040',
                'phone' => '+91 94440 67890',
                'source' => 'Website',
                'days_ago' => 45,
            ],
            [
                'name' => 'Suresh Reddy',
                'address' => 'Road No 10, Banjara Hills, Hyderabad, Telangana 500034',
                'phone' => '+91 99890 11223',
                'source' => 'Referral',
                'days_ago' => 70,
            ],
        ];

        foreach ($customers as $cust) {
            $prod = $createdProducts[array_rand($createdProducts)];
            $qty = rand(1, 3);
            $subtotalSale = $prod->sale_rate * $qty;
            $subtotalPurchase = $prod->purchase_rate * $qty;
            $subtotalOther = $prod->other_rate * $qty;
            $profit = ($prod->sale_rate - $prod->purchase_rate - $prod->other_rate) * $qty;

            $date = Carbon::now()->subDays($cust['days_ago']);

            $checkout = Checkout::create([
                'order_number' => 'ORD-'.$date->format('Ymd').'-'.rand(1000, 9999),
                'customer_name' => $cust['name'],
                'customer_address' => $cust['address'],
                'customer_phone' => $cust['phone'],
                'enquiry_from' => $cust['source'],
                'total_quantity' => $qty,
                'total_sale_amount' => $subtotalSale,
                'total_purchase_cost' => $subtotalPurchase,
                'total_other_cost' => $subtotalOther,
                'total_profit' => $profit,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            CheckoutItem::create([
                'checkout_id' => $checkout->id,
                'product_id' => $prod->id,
                'quantity' => $qty,
                'unit_purchase_rate' => $prod->purchase_rate,
                'unit_sale_rate' => $prod->sale_rate,
                'unit_other_rate' => $prod->other_rate,
                'unit_profit' => $prod->sale_rate - $prod->purchase_rate - $prod->other_rate,
                'subtotal_sale' => $subtotalSale,
                'subtotal_profit' => $profit,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            StockMovement::create([
                'product_id' => $prod->id,
                'type' => 'CHECK_OUT',
                'quantity' => -$qty,
                'reference' => 'Order #'.$checkout->order_number,
                'notes' => 'Customer: '.$checkout->customer_name,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
    }
}
