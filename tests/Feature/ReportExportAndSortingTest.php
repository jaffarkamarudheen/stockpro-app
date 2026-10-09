<?php

namespace Tests\Feature;

use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportAndSortingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'can_access_admin' => true,
            'can_access_app' => true,
            'is_active' => true,
        ]);
    }

    public function test_financial_csv_export_returns_streamed_csv(): void
    {
        $product = Product::factory()->create([
            'purchase_rate' => 100,
            'sale_rate' => 200,
            'other_rate' => 20,
            'stock_quantity' => 50,
        ]);

        $checkout = Checkout::factory()->create([
            'user_id' => $this->admin->id,
            'ordered_at' => now(),
            'is_promotion' => false,
            'total_purchase_cost' => 100,
            'total_other_cost' => 20,
            'total_sale_amount' => 200,
            'total_profit' => 80,
            'status' => Checkout::STATUS_DELIVERED,
        ]);

        CheckoutItem::create([
            'checkout_id' => $checkout->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'purchase_rate' => 100,
            'sale_rate' => 200,
            'other_rate' => 20,
            'cost_total' => 100,
            'sale_total' => 200,
            'profit_total' => 80,
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.export.financial.csv', [
            'year' => now()->year,
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_financial_pdf_view_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get(route('reports.export.financial.pdf', [
            'year' => now()->year,
        ]));

        $response->assertOk();
        $response->assertViewIs('reports.pdf.financial');
        $response->assertSee('Cherry Adorn');
    }

    public function test_reports_page_renders_with_product_sales_breakdown(): void
    {
        $product = Product::factory()->create([
            'name' => 'Kundan Choker',
            'product_number' => 'KC-001',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Kundan Choker');
        $response->assertSee('KC-001');
        $response->assertSee('Product Sales & Profit Breakdown', false);
    }

    public function test_product_sales_csv_and_pdf_export_work(): void
    {
        $product = Product::factory()->create([
            'name' => 'Rose Gold Ring',
            'product_number' => 'RGR-001',
            'purchase_rate' => 50,
            'sale_rate' => 120,
            'other_rate' => 10,
            'stock_quantity' => 25,
        ]);

        $checkout = Checkout::factory()->create([
            'user_id' => $this->admin->id,
            'ordered_at' => now(),
            'is_promotion' => false,
            'total_sale_amount' => 240,
            'total_profit' => 120,
        ]);

        CheckoutItem::create([
            'checkout_id' => $checkout->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'purchase_rate' => 50,
            'sale_rate' => 120,
            'other_rate' => 10,
            'cost_total' => 100,
            'sale_total' => 240,
            'profit_total' => 120,
        ]);

        // CSV
        $csvResponse = $this->actingAs($this->admin)->get(route('reports.export.product_sales.csv', [
            'year' => now()->year,
        ]));
        $csvResponse->assertOk();
        $csvResponse->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // PDF
        $pdfResponse = $this->actingAs($this->admin)->get(route('reports.export.product_sales.pdf', [
            'year' => now()->year,
        ]));
        $pdfResponse->assertOk();
        $pdfResponse->assertViewIs('reports.pdf.product_sales');
        $pdfResponse->assertSee('Rose Gold Ring');
    }

    public function test_product_catalog_csv_and_pdf_export_work(): void
    {
        Product::factory()->create([
            'name' => 'Silver Earring',
            'product_number' => 'SE-101',
            'stock_quantity' => 10,
        ]);

        $csv = $this->actingAs($this->admin)->get(route('products.export.csv'));
        $csv->assertOk();
        $csv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $pdf = $this->actingAs($this->admin)->get(route('products.export.pdf'));
        $pdf->assertOk();
        $pdf->assertViewIs('products.pdf.catalog');
        $pdf->assertSee('Silver Earring');
    }

    public function test_checkout_sales_ledger_csv_and_pdf_export_work(): void
    {
        $product = Product::factory()->create();

        $c = Checkout::factory()->create([
            'user_id' => $this->admin->id,
            'customer_name' => 'Meera Patel',
            'order_number' => 'CA-ORD-9999',
            'is_promotion' => false,
            'total_sale_amount' => 500,
            'total_profit' => 200,
        ]);

        CheckoutItem::create([
            'checkout_id' => $c->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'cost_total' => 250,
            'sale_total' => 500,
            'profit_total' => 200,
        ]);

        $csv = $this->actingAs($this->admin)->get(route('checkouts.export.csv'));
        $csv->assertOk();
        $csv->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $pdf = $this->actingAs($this->admin)->get(route('checkouts.export.pdf'));
        $pdf->assertOk();
        $pdf->assertViewIs('checkouts.pdf.ledger');
        $pdf->assertSee('Meera Patel');
    }

    public function test_product_listing_orders_by_latest_first_by_default_and_supports_sorting(): void
    {
        $oldProduct = Product::factory()->create([
            'name' => 'AAA Product',
            'sale_rate' => 10,
            'stock_quantity' => 1,
            'created_at' => now()->subDays(5),
        ]);

        $newProduct = Product::factory()->create([
            'name' => 'ZZZ Product',
            'sale_rate' => 999,
            'stock_quantity' => 100,
            'created_at' => now(),
        ]);

        // Default latest first
        $response = $this->actingAs($this->admin)->get(route('products.index'));
        $response->assertOk();
        $products = $response->viewData('products');
        $this->assertEquals($newProduct->id, $products->first()->id);

        // Sort price_desc
        $responsePrice = $this->actingAs($this->admin)->get(route('products.index', ['sort' => 'price_desc']));
        $this->assertEquals($newProduct->id, $responsePrice->viewData('products')->first()->id);

        // Sort price_asc
        $responsePriceAsc = $this->actingAs($this->admin)->get(route('products.index', ['sort' => 'price_asc']));
        $this->assertEquals($oldProduct->id, $responsePriceAsc->viewData('products')->first()->id);

        // Sort name_asc
        $responseNameAsc = $this->actingAs($this->admin)->get(route('products.index', ['sort' => 'name_asc']));
        $this->assertEquals($oldProduct->id, $responseNameAsc->viewData('products')->first()->id);
    }

    public function test_checkouts_supports_sorting_and_custom_per_page(): void
    {
        $oldCheckout = Checkout::factory()->create([
            'user_id' => $this->admin->id,
            'customer_name' => 'Alpha Customer',
            'total_sale_amount' => 50,
            'ordered_at' => now()->subDays(3),
        ]);

        $newCheckout = Checkout::factory()->create([
            'user_id' => $this->admin->id,
            'customer_name' => 'Zeta Customer',
            'total_sale_amount' => 500,
            'ordered_at' => now(),
        ]);

        // Default latest
        $response = $this->actingAs($this->admin)->get(route('checkouts.index'));
        $response->assertOk();
        $this->assertEquals($newCheckout->id, $response->viewData('checkouts')->first()->id);

        // Sort amount_desc
        $responseAmount = $this->actingAs($this->admin)->get(route('checkouts.index', ['sort' => 'amount_desc']));
        $this->assertEquals($newCheckout->id, $responseAmount->viewData('checkouts')->first()->id);

        // Sort customer_asc
        $responseCustomer = $this->actingAs($this->admin)->get(route('checkouts.index', ['sort' => 'customer_asc']));
        $this->assertEquals($oldCheckout->id, $responseCustomer->viewData('checkouts')->first()->id);

        // Custom per_page
        $responsePerPage = $this->actingAs($this->admin)->get(route('checkouts.index', ['per_page' => 25]));
        $this->assertEquals(25, $responsePerPage->viewData('checkouts')->perPage());
    }

    public function test_stock_movements_support_sorting_and_custom_per_page(): void
    {
        $product = Product::factory()->create();

        $sm1 = StockMovement::create([
            'product_id' => $product->id,
            'type' => 'CHECK_IN',
            'quantity' => 10,
            'created_at' => now()->subDay(),
        ]);

        $sm2 = StockMovement::create([
            'product_id' => $product->id,
            'type' => 'CHECK_IN',
            'quantity' => 100,
            'created_at' => now(),
        ]);

        // Default latest
        $response = $this->actingAs($this->admin)->get(route('stock.index'));
        $response->assertOk();
        $this->assertEquals($sm2->id, $response->viewData('movements')->first()->id);

        // Sort quantity_desc
        $resQty = $this->actingAs($this->admin)->get(route('stock.index', ['sort' => 'quantity_desc']));
        $this->assertEquals($sm2->id, $resQty->viewData('movements')->first()->id);

        // Sort quantity_asc
        $resQtyAsc = $this->actingAs($this->admin)->get(route('stock.index', ['sort' => 'quantity_asc']));
        $this->assertEquals($sm1->id, $resQtyAsc->viewData('movements')->first()->id);
    }

    public function test_product_store_with_image_url_persists_safely(): void
    {
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'name' => 'Pearl Necklace',
            'product_number' => 'PN-505',
            'price' => 600,
            'purchase_rate' => 300,
            'sale_rate' => 600,
            'other_rate' => 30,
            'stock_quantity' => 15,
            'low_stock_threshold' => 5,
            'quality' => 'Premium',
            'photo_url_input' => 'https://images.unsplash.com/photo-pearl.jpg',
        ]);

        $response->assertRedirect(route('products.index'));

        $product = Product::where('product_number', 'PN-505')->first();
        $this->assertNotNull($product);
        $this->assertEquals('https://images.unsplash.com/photo-pearl.jpg', $product->photo_path);
        $this->assertEquals('https://images.unsplash.com/photo-pearl.jpg', $product->photo_url);
    }
}
