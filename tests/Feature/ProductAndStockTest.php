<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAndStockTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'can_access_admin' => true,
            'can_access_app' => true,
            'is_active' => true,
        ]);

        $this->operator = User::create([
            'name' => 'Operator Test',
            'email' => 'operator@test.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'can_access_admin' => false,
            'can_access_app' => true,
            'is_active' => true,
        ]);
    }

    public function test_product_creation_and_automatic_profit_calculation(): void
    {
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'product_number' => 'TEST-SKU-999',
            'name' => 'Premium Gaming Headset',
            'quality' => 'Grade A',
            'price' => 120.00,
            'purchase_rate' => 50.00,
            'sale_rate' => 100.00,
            'other_rate' => 10.00,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
        ]);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('product_number', 'TEST-SKU-999')->first();
        $this->assertNotNull($product);
        // Profit = 100 (Sale) - 50 (Purchase) - 10 (Other) = 40
        $this->assertEquals(40.00, (float) $product->profit_per_unit);
        $this->assertEquals(20, $product->stock_quantity);
    }

    public function test_stock_check_in_increments_product_stock_count(): void
    {
        $product = Product::create([
            'product_number' => 'SKU-STOCK-01',
            'name' => 'USB-C Cable',
            'price' => 15.00,
            'purchase_rate' => 5.00,
            'sale_rate' => 12.00,
            'other_rate' => 1.00,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post(route('stock.check-in'), [
            'product_id' => $product->id,
            'quantity' => 15,
            'reference' => 'Supplier Delivery A',
            'notes' => 'Received in good condition',
        ]);

        $response->assertRedirect();
        $product->refresh();

        $this->assertEquals(25, $product->stock_quantity);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'CHECK_IN',
            'quantity' => 15,
        ]);
    }

    public function test_checkout_deducts_stock_and_stores_buyer_address_and_enquiry(): void
    {
        $product = Product::create([
            'product_number' => 'SKU-SALE-02',
            'name' => 'Mechanical Keyboard',
            'price' => 100.00,
            'purchase_rate' => 40.00,
            'sale_rate' => 80.00,
            'other_rate' => 5.00,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'John Doe',
            'customer_address' => '123 Main St, MG Road, Bengaluru 560001',
            'customer_phone' => '+91 98450 12345',
            'enquiry_from' => 'Instagram',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_sale_rate' => 80.00,
                ],
            ],
        ]);

        $product->refresh();
        $this->assertEquals(7, $product->stock_quantity); // 10 - 3 = 7

        $checkout = Checkout::where('customer_name', 'John Doe')->first();
        $this->assertNotNull($checkout);
        $this->assertEquals('123 Main St, MG Road, Bengaluru 560001', $checkout->customer_address);
        $this->assertEquals('Instagram', $checkout->enquiry_from);
        $this->assertEquals(240.00, (float) $checkout->total_sale_amount); // 3 * 80
        $this->assertEquals(120.00, (float) $checkout->total_purchase_cost); // 3 * 40
        $this->assertEquals(15.00, (float) $checkout->total_other_cost); // 3 * 5
        $this->assertEquals(105.00, (float) $checkout->total_profit); // 3 * (80 - 40 - 5) = 105

        $response->assertRedirect(route('checkouts.show', $checkout));
    }

    public function test_api_v1_product_lookup_by_code(): void
    {
        $product = Product::create([
            'product_number' => 'BARCODE-XYZ',
            'name' => 'Fast Charging Adapter',
            'price' => 30.00,
            'purchase_rate' => 10.00,
            'sale_rate' => 25.00,
            'other_rate' => 2.00,
            'stock_quantity' => 50,
        ]);

        $response = $this->getJson(route('api.v1.products.show', 'BARCODE-XYZ'));

        $response->assertOk()
            ->assertJsonPath('data.product_number', 'BARCODE-XYZ')
            ->assertJsonPath('data.profit_per_unit', 13);
    }

    public function test_reports_page_loads_with_financial_and_stock_metrics(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.index'));
        $response->assertOk()
            ->assertViewHas(['totalSales', 'totalProfit', 'stockStats', 'monthlyData']);
    }

    public function test_operator_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->operator)->get(route('admin.dashboard'));
        $response->assertRedirect(route('app.index'));
    }

    public function test_operator_can_access_mobile_app(): void
    {
        $response = $this->actingAs($this->operator)->get(route('app.index'));
        $response->assertOk();
    }

    public function test_admin_can_manage_users_and_permissions(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'New Staff',
            'email' => 'newstaff@stockpro.com',
            'password' => 'secret123',
            'role' => 'operator',
            'can_access_app' => 1,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['email' => 'newstaff@stockpro.com', 'can_access_app' => 1]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'USER_CREATED',
        ]);
    }

    public function test_checkout_with_multiple_products_deducts_all_stocks_and_calculates_total_profit(): void
    {
        $prod1 = Product::create([
            'product_number' => 'SKU-MULTI-1',
            'name' => 'Fast Charger 65W',
            'price' => 1000.00,
            'purchase_rate' => 400.00,
            'sale_rate' => 800.00,
            'other_rate' => 50.00, // unit profit = 800 - 400 - 50 = 350
            'stock_quantity' => 10,
        ]);

        $prod2 = Product::create([
            'product_number' => 'SKU-MULTI-2',
            'name' => 'Wireless Mouse RGB',
            'price' => 1500.00,
            'purchase_rate' => 600.00,
            'sale_rate' => 1200.00,
            'other_rate' => 100.00, // unit profit = 1200 - 600 - 100 = 500
            'stock_quantity' => 15,
        ]);

        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Rohan Sharma',
            'customer_address' => 'Flat 101, Green Meadows, Bengaluru 560038',
            'customer_phone' => '+91 99000 11223',
            'enquiry_from' => 'WhatsApp',
            'items' => [
                [
                    'product_id' => $prod1->id,
                    'quantity' => 2,
                    'unit_sale_rate' => 800.00,
                ],
                [
                    'product_id' => $prod2->id,
                    'quantity' => 3,
                    'unit_sale_rate' => 1200.00,
                ],
            ],
        ]);

        $prod1->refresh();
        $prod2->refresh();

        // Stock deduction check: prod1 was 10 - 2 = 8, prod2 was 15 - 3 = 12
        $this->assertEquals(8, $prod1->stock_quantity);
        $this->assertEquals(12, $prod2->stock_quantity);

        $checkout = Checkout::where('customer_name', 'Rohan Sharma')->first();
        $this->assertNotNull($checkout);
        $this->assertEquals(5, $checkout->total_quantity); // 2 + 3

        // Total Sale = (2 * 800) + (3 * 1200) = 1600 + 3600 = 5200
        $this->assertEquals(5200.00, (float) $checkout->total_sale_amount);

        // Total Profit = (2 * 350) + (3 * 500) = 700 + 1500 = 2200
        $this->assertEquals(2200.00, (float) $checkout->total_profit);

        $this->assertCount(2, $checkout->items);
        $response->assertRedirect(route('checkouts.show', $checkout));
    }
}
