<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
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

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $perPage = $request->input('per_page') === 'all' ? 500 : (int) $request->input('per_page', 50);
        $products = $query->with('user')->paginate($perPage)->withQueryString();

        $stats = [
            'total' => Product::count(),
            'in_stock' => Product::where('stock_quantity', '>', 0)->count(),
            'low_stock' => Product::where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'out_of_stock' => Product::where('stock_quantity', '<=', 0)->count(),
        ];

        $users = User::orderBy('name')->get();

        return view('products.index', compact('products', 'stats', 'users'));
    }

    public function create(): View
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            $validated['photo_path'] = Product::processImage($request->file('photo'));
        }

        unset($validated['photo']);

        $product = Product::create($validated);

        ActivityLog::record('PRODUCT_CREATED', "Created product '{$product->name}' ({$product->product_number}) with {$product->stock_quantity} units.");

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' created successfully.");
    }

    public function edit(Product $product): View
    {
        return view('products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
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

        ActivityLog::record('PRODUCT_UPDATED', "Updated product '{$product->name}' ({$product->product_number}).");

        return redirect()->route('products.index')->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->photo_path && ! str_starts_with($product->photo_path, 'data:') && Storage::disk('public')->exists($product->photo_path)) {
            Storage::disk('public')->delete($product->photo_path);
        }

        $name = $product->name;
        $num = $product->product_number;
        $product->delete();

        ActivityLog::record('PRODUCT_DELETED', "Deleted product '{$name}' ({$num}).");

        return redirect()->route('products.index')->with('success', "Product '{$name}' deleted successfully.");
    }
}
