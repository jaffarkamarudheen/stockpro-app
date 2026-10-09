<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Requests\UpdateCheckoutRequest;
use App\Models\ActivityLog;
use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
        $query = Checkout::with('items.product')->latest('ordered_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_address', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->boolean('needs_attention')) {
            $query->needsAttention();
        }

        if ($request->has('is_promotion') && $request->input('is_promotion') !== '') {
            $query->where('is_promotion', $request->boolean('is_promotion'));
        }

        if ($source = $request->input('enquiry_from')) {
            $query->where('enquiry_from', $source);
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('ordered_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('ordered_at', '<=', $to);
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

        // Calculate reminder counts
        $overdueOrderedCount = Checkout::where('status', Checkout::STATUS_ORDERED)
            ->where('ordered_at', '<=', now()->subDay())
            ->count();

        $overdueDeliveredCount = Checkout::where('status', Checkout::STATUS_DELIVERED)
            ->where(function ($q): void {
                $q->where('delivered_at', '<=', now()->subDays(2))
                    ->orWhere(function ($sub): void {
                        $sub->whereNull('delivered_at')
                            ->where('updated_at', '<=', now()->subDays(2));
                    });
            })
            ->count();

        $totalNeedsAttention = $overdueOrderedCount + $overdueDeliveredCount;

        // Financial totals (ignoring promotional items per user requirement)
        $totalSales = Checkout::nonPromotional()->sum('total_sale_amount');
        $totalProfit = Checkout::nonPromotional()->sum('total_profit');
        $totalOrders = Checkout::count();
        $totalPromotions = Checkout::where('is_promotion', true)->count();

        return view('checkouts.index', compact(
            'checkouts',
            'enquirySources',
            'totalSales',
            'totalProfit',
            'totalOrders',
            'totalPromotions',
            'overdueOrderedCount',
            'overdueDeliveredCount',
            'totalNeedsAttention',
            'users'
        ));
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
            $subtotalSale = 0.0;
            $totalPurchase = 0.0;
            $totalOther = 0.0;
            $itemsToCreate = [];

            $isPromotion = (bool) ($validated['is_promotion'] ?? false);
            $discountAmount = max(0.0, (float) ($validated['discount_amount'] ?? 0));

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
                $otherRate = isset($itemData['unit_other_rate']) && $itemData['unit_other_rate'] !== null && $itemData['unit_other_rate'] !== ''
                    ? max(0.0, (float) $itemData['unit_other_rate'])
                    : (float) $product->other_rate;
                $unitProfit = round($saleRate - $purchaseRate - $otherRate, 2);

                $lineSubtotal = round($saleRate * $qty, 2);
                $lineProfit = round($unitProfit * $qty, 2);

                $totalQuantity += $qty;
                $subtotalSale += $lineSubtotal;
                $totalPurchase += round($purchaseRate * $qty, 2);
                $totalOther += round($otherRate * $qty, 2);

                $itemsToCreate[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_purchase_rate' => $purchaseRate,
                    'unit_sale_rate' => $saleRate,
                    'unit_other_rate' => $otherRate,
                    'unit_profit' => $unitProfit,
                    'subtotal_sale' => $lineSubtotal,
                    'subtotal_profit' => $lineProfit,
                ];
            }

            // If promotion, total sale and financial profit are 0 so reports are not deducted
            if ($isPromotion) {
                $finalSaleAmount = 0.0;
                $finalPurchaseCost = 0.0;
                $finalOtherCost = 0.0;
                $finalProfit = 0.0;
                $discountAmount = $subtotalSale; // 100% promotion discount
            } else {
                $finalSaleAmount = max(0.0, round($subtotalSale - $discountAmount, 2));
                $finalPurchaseCost = $totalPurchase;
                $finalOtherCost = $totalOther;
                $finalProfit = round($finalSaleAmount - $totalPurchase - $totalOther, 2);
            }

            $orderedAt = ! empty($validated['ordered_at']) ? Carbon::parse($validated['ordered_at']) : now();
            $expectedDeliveryDate = ! empty($validated['expected_delivery_date']) ? Carbon::parse($validated['expected_delivery_date'])->toDateString() : null;
            $status = $validated['status'] ?? Checkout::STATUS_ORDERED;

            $checkout = Checkout::create([
                'customer_name' => $validated['customer_name'],
                'customer_address' => $validated['customer_address'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'enquiry_from' => $validated['enquiry_from'],
                'is_promotion' => $isPromotion,
                'subtotal_amount' => $subtotalSale,
                'discount_amount' => $discountAmount,
                'status' => $status,
                'ordered_at' => $orderedAt,
                'expected_delivery_date' => $expectedDeliveryDate,
                'delivered_at' => $status === Checkout::STATUS_DELIVERED ? now() : null,
                'received_at' => $status === Checkout::STATUS_RECEIVED ? now() : null,
                'total_quantity' => $totalQuantity,
                'total_sale_amount' => $finalSaleAmount,
                'total_purchase_cost' => $finalPurchaseCost,
                'total_other_cost' => $finalOtherCost,
                'total_profit' => $finalProfit,
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
                    'notes' => ($isPromotion ? '[PROMOTION] ' : '').'Customer: '.$checkout->customer_name,
                ]);
            }

            $typeLabel = $isPromotion ? 'Promotional Order' : 'Order';
            ActivityLog::record('CHECKOUT_COMPLETED', "{$typeLabel} #{$checkout->order_number} created for {$checkout->customer_name}. Total: ₹{$checkout->total_sale_amount}, Net Profit: ₹{$checkout->total_profit}");

            return $checkout;
        });

        return redirect()->route('checkouts.show', $checkout)->with('success', "Order #{$checkout->order_number} created successfully!");
    }

    public function show(Checkout $checkout): View
    {
        $checkout->load(['items.product']);

        return view('checkouts.show', compact('checkout'));
    }

    public function edit(Checkout $checkout): View
    {
        $checkout->load(['items.product']);
        $products = Product::orderBy('name')->get();

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

        return view('checkouts.edit', compact('checkout', 'products', 'defaultSources'));
    }

    public function update(UpdateCheckoutRequest $request, Checkout $checkout): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $checkout) {
            // Step 1: Restore stock from existing items before recalculating
            foreach ($checkout->items as $existingItem) {
                $existingItem->product?->increment('stock_quantity', $existingItem->quantity);
            }

            // Step 2: Calculate new items and deduct stock
            $totalQuantity = 0;
            $subtotalSale = 0.0;
            $totalPurchase = 0.0;
            $totalOther = 0.0;
            $itemsToCreate = [];

            $isPromotion = (bool) ($validated['is_promotion'] ?? false);
            $discountAmount = max(0.0, (float) ($validated['discount_amount'] ?? 0));

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
                $otherRate = isset($itemData['unit_other_rate']) && $itemData['unit_other_rate'] !== null && $itemData['unit_other_rate'] !== ''
                    ? max(0.0, (float) $itemData['unit_other_rate'])
                    : (float) $product->other_rate;
                $unitProfit = round($saleRate - $purchaseRate - $otherRate, 2);

                $lineSubtotal = round($saleRate * $qty, 2);
                $lineProfit = round($unitProfit * $qty, 2);

                $totalQuantity += $qty;
                $subtotalSale += $lineSubtotal;
                $totalPurchase += round($purchaseRate * $qty, 2);
                $totalOther += round($otherRate * $qty, 2);

                $itemsToCreate[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'unit_purchase_rate' => $purchaseRate,
                    'unit_sale_rate' => $saleRate,
                    'unit_other_rate' => $otherRate,
                    'unit_profit' => $unitProfit,
                    'subtotal_sale' => $lineSubtotal,
                    'subtotal_profit' => $lineProfit,
                ];
            }

            // Calculate amounts
            if ($isPromotion) {
                $finalSaleAmount = 0.0;
                $finalPurchaseCost = 0.0;
                $finalOtherCost = 0.0;
                $finalProfit = 0.0;
                $discountAmount = $subtotalSale;
            } else {
                $finalSaleAmount = max(0.0, round($subtotalSale - $discountAmount, 2));
                $finalPurchaseCost = $totalPurchase;
                $finalOtherCost = $totalOther;
                $finalProfit = round($finalSaleAmount - $totalPurchase - $totalOther, 2);
            }

            // Delete old items and insert updated ones
            $checkout->items()->delete();

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

                // Deduct newly assigned stock
                $item['product']->decrement('stock_quantity', $item['quantity']);

                StockMovement::create([
                    'product_id' => $item['product']->id,
                    'type' => 'CHECK_OUT',
                    'quantity' => -$item['quantity'],
                    'reference' => 'Order #'.$checkout->order_number.' (Updated)',
                    'notes' => 'Adjusted during order editing',
                ]);
            }

            // Handle date transitions
            $status = $validated['status'];
            $deliveredAt = ! empty($validated['delivered_at']) ? Carbon::parse($validated['delivered_at']) : $checkout->delivered_at;
            $receivedAt = ! empty($validated['received_at']) ? Carbon::parse($validated['received_at']) : $checkout->received_at;

            if ($status === Checkout::STATUS_DELIVERED && empty($deliveredAt)) {
                $deliveredAt = now();
            }
            if ($status === Checkout::STATUS_RECEIVED && empty($receivedAt)) {
                $receivedAt = now();
            }

            $orderedAt = ! empty($validated['ordered_at']) ? Carbon::parse($validated['ordered_at']) : $checkout->ordered_at;
            $expectedDeliveryDate = ! empty($validated['expected_delivery_date']) ? Carbon::parse($validated['expected_delivery_date'])->toDateString() : null;

            $checkout->update([
                'customer_name' => $validated['customer_name'],
                'customer_address' => $validated['customer_address'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'enquiry_from' => $validated['enquiry_from'],
                'is_promotion' => $isPromotion,
                'subtotal_amount' => $subtotalSale,
                'discount_amount' => $discountAmount,
                'status' => $status,
                'ordered_at' => $orderedAt,
                'expected_delivery_date' => $expectedDeliveryDate,
                'delivered_at' => $deliveredAt,
                'received_at' => $receivedAt,
                'total_quantity' => $totalQuantity,
                'total_sale_amount' => $finalSaleAmount,
                'total_purchase_cost' => $finalPurchaseCost,
                'total_other_cost' => $finalOtherCost,
                'total_profit' => $finalProfit,
                'notes' => $validated['notes'] ?? null,
            ]);

            ActivityLog::record('CHECKOUT_UPDATED', "Updated Order #{$checkout->order_number} for {$checkout->customer_name}. Status: {$checkout->status_label}");
        });

        return redirect()->route('checkouts.show', $checkout)->with('success', "Order #{$checkout->order_number} updated successfully.");
    }

    public function destroy(Checkout $checkout): RedirectResponse
    {
        $orderNumber = $checkout->order_number;
        $customerName = $checkout->customer_name;

        DB::transaction(function () use ($checkout, $orderNumber, $customerName) {
            // Restore inventory stock for each product in this checkout
            foreach ($checkout->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);

                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'type' => 'RETURN',
                        'quantity' => $item->quantity,
                        'reference' => 'Cancelled Order #'.$orderNumber,
                        'notes' => 'Stock restored after order deletion for '.$customerName,
                    ]);
                }
            }

            $checkout->delete();

            ActivityLog::record('CHECKOUT_DELETED', "Deleted Order #{$orderNumber} and restored product inventory.");
        });

        return redirect()->route('checkouts.index')->with('success', "Order #{$orderNumber} deleted and product stock restored successfully.");
    }

    public function quickUpdateStatus(Request $request, Checkout $checkout): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ordered,waiting_for_delivery,delivered,received,cancelled'],
        ]);

        $newStatus = $validated['status'];
        $updates = ['status' => $newStatus];

        if ($newStatus === Checkout::STATUS_DELIVERED && empty($checkout->delivered_at)) {
            $updates['delivered_at'] = now();
        }

        if ($newStatus === Checkout::STATUS_RECEIVED && empty($checkout->received_at)) {
            $updates['received_at'] = now();
            if (empty($checkout->delivered_at)) {
                $updates['delivered_at'] = now();
            }
        }

        $checkout->update($updates);

        ActivityLog::record('CHECKOUT_STATUS_CHANGED', "Order #{$checkout->order_number} status changed to {$checkout->status_label}.");

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Order #{$checkout->order_number} status updated to {$checkout->status_label}",
                'status' => $checkout->status,
                'status_label' => $checkout->status_label,
            ]);
        }

        return back()->with('success', "Order #{$checkout->order_number} status updated to {$checkout->status_label}.");
    }

    public function clientReceipt(Checkout $checkout): View
    {
        $checkout->load(['items.product']);

        return view('checkouts.receipt', compact('checkout'));
    }
}
