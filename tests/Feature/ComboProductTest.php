<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComboProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Product $earring;

    protected Product $locket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Manager',
            'email' => 'admin@cherryadorn.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'can_access_admin' => true,
            'can_access_app' => true,
            'is_active' => true,
        ]);

        $this->earring = Product::create([
            'product_number' => 'PRD-EAR-001',
            'name' => 'Gold Stud Earring',
            'quality' => 'Grade A',
            'price' => 300.00,
            'purchase_rate' => 100.00,
            'sale_rate' => 250.00,
            'other_rate' => 30.00,
            'profit_per_unit' => 120.00,
            'stock_quantity' => 15,
            'low_stock_threshold' => 3,
            'is_combo' => false,
        ]);

        $this->locket = Product::create([
            'product_number' => 'PRD-LOK-001',
            'name' => 'Crystal Heart Locket',
            'quality' => 'Premium',
            'price' => 500.00,
            'purchase_rate' => 200.00,
            'sale_rate' => 450.00,
            'other_rate' => 40.00,
            'profit_per_unit' => 210.00,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'is_combo' => false,
        ]);
    }

    public function test_can_create_combo_product_with_constituent_items(): void
    {
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'product_number' => 'CMB-SET-001',
            'name' => 'Earring & Locket Gift Set',
            'quality' => 'Gift Set',
            'price' => 850.00,
            'purchase_rate' => 300.00,
            'sale_rate' => 700.00,
            'other_rate' => 90.00,
            'stock_quantity' => 5,
            'low_stock_threshold' => 2,
            'description' => 'Beautiful gift combo set containing earrings and locket',
            'is_combo' => 1,
            'combo_items' => [
                [
                    'product_id' => $this->earring->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $this->locket->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertRedirect(route('products.index'));

        $combo = Product::where('product_number', 'CMB-SET-001')->first();
        $this->assertNotNull($combo);
        $this->assertTrue($combo->is_combo);
        $this->assertEquals(2, $combo->comboItems()->count());
        $this->assertEquals(5, $combo->effective_stock); // 15 / 2 = 7, 10 / 1 = 10 -> min is 7. But combo has stock 5.
    }

    public function test_checkout_combo_product_deducts_both_combo_and_component_stocks(): void
    {
        // Create combo product with 5 in stock:
        // Set includes 2x Earring (stock: 15) and 1x Locket (stock: 10)
        $combo = Product::create([
            'product_number' => 'CMB-SET-002',
            'name' => 'Royal Combo Set',
            'quality' => 'Luxury',
            'price' => 800.00,
            'purchase_rate' => 300.00,
            'sale_rate' => 750.00,
            'other_rate' => 90.00,
            'profit_per_unit' => 360.00,
            'stock_quantity' => 5,
            'low_stock_threshold' => 2,
            'is_combo' => true,
        ]);

        ComboItem::create([
            'combo_product_id' => $combo->id,
            'item_product_id' => $this->earring->id,
            'quantity' => 2,
        ]);

        ComboItem::create([
            'combo_product_id' => $combo->id,
            'item_product_id' => $this->locket->id,
            'quantity' => 1,
        ]);

        // Checkout 2 units of the combo
        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Sneha Patel',
            'customer_address' => '12 MG Road, Bangalore',
            'customer_phone' => '9876543210',
            'enquiry_from' => 'Instagram',
            'is_promotion' => false,
            'discount_amount' => 0.00,
            'status' => 'ordered',
            'items' => [
                [
                    'product_id' => $combo->id,
                    'quantity' => 2,
                    'unit_sale_rate' => 750.00,
                    'unit_other_rate' => 90.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        // 1. Combo product stock: was 5, 2 checked out -> now 3
        $this->assertEquals(3, $combo->fresh()->stock_quantity);

        // 2. Earring stock: was 15, checked out 2 * 2 = 4 -> now 11
        $this->assertEquals(11, $this->earring->fresh()->stock_quantity);

        // 3. Locket stock: was 10, checked out 2 * 1 = 2 -> now 8
        $this->assertEquals(8, $this->locket->fresh()->stock_quantity);

        // Verify stock movement logs recorded for all products
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $combo->id,
            'type' => 'CHECK_OUT',
            'quantity' => -2,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->earring->id,
            'type' => 'CHECK_OUT',
            'quantity' => -4,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->locket->id,
            'type' => 'CHECK_OUT',
            'quantity' => -2,
        ]);
    }

    public function test_checkout_fails_if_component_stock_is_insufficient(): void
    {
        // Earring stock is 15. Set requires 10 earrings per set.
        // Ordering 2 sets requires 20 earrings, which exceeds 15.
        $combo = Product::create([
            'product_number' => 'CMB-SET-003',
            'name' => 'Mega Earring Bundle',
            'quality' => 'Bulk',
            'price' => 2000.00,
            'purchase_rate' => 800.00,
            'sale_rate' => 1800.00,
            'other_rate' => 90.00,
            'profit_per_unit' => 910.00,
            'stock_quantity' => 10,
            'low_stock_threshold' => 1,
            'is_combo' => true,
        ]);

        ComboItem::create([
            'combo_product_id' => $combo->id,
            'item_product_id' => $this->earring->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Rohan Varma',
            'customer_address' => '7 Green Glen, Bangalore',
            'customer_phone' => '9123456789',
            'enquiry_from' => 'WhatsApp',
            'status' => 'ordered',
            'items' => [
                [
                    'product_id' => $combo->id,
                    'quantity' => 2, // Needs 20 earrings, available is 15
                ],
            ],
        ]);

        $response->assertSessionHasErrors('items');

        // Stocks must remain unchanged
        $this->assertEquals(10, $combo->fresh()->stock_quantity);
        $this->assertEquals(15, $this->earring->fresh()->stock_quantity);
    }

    public function test_deleting_order_restores_combo_and_component_stocks(): void
    {
        $combo = Product::create([
            'product_number' => 'CMB-SET-004',
            'name' => 'Bridal Set',
            'quality' => 'Premium',
            'price' => 1000.00,
            'purchase_rate' => 400.00,
            'sale_rate' => 900.00,
            'other_rate' => 90.00,
            'profit_per_unit' => 410.00,
            'stock_quantity' => 10,
            'low_stock_threshold' => 1,
            'is_combo' => true,
        ]);

        ComboItem::create([
            'combo_product_id' => $combo->id,
            'item_product_id' => $this->earring->id,
            'quantity' => 3,
        ]);

        // Place checkout for 1 combo
        $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Meera Das',
            'customer_address' => '88 Residency Road',
            'enquiry_from' => 'Phone Call',
            'status' => 'ordered',
            'items' => [
                [
                    'product_id' => $combo->id,
                    'quantity' => 1,
                ],
            ],
        ]);

        $checkout = Checkout::latest('id')->first();
        $this->assertNotNull($checkout);

        // After checkout: combo was 10 -> 9. Earring was 15 -> 12
        $this->assertEquals(9, $combo->fresh()->stock_quantity);
        $this->assertEquals(12, $this->earring->fresh()->stock_quantity);

        // Delete checkout
        $deleteResponse = $this->actingAs($this->admin)->delete(route('checkouts.destroy', $checkout));
        $deleteResponse->assertRedirect(route('checkouts.index'));

        // Both stocks should be restored
        $this->assertEquals(10, $combo->fresh()->stock_quantity);
        $this->assertEquals(15, $this->earring->fresh()->stock_quantity);

        // Verify RETURN stock movements
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $combo->id,
            'type' => 'RETURN',
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->earring->id,
            'type' => 'RETURN',
            'quantity' => 3,
        ]);
    }

    public function test_api_checkout_deducts_and_restores_combo_stocks(): void
    {
        $combo = Product::create([
            'product_number' => 'CMB-SET-API',
            'name' => 'API Combo Set',
            'quality' => 'Grade A',
            'price' => 500.00,
            'purchase_rate' => 200.00,
            'sale_rate' => 450.00,
            'other_rate' => 90.00,
            'profit_per_unit' => 160.00,
            'stock_quantity' => 8,
            'low_stock_threshold' => 1,
            'is_combo' => true,
        ]);

        ComboItem::create([
            'combo_product_id' => $combo->id,
            'item_product_id' => $this->earring->id,
            'quantity' => 2,
        ]);

        // API store
        $response = $this->actingAs($this->admin)->postJson('/api/v1/checkouts', [
            'customer_name' => 'Mobile App Client',
            'customer_address' => 'App User City',
            'enquiry_from' => 'WhatsApp',
            'items' => [
                [
                    'product_id' => $combo->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertStatus(201);
        $orderId = $response->json('data.id');

        // Combo stock: 8 - 2 = 6. Earring stock: 15 - 4 = 11
        $this->assertEquals(6, $combo->fresh()->stock_quantity);
        $this->assertEquals(11, $this->earring->fresh()->stock_quantity);

        // API destroy
        $deleteResponse = $this->actingAs($this->admin)->deleteJson("/api/v1/checkouts/{$orderId}");
        $deleteResponse->assertStatus(200);

        // Both stocks restored
        $this->assertEquals(8, $combo->fresh()->stock_quantity);
        $this->assertEquals(15, $this->earring->fresh()->stock_quantity);
    }
}
