<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = $request->input('month'); // optional specific month

        // Base checkouts query for financial analytics (promotions excluded from revenue & profit totals)
        $checkoutsQuery = Checkout::nonPromotional()->whereYear('created_at', $selectedYear);

        if ($selectedMonth) {
            $checkoutsQuery->whereMonth('created_at', (int) $selectedMonth);
        }

        $checkoutsForPeriod = $checkoutsQuery->get();

        $totalSales = $checkoutsForPeriod->sum('total_sale_amount');
        $totalPurchase = $checkoutsForPeriod->sum('total_purchase_cost');
        $totalOther = $checkoutsForPeriod->sum('total_other_cost');
        $totalProfit = $checkoutsForPeriod->sum('total_profit');
        $totalOrders = Checkout::whereYear('created_at', $selectedYear)
            ->when($selectedMonth, fn ($q) => $q->whereMonth('created_at', (int) $selectedMonth))
            ->count();
        $totalItemsSold = Checkout::whereYear('created_at', $selectedYear)
            ->when($selectedMonth, fn ($q) => $q->whereMonth('created_at', (int) $selectedMonth))
            ->sum('total_quantity');

        // All non-promotional checkouts in selected year to build 12-month revenue trend
        $allYearCheckouts = Checkout::nonPromotional()->whereYear('created_at', $selectedYear)->get();

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthKey = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $monthName = Carbon::createFromDate($selectedYear, $m, 1)->format('M');

            $monthOrders = $allYearCheckouts->filter(function ($c) use ($monthKey) {
                return Carbon::parse($c->created_at)->format('m') === $monthKey;
            });

            $monthlyData[$m] = [
                'month_name' => $monthName,
                'month_number' => $m,
                'sales' => (float) $monthOrders->sum('total_sale_amount'),
                'profit' => (float) $monthOrders->sum('total_profit'),
                'orders_count' => $monthOrders->count(),
                'items_sold' => (int) $monthOrders->sum('total_quantity'),
            ];
        }

        // Available years for dropdown
        $availableYears = Checkout::selectRaw('created_at')
            ->get()
            ->map(fn ($c) => (int) Carbon::parse($c->created_at)->format('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        if (! $availableYears->contains($selectedYear)) {
            $availableYears->push($selectedYear);
            $availableYears = $availableYears->sortDesc();
        }

        // Stock status analytics
        $inStockProducts = Product::where('stock_quantity', '>', 0)->get();
        $lowStockProducts = Product::where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->get();
        $outOfStockProducts = Product::where('stock_quantity', '<=', 0)->get();

        $stockStats = [
            'in_stock_count' => $inStockProducts->count(),
            'total_units_in_stock' => (int) $inStockProducts->sum('stock_quantity'),
            'total_inventory_purchase_value' => (float) $inStockProducts->sum(fn ($p) => $p->stock_quantity * $p->purchase_rate),
            'total_inventory_sale_value' => (float) $inStockProducts->sum(fn ($p) => $p->stock_quantity * $p->sale_rate),
            'potential_profit' => (float) $inStockProducts->sum(fn ($p) => $p->stock_quantity * $p->profit_per_unit),
            'low_stock_count' => $lowStockProducts->count(),
            'out_of_stock_count' => $outOfStockProducts->count(),
        ];

        return view('reports.index', compact(
            'selectedYear',
            'selectedMonth',
            'availableYears',
            'totalSales',
            'totalPurchase',
            'totalOther',
            'totalProfit',
            'totalOrders',
            'totalItemsSold',
            'monthlyData',
            'stockStats',
            'lowStockProducts',
            'outOfStockProducts'
        ));
    }
}
