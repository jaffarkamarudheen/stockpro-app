<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Models\ActivityLog;
use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
        $query = Checkout::with('items.product')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_address', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($source = $request->input('enquiry_from')) {
            $query->where('enquiry_from', $source);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $checkouts = $query->paginate(15)->withQueryString();

        $enquirySources = Checkout::select('enquiry_from')
            ->distinct()
            ->whereNotNull('enquiry_from')
            ->pluck('enquiry_from');

        $users = User::orderBy('name')->get();

        $totalSales = Checkout::sum('total_sale_amount');
        $totalProfit = Checkout::sum('total_profit');
        $totalOrders = Checkout::count();

        return view('checkouts.index', compact('checkouts', 'enquirySources', 'totalSales', 'totalProfit', 'totalOrders', 'users'));
    }

    public function create(): View
    {
        $products = Product::where('stock_quantity', '>', 0)->orderBy('name')->get();

        $defaultSources = [
            'WhatsApp',
            'Instagram',
            'Store Walk-in',
            'Facebook',
            'Phone Call',
            'Referral',
            'Website',
            'Other',
        ];

        return view('checkouts.create', compact('products', 'defaultSources'));
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $checkout = DB::transaction(function () use ($validated) {
            $totalQuantity = 0;
            $totalSale = 0.0;
            $totalPurchase = 0.0;
            $totalOther = 0.0;
            $totalProfit = 0.0;
            $itemsToCreate = [];

            foreach ($validated['items'] as $itemData) {
                $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);
                $qty = (int) $itemData['quantity'];

                if ($product->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Not enough stock for {$product->name}. Requested: {$qty}, Available: {$product->stock_quantity}"],
                    ]);
                }

                $saleRate = isset($itemData['unit_sale_rate']) && $itemData['unit_sale_rate'] !== null && $itemData['unit_sale_rate'] !== ''
                    ? (float) $itemData['unit_sale_rate']
                    : (float) $product->sale_rate;

                $purchaseRate = (float) $product->purchase_rate;
                $otherRate = (float) $product->other_rate;
                $unitProfit = round($saleRate - $purchaseRate - $otherRate, 2);

                $subtotalSale = round($saleRate * $qty, 2);
                $subtotalProfit = round($unitProfit * $qty, 2);

                $totalQuantity += $qty;
                $totalSale += $subtotalSale;
                $totalPurchase += round($purchaseRate * $qty, 2);
                $totalOther += round($otherRate * $qty, 2);
                $totalProfit += $subtotalProfit;

                $itemsToCreate[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_purchase_rate' => $purchaseRate,
                    'unit_sale_rate' => $saleRate,
                    'unit_other_rate' => $otherRate,
                    'unit_profit' => $unitProfit,
                    'subtotal_sale' => $subtotalSale,
                    'subtotal_profit' => $subtotalProfit,
                ];
            }

            $checkout = Checkout::create([
                'customer_name' => $validated['customer_name'],
                'customer_address' => $validated['customer_address'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'enquiry_from' => $validated['enquiry_from'],
                'total_quantity' => $totalQuantity,
                'total_sale_amount' => $totalSale,
                'total_purchase_cost' => $totalPurchase,
                'total_other_cost' => $totalOther,
                'total_profit' => $totalProfit,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($itemsToCreate as $item) {
                CheckoutItem::create([
                    'checkout_id' => $checkout->id,
                    'product_id' => $item['product']->id,
                    'quantity' => $item['quantity'],
                    'unit_purchase_rate' => $item['unit_purchase_rate'],
                    'unit_sale_rate' => $item['unit_sale_rate'],
                    'unit_other_rate' => $item['unit_other_rate'],
                    'unit_profit' => $item['unit_profit'],
                    'subtotal_sale' => $item['subtotal_sale'],
                    'subtotal_profit' => $item['subtotal_profit'],
                ]);

                // Deduct stock
                $item['product']->decrement('stock_quantity', $item['quantity']);

                // Record stock movement
                StockMovement::create([
                    'product_id' => $item['product']->id,
                    'type' => 'CHECK_OUT',
                    'quantity' => -$item['quantity'],
                    'reference' => 'Order #'.$checkout->order_number,
                    'notes' => 'Customer: '.$checkout->customer_name,
                ]);
            }

            ActivityLog::record('CHECKOUT_COMPLETED', "Order #{$checkout->order_number} created for {$checkout->customer_name}. Total: ₹{$checkout->total_sale_amount}, Net Profit: ₹{$checkout->total_profit}");

            return $checkout;
        });

        return redirect()->route('checkouts.show', $checkout)->with('success', "Order #{$checkout->order_number} created successfully!");
    }

    public function show(Checkout $checkout): View
    {
        $checkout->load(['items.product']);

        return view('checkouts.show', compact('checkout'));
    }
}
