<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInStockRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockApiController extends Controller
{
    public function checkIn(CheckInStockRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $product = DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            $product->increment('stock_quantity', $validated['quantity']);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'CHECK_IN',
                'quantity' => $validated['quantity'],
                'reference' => $validated['reference'] ?? 'Mobile App Check-in',
                'notes' => $validated['notes'] ?? null,
            ]);

            return $product->fresh();
        });

        return response()->json([
            'message' => "Successfully added {$validated['quantity']} units to {$product->name}.",
            'product' => new ProductResource($product),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $query = StockMovement::with('product')->latest();

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        $movements = $query->paginate(20);

        return response()->json($movements);
    }
}
