<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class ProductApiController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_number', 'like', "%{$search}%")
                    ->orWhere('quality', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status === 'in_stock') {
                $query->where('stock_quantity', '>', 0);
            } elseif ($status === 'out_of_stock') {
                $query->where('stock_quantity', '<=', 0);
            } elseif ($status === 'low_stock') {
                $query->where('stock_quantity', '>', 0)
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            }
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        return ProductResource::collection($query->paginate($perPage));
    }

    public function show(string $identifier): JsonResponse|ProductResource
    {
        // Try finding by ID or by product_number / barcode
        $product = Product::where('id', $identifier)
            ->orWhere('product_number', $identifier)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found with ID or code: '.$identifier,
            ], 404);
        }

        return new ProductResource($product);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = Product::processImage($request->file('photo'));
        }

        unset($validated['photo']);

        $product = Product::create($validated);

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            if ($product->photo_path && ! str_starts_with($product->photo_path, 'data:') && Storage::disk('public')->exists($product->photo_path)) {
                Storage::disk('public')->delete($product->photo_path);
            }
            $validated['photo_path'] = Product::processImage($request->file('photo'));
        }

        unset($validated['photo']);

        $product->update($validated);

        return new ProductResource($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        if ($product->photo_path && ! str_starts_with($product->photo_path, 'data:') && Storage::disk('public')->exists($product->photo_path)) {
            Storage::disk('public')->delete($product->photo_path);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }
}
