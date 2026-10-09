<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin@cherryadorn.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'can_access_admin' => true,
            'can_access_app' => true,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'product_number' => 'CA-NECK-001',
            'name' => 'Rose Quartz Necklace',
            'quality' => 'Premium',
            'price' => 500.00,
            'purchase_rate' => 150.00,
            'sale_rate' => 450.00,
            'other_rate' => 50.00,
            'profit_per_unit' => 250.00,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
        ]);
    }

    public function test_standard_checkout_with_discount(): void
    {
        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Ananya Sharma',
            'customer_address' => '42 MG Road, Bangalore',
            'customer_phone' => '9876543210',
            'enquiry_from' => 'Instagram',
            'is_promotion' => false,
            'discount_amount' => 50.00,
            'status' => 'ordered',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_sale_rate' => 450.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $checkout = Checkout::latest('id')->first();
        $this->assertNotNull($checkout);
        $this->assertEquals(2, $checkout->total_quantity);
        $this->assertEquals(900.00, (float) $checkout->subtotal_amount);
        $this->assertEquals(50.00, (float) $checkout->discount_amount);
        $this->assertEquals(850.00, (float) $checkout->total_sale_amount);
        // Cost: 2 * 150 = 300, Other: 2 * 50 = 100. Profit = 850 - 400 = 450
        $this->assertEquals(450.00, (float) $checkout->total_profit);

        // Verify stock deducted
        $this->product->refresh();
        $this->assertEquals(18, $this->product->stock_quantity);
    }

    public function test_promotional_checkout_without_money_deducts_stock_only(): void
    {
        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Influencer VIP',
            'customer_address' => 'Bandra West, Mumbai',
            'enquiry_from' => 'Instagram',
            'is_promotion' => true,
            'status' => 'ordered',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 3,
                    'unit_sale_rate' => 450.00,
                ],
            ],
        ]);

        $response->assertRedirect();

        $checkout = Checkout::latest('id')->first();
        $this->assertTrue($checkout->is_promotion);
        $this->assertEquals(0.00, (float) $checkout->total_sale_amount);
        $this->assertEquals(0.00, (float) $checkout->total_profit);

        // Verify stock deducted by 3
        $this->product->refresh();
        $this->assertEquals(17, $this->product->stock_quantity);

        // Reports check: non-promotional sum ignores promo orders
        $totalSales = Checkout::nonPromotional()->sum('total_sale_amount');
        $this->assertEquals(0.00, (float) $totalSales);
    }

    public function test_checkout_can_be_edited_and_reconciles_stock(): void
    {
        // 1. Create checkout for 2 units
        $checkout = Checkout::create([
            'customer_name' => 'Pooja Nair',
            'customer_address' => 'Kochi, Kerala',
            'enquiry_from' => 'WhatsApp',
            'status' => 'ordered',
            'total_quantity' => 2,
            'subtotal_amount' => 900.00,
            'total_sale_amount' => 900.00,
            'total_purchase_cost' => 300.00,
            'total_other_cost' => 100.00,
            'total_profit' => 500.00,
            'ordered_at' => now(),
        ]);

        $checkout->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit_purchase_rate' => 150.00,
            'unit_sale_rate' => 450.00,
            'unit_other_rate' => 50.00,
            'unit_profit' => 250.00,
            'subtotal_sale' => 900.00,
            'subtotal_profit' => 500.00,
        ]);
        $this->product->decrement('stock_quantity', 2); // 18 remaining

        // 2. Edit checkout: change quantity from 2 to 5 units
        $response = $this->actingAs($this->admin)->put(route('checkouts.update', $checkout), [
            'customer_name' => 'Pooja Nair Updated',
            'customer_address' => 'Kochi, Kerala',
            'enquiry_from' => 'WhatsApp',
            'status' => 'waiting_for_delivery',
            'is_promotion' => false,
            'discount_amount' => 0.00,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 5,
                    'unit_sale_rate' => 450.00,
                ],
            ],
        ]);

        $response->assertRedirect(route('checkouts.show', $checkout));

        $checkout->refresh();
        $this->assertEquals('Pooja Nair Updated', $checkout->customer_name);
        $this->assertEquals('waiting_for_delivery', $checkout->status);
        $this->assertEquals(5, $checkout->total_quantity);

        // Product stock should have restored 2 and deducted 5 (20 - 5 = 15)
        $this->product->refresh();
        $this->assertEquals(15, $this->product->stock_quantity);
    }

    public function test_checkout_edit_view_loads_with_selected_product(): void
    {
        $checkout = Checkout::create([
            'customer_name' => 'Pooja Nair',
            'customer_address' => 'Kochi, Kerala',
            'enquiry_from' => 'WhatsApp',
            'status' => 'ordered',
            'total_quantity' => 1,
            'subtotal_amount' => 450.00,
            'total_sale_amount' => 450.00,
            'ordered_at' => now(),
        ]);

        $checkout->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_purchase_rate' => 150.00,
            'unit_sale_rate' => 450.00,
            'unit_other_rate' => 50.00,
            'unit_profit' => 250.00,
            'subtotal_sale' => 450.00,
            'subtotal_profit' => 250.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('checkouts.edit', $checkout));
        $response->assertOk();
        $response->assertSee('Rose Quartz Necklace');
        $response->assertSee('value="'.$this->product->id.'"', false);
    }

    public function test_checkout_can_be_deleted_and_restores_inventory_stock(): void
    {
        $checkout = Checkout::create([
            'customer_name' => 'Cancelled Buyer',
            'customer_address' => 'Chennai',
            'enquiry_from' => 'Phone Call',
            'status' => 'ordered',
            'total_quantity' => 4,
            'total_sale_amount' => 1800.00,
            'ordered_at' => now(),
        ]);

        $checkout->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 4,
            'unit_purchase_rate' => 150.00,
            'unit_sale_rate' => 450.00,
            'unit_other_rate' => 50.00,
            'unit_profit' => 250.00,
            'subtotal_sale' => 1800.00,
            'subtotal_profit' => 1000.00,
        ]);
        $this->product->decrement('stock_quantity', 4); // 16 remaining
        $this->assertEquals(16, $this->product->stock_quantity);

        // Delete checkout
        $response = $this->actingAs($this->admin)->delete(route('checkouts.destroy', $checkout));
        $response->assertRedirect(route('checkouts.index'));

        $this->assertDatabaseMissing('checkouts', ['id' => $checkout->id]);

        // Stock restored to 20!
        $this->product->refresh();
        $this->assertEquals(20, $this->product->stock_quantity);
    }

    public function test_status_reminders_for_ordered_and_delivered(): void
    {
        // Order placed 2 days ago in 'ordered' status
        $overdueOrder = Checkout::create([
            'customer_name' => 'Delayed Order',
            'customer_address' => 'Delhi',
            'enquiry_from' => 'Website',
            'status' => 'ordered',
            'ordered_at' => now()->subDays(2),
        ]);

        $this->assertTrue($overdueOrder->isOverdueOrdered());
        $this->assertTrue($overdueOrder->needs_attention);

        // Order marked delivered 3 days ago without received confirmation
        $overdueDelivered = Checkout::create([
            'customer_name' => 'Delivered Followup',
            'customer_address' => 'Goa',
            'enquiry_from' => 'Website',
            'status' => 'delivered',
            'ordered_at' => now()->subDays(5),
            'delivered_at' => now()->subDays(3),
        ]);

        $this->assertTrue($overdueDelivered->isOverdueDelivered());
        $this->assertTrue($overdueDelivered->needs_attention);

        // Order received today
        $receivedOrder = Checkout::create([
            'customer_name' => 'Completed Happy Customer',
            'customer_address' => 'Pune',
            'enquiry_from' => 'Website',
            'status' => 'received',
            'ordered_at' => now()->subDays(3),
            'delivered_at' => now()->subDays(1),
            'received_at' => now(),
        ]);

        $this->assertFalse($receivedOrder->isOverdueOrdered());
        $this->assertFalse($receivedOrder->isOverdueDelivered());
        $this->assertFalse($receivedOrder->needs_attention);
    }

    public function test_client_receipt_view_displays_cherry_adorn_invoice(): void
    {
        $checkout = Checkout::create([
            'customer_name' => 'Meera Patel',
            'customer_address' => 'Ahmedabad, Gujarat',
            'customer_phone' => '9988776655',
            'enquiry_from' => 'WhatsApp',
            'status' => 'delivered',
            'total_quantity' => 1,
            'subtotal_amount' => 450.00,
            'total_sale_amount' => 450.00,
            'ordered_at' => now(),
        ]);

        $checkout->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'unit_purchase_rate' => 150.00,
            'unit_sale_rate' => 450.00,
            'unit_other_rate' => 50.00,
            'unit_profit' => 250.00,
            'subtotal_sale' => 450.00,
            'subtotal_profit' => 250.00,
        ]);

        $response = $this->actingAs($this->admin)->get(route('checkouts.receipt', $checkout));
        $response->assertOk();
        $response->assertSee('Cherry Adorn');
        $response->assertSee('The Little Jewellery Studio');
        $response->assertSee('Meera Patel');
        $response->assertSee('Download PDF');
    }

    public function test_checkout_with_waived_delivery_charge_overhead_calculates_profit_accurately(): void
    {
        // Product: Sale 450, Purchase 150. Default overhead: 90 (Delivery 50 + Box 40)
        // With delivery waived: overhead is 40. User gives ₹50 discount to customer.
        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Kavya Rao',
            'customer_address' => 'Store Pickup - Indiranagar',
            'customer_phone' => '9845012345',
            'enquiry_from' => 'Store Walk-in',
            'is_promotion' => false,
            'discount_amount' => 50.00,
            'status' => 'delivered',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'unit_sale_rate' => 450.00,
                    'unit_other_rate' => 40.00, // Delivery waived (saved ₹50)
                ],
            ],
        ]);

        $response->assertRedirect();

        $checkout = Checkout::latest()->first();
        $this->assertEquals(400.00, (float) $checkout->total_sale_amount); // 450 - 50 discount
        $this->assertEquals(40.00, (float) $checkout->total_other_cost); // only box expense incurred
        $this->assertEquals(150.00, (float) $checkout->total_purchase_cost);
        // Correct profit: 400 sale - 150 purchase - 40 overhead = +210 profit
        $this->assertEquals(210.00, (float) $checkout->total_profit);

        $item = $checkout->items->first();
        $this->assertEquals(40.00, (float) $item->unit_other_rate);
        $this->assertEquals(260.00, (float) $item->unit_profit); // 450 - 150 - 40
    }

    public function test_checkout_with_both_delivery_and_box_waived(): void
    {
        // Both delivery and packaging waived: overhead is 0
        $response = $this->actingAs($this->admin)->post(route('checkouts.store'), [
            'customer_name' => 'Deepak Verma',
            'customer_address' => 'Local Handover',
            'enquiry_from' => 'Website',
            'is_promotion' => false,
            'discount_amount' => 0.00,
            'status' => 'received',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_sale_rate' => 450.00,
                    'unit_other_rate' => 0.00, // ₹0 overhead
                ],
            ],
        ]);

        $response->assertRedirect();

        $checkout = Checkout::latest()->first();
        $this->assertEquals(900.00, (float) $checkout->total_sale_amount);
        $this->assertEquals(0.00, (float) $checkout->total_other_cost);
        $this->assertEquals(300.00, (float) $checkout->total_purchase_cost);
        // Profit: 900 - 300 - 0 = +600 profit
        $this->assertEquals(600.00, (float) $checkout->total_profit);
    }
}
