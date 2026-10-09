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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->buildFilteredQuery($request);

        $perPageInput = $request->input('per_page', '20');
        $perPage = $perPageInput === 'all' ? 1000 : (int) $perPageInput;
        if (! in_array($perPage, [10, 20, 25, 50, 100, 1000], true)) {
            $perPage = 20;
        }
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

    public function exportCsv(Request $request): StreamedResponse
    {
        $products = $this->buildFilteredQuery($request)->get();
        $filename = 'CherryAdorn_Inventory_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Cherry Adorn - The Little Jewellery Studio']);
            fputcsv($handle, ['Products Inventory Catalog Export', 'Generated At: '.now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Product Code',
                'Product Name',
                'Quality / Grade',
                'Stock Qty',
                'Low Stock Threshold',
                'Status',
                'Purchase Rate (₹)',
                'Sale Rate (₹)',
                'Overhead (₹)',
                'Profit / Unit (₹)',
                'Total Purchase Value (₹)',
                'Total Sale Value (₹)',
                'Potential Profit (₹)',
            ]);

            $totalUnits = 0;
            $totalPurchaseVal = 0.0;
            $totalSaleVal = 0.0;
            $totalProfitVal = 0.0;

            foreach ($products as $p) {
                $pVal = $p->stock_quantity * $p->purchase_rate;
                $sVal = $p->stock_quantity * $p->sale_rate;
                $profVal = $p->stock_quantity * $p->profit_per_unit;

                $totalUnits += $p->stock_quantity;
                $totalPurchaseVal += $pVal;
                $totalSaleVal += $sVal;
                $totalProfitVal += $profVal;

                fputcsv($handle, [
                    $p->product_number,
                    $p->name,
                    $p->quality ?? 'Standard',
                    $p->stock_quantity,
                    $p->low_stock_threshold,
                    $p->stock_status,
                    number_format($p->purchase_rate, 2, '.', ''),
                    number_format($p->sale_rate, 2, '.', ''),
                    number_format($p->other_rate, 2, '.', ''),
                    number_format($p->profit_per_unit, 2, '.', ''),
                    number_format($pVal, 2, '.', ''),
                    number_format($sVal, 2, '.', ''),
                    number_format($profVal, 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                'All Catalog Summary',
                '',
                $totalUnits,
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($totalPurchaseVal, 2, '.', ''),
                number_format($totalSaleVal, 2, '.', ''),
                number_format($totalProfitVal, 2, '.', ''),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request): View
    {
        $products = $this->buildFilteredQuery($request)->get();

        return view('products.pdf.catalog', compact('products'));
    }

    protected function buildFilteredQuery(Request $request)
    {
        $query = Product::query();

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

        $sort = $request->input('sort', 'latest');
        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'stock_desc':
                $query->orderBy('stock_quantity', 'desc');
                break;
            case 'stock_asc':
                $query->orderBy('stock_quantity', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('sale_rate', 'desc');
                break;
            case 'price_asc':
                $query->orderBy('sale_rate', 'asc');
                break;
            case 'latest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        return $query;
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
        } elseif (! empty($validated['photo_url_input'])) {
            $validated['photo_path'] = $validated['photo_url_input'];
        }

        unset($validated['photo'], $validated['photo_url_input']);

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
        } elseif (! empty($validated['photo_url_input'])) {
            $validated['photo_path'] = $validated['photo_url_input'];
        }

        unset($validated['photo'], $validated['photo_url_input']);

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
