<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCheckoutRequest;
use App\Http\Requests\UpdateCheckoutRequest;
use App\Http\Resources\CheckoutResource;
use App\Models\ActivityLog;
use App\Models\Checkout;
use App\Models\CheckoutItem;
use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Checkout::with('items.product')->latest('ordered_at');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_address', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%");
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

        return CheckoutResource::collection($query->paginate(20));
    }

    public function show(Checkout $checkout): CheckoutResource
    {
        $checkout->load('items.product');

        return new CheckoutResource($checkout);
    }

    public function store(StoreCheckoutRequest $request): JsonResponse
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

            // Step 1: Pre-calculate required quantities across all direct products and combo components
            $requiredQuantities = [];
            foreach ($validated['items'] as $itemData) {
                $qty = (int) $itemData['quantity'];
                $requiredQuantities[$itemData['product_id']] = ($requiredQuantities[$itemData['product_id']] ?? 0) + $qty;

                $prod = Product::with('comboItems')->find($itemData['product_id']);
                if ($prod && $prod->is_combo) {
                    foreach ($prod->comboItems as $ci) {
                        $compQty = $ci->quantity * $qty;
                        $requiredQuantities[$ci->item_product_id] = ($requiredQuantities[$ci->item_product_id] ?? 0) + $compQty;
                    }
                }
            }

            // Step 2: Lock products and validate stock sufficiency
            $lockedProducts = [];
            foreach (array_keys($requiredQuantities) as $productId) {
                $lockedProducts[$productId] = Product::lockForUpdate()->with('comboItems.itemProduct')->findOrFail($productId);
                $needed = $requiredQuantities[$productId];
                if ($lockedProducts[$productId]->stock_quantity < $needed) {
                    throw ValidationException::withMessages([
                        'items' => ["Not enough stock for {$lockedProducts[$productId]->name}. Required: {$needed}, Available: {$lockedProducts[$productId]->stock_quantity}"],
                    ]);
                }
            }

            foreach ($validated['items'] as $itemData) {
                $product = $lockedProducts[$itemData['product_id']];
                $qty = (int) $itemData['quantity'];

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

                // Deduct stock for product/combo
                $item['product']->decrement('stock_quantity', $item['quantity']);

                // Record stock movement
                StockMovement::create([
                    'product_id' => $item['product']->id,
                    'type' => 'CHECK_OUT',
                    'quantity' => -$item['quantity'],
                    'reference' => 'Order #'.$checkout->order_number,
                    'notes' => ($isPromotion ? '[PROMOTION] ' : '').'Customer: '.$checkout->customer_name,
                ]);

                // If combo, deduct component stocks and record movements
                if ($item['product']->is_combo) {
                    foreach ($item['product']->comboItems as $comboItem) {
                        $compDeductQty = $comboItem->quantity * $item['quantity'];
                        $componentProduct = $lockedProducts[$comboItem->item_product_id] ?? Product::find($comboItem->item_product_id);
                        if ($componentProduct) {
                            $componentProduct->decrement('stock_quantity', $compDeductQty);
                            StockMovement::create([
                                'product_id' => $componentProduct->id,
                                'type' => 'CHECK_OUT',
                                'quantity' => -$compDeductQty,
                                'reference' => 'Order #'.$checkout->order_number,
                                'notes' => ($isPromotion ? '[PROMOTION] ' : '').'[COMBO: '.$item['product']->name.'] Customer: '.$checkout->customer_name,
                            ]);
                        }
                    }
                }
            }

            return $checkout->load('items.product.comboItems.itemProduct');
        });

        return (new CheckoutResource($checkout))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCheckoutRequest $request, Checkout $checkout): CheckoutResource
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $checkout) {
            // Restore previous stock
            foreach ($checkout->items as $existingItem) {
                if ($existingItem->product) {
                    $existingItem->product->increment('stock_quantity', $existingItem->quantity);

                    if ($existingItem->product->is_combo) {
                        foreach ($existingItem->product->comboItems as $comboItem) {
                            $comboItem->itemProduct?->increment('stock_quantity', $comboItem->quantity * $existingItem->quantity);
                        }
                    }
                }
            }

            // Pre-calculate required quantities across all direct products and combo components
            $requiredQuantities = [];
            foreach ($validated['items'] as $itemData) {
                $qty = (int) $itemData['quantity'];
                $requiredQuantities[$itemData['product_id']] = ($requiredQuantities[$itemData['product_id']] ?? 0) + $qty;

                $prod = Product::with('comboItems')->find($itemData['product_id']);
                if ($prod && $prod->is_combo) {
                    foreach ($prod->comboItems as $ci) {
                        $compQty = $ci->quantity * $qty;
                        $requiredQuantities[$ci->item_product_id] = ($requiredQuantities[$ci->item_product_id] ?? 0) + $compQty;
                    }
                }
            }

            // Lock products and validate stock sufficiency
            $lockedProducts = [];
            foreach (array_keys($requiredQuantities) as $productId) {
                $lockedProducts[$productId] = Product::lockForUpdate()->with('comboItems.itemProduct')->findOrFail($productId);
                $needed = $requiredQuantities[$productId];
                if ($lockedProducts[$productId]->stock_quantity < $needed) {
                    throw ValidationException::withMessages([
                        'items' => ["Not enough stock for {$lockedProducts[$productId]->name}. Required: {$needed}, Available: {$lockedProducts[$productId]->stock_quantity}"],
                    ]);
                }
            }

            $totalQuantity = 0;
            $subtotalSale = 0.0;
            $totalPurchase = 0.0;
            $totalOther = 0.0;
            $itemsToCreate = [];

            $isPromotion = (bool) ($validated['is_promotion'] ?? false);
            $discountAmount = max(0.0, (float) ($validated['discount_amount'] ?? 0));

            foreach ($validated['items'] as $itemData) {
                $product = $lockedProducts[$itemData['product_id']];
                $qty = (int) $itemData['quantity'];

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

                $item['product']->decrement('stock_quantity', $item['quantity']);

                StockMovement::create([
                    'product_id' => $item['product']->id,
                    'type' => 'CHECK_OUT',
                    'quantity' => -$item['quantity'],
                    'reference' => 'Order #'.$checkout->order_number.' (Updated)',
                    'notes' => 'Adjusted during order editing',
                ]);

                // If combo, deduct component stocks and record movements
                if ($item['product']->is_combo) {
                    foreach ($item['product']->comboItems as $comboItem) {
                        $compDeductQty = $comboItem->quantity * $item['quantity'];
                        $componentProduct = $lockedProducts[$comboItem->item_product_id] ?? Product::find($comboItem->item_product_id);
                        if ($componentProduct) {
                            $componentProduct->decrement('stock_quantity', $compDeductQty);
                            StockMovement::create([
                                'product_id' => $componentProduct->id,
                                'type' => 'CHECK_OUT',
                                'quantity' => -$compDeductQty,
                                'reference' => 'Order #'.$checkout->order_number.' (Updated)',
                                'notes' => '[COMBO: '.$item['product']->name.'] Adjusted during order editing',
                            ]);
                        }
                    }
                }
            }

            $status = $validated['status'];
            $deliveredAt = ! empty($validated['delivered_at']) ? Carbon::parse($validated['delivered_at']) : $checkout->delivered_at;
            $receivedAt = ! empty($validated['received_at']) ? Carbon::parse($validated['received_at']) : $checkout->received_at;

            if ($status === Checkout::STATUS_DELIVERED && empty($deliveredAt)) {
                $deliveredAt = now();
            }
            if ($status === Checkout::STATUS_RECEIVED && empty($receivedAt)) {
                $receivedAt = now();
            }

            $checkout->update([
                'customer_name' => $validated['customer_name'],
                'customer_address' => $validated['customer_address'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'enquiry_from' => $validated['enquiry_from'],
                'is_promotion' => $isPromotion,
                'subtotal_amount' => $subtotalSale,
                'discount_amount' => $discountAmount,
                'status' => $status,
                'ordered_at' => ! empty($validated['ordered_at']) ? Carbon::parse($validated['ordered_at']) : $checkout->ordered_at,
                'expected_delivery_date' => ! empty($validated['expected_delivery_date']) ? Carbon::parse($validated['expected_delivery_date'])->toDateString() : null,
                'delivered_at' => $deliveredAt,
                'received_at' => $receivedAt,
                'total_quantity' => $totalQuantity,
                'total_sale_amount' => $finalSaleAmount,
                'total_purchase_cost' => $finalPurchaseCost,
                'total_other_cost' => $finalOtherCost,
                'total_profit' => $finalProfit,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return new CheckoutResource($checkout->fresh('items.product.comboItems.itemProduct'));
    }

    public function destroy(Checkout $checkout): JsonResponse
    {
        $orderNumber = $checkout->order_number;

        DB::transaction(function () use ($checkout, $orderNumber) {
            foreach ($checkout->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock_quantity', $item->quantity);

                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'type' => 'RETURN',
                        'quantity' => $item->quantity,
                        'reference' => 'Cancelled Order #'.$orderNumber,
                        'notes' => 'Stock restored after order deletion',
                    ]);

                    if ($item->product->is_combo) {
                        foreach ($item->product->comboItems as $comboItem) {
                            if ($comboItem->itemProduct) {
                                $compRestoreQty = $comboItem->quantity * $item->quantity;
                                $comboItem->itemProduct->increment('stock_quantity', $compRestoreQty);

                                StockMovement::create([
                                    'product_id' => $comboItem->item_product_id,
                                    'type' => 'RETURN',
                                    'quantity' => $compRestoreQty,
                                    'reference' => 'Cancelled Order #'.$orderNumber,
                                    'notes' => '[COMBO: '.$item->product->name.'] Stock restored after order deletion',
                                ]);
                            }
                        }
                    }
                }
            }

            $checkout->delete();

            ActivityLog::record('CHECKOUT_DELETED', "Deleted Order #{$orderNumber} and restored stock via API.");
        });

        return response()->json([
            'message' => "Order #{$orderNumber} deleted and stock restored successfully.",
        ]);
    }

    public function updateStatus(Request $request, Checkout $checkout): JsonResponse
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

        return response()->json([
            'message' => "Order #{$checkout->order_number} status updated to {$checkout->status_label}",
            'checkout' => new CheckoutResource($checkout),
        ]);
    }
}
