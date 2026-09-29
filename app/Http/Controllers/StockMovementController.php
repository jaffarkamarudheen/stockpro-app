<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckInStockRequest;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View
    {
        $query = StockMovement::with('product')->latest();

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_number', 'like', "%{$search}%");
            });
        }

        $movements = $query->paginate(15)->withQueryString();
        $products = Product::orderBy('name')->get();

        return view('stock.index', compact('movements', 'products'));
    }

    public function store(CheckInStockRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            $product = Product::findOrFail($validated['product_id']);
            $product->increment('stock_quantity', $validated['quantity']);

            StockMovement::create([
                'product_id' => $product->id,
                'type' => 'CHECK_IN',
                'quantity' => $validated['quantity'],
                'reference' => $validated['reference'] ?? 'Manual Check-in',
                'notes' => $validated['notes'] ?? null,
            ]);

            ActivityLog::record('STOCK_CHECK_IN', "Checked in {$validated['quantity']} units for '{$product->name}' ({$product->product_number}). New stock: {$product->stock_quantity}");
        });

        return redirect()->back()->with('success', 'Stock checked in successfully.');
    }
}
