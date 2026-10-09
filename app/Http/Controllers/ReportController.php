<?php

namespace App\Http\Controllers;

use App\Models\Checkout;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $productSalesData = $this->getProductSalesData($selectedYear, $selectedMonth);

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
            'outOfStockProducts',
            'productSalesData'
        ));
    }

    public function exportFinancialCsv(Request $request): StreamedResponse
    {
        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = $request->input('month');

        $checkoutsQuery = Checkout::nonPromotional()->whereYear('created_at', $selectedYear);
        if ($selectedMonth) {
            $checkoutsQuery->whereMonth('created_at', (int) $selectedMonth);
        }

        $allYearCheckouts = $checkoutsQuery->get();
        $filename = "CherryAdorn_Financial_Report_{$selectedYear}".($selectedMonth ? "_M{$selectedMonth}" : '').'.csv';

        return response()->streamDownload(function () use ($selectedYear, $selectedMonth, $allYearCheckouts) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens INR and accented characters cleanly
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Cherry Adorn - The Little Jewellery Studio']);
            fputcsv($handle, ['Financial & Sales Performance Report', "Year: {$selectedYear}".($selectedMonth ? ", Month: {$selectedMonth}" : '')]);
            fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Month #',
                'Month Name',
                'Orders Count',
                'Units Sold',
                'Gross Sales (₹)',
                'Cost of Goods (₹)',
                'Other Expenses (₹)',
                'Net Profit (₹)',
                'Profit Margin (%)',
            ]);

            $totalOrders = 0;
            $totalUnits = 0;
            $totalSales = 0.0;
            $totalCost = 0.0;
            $totalOther = 0.0;
            $totalProfit = 0.0;

            for ($m = 1; $m <= 12; $m++) {
                if ($selectedMonth && (int) $selectedMonth !== $m) {
                    continue;
                }

                $monthKey = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
                $monthName = Carbon::createFromDate($selectedYear, $m, 1)->format('F');

                $monthOrders = $allYearCheckouts->filter(function ($c) use ($monthKey) {
                    return Carbon::parse($c->created_at)->format('m') === $monthKey;
                });

                $mSales = (float) $monthOrders->sum('total_sale_amount');
                $mCost = (float) $monthOrders->sum('total_purchase_cost');
                $mOther = (float) $monthOrders->sum('total_other_cost');
                $mProfit = (float) $monthOrders->sum('total_profit');
                $mOrders = $monthOrders->count();
                $mUnits = (int) $monthOrders->sum('total_quantity');
                $mMargin = $mSales > 0 ? round(($mProfit / $mSales) * 100, 1) : 0;

                $totalOrders += $mOrders;
                $totalUnits += $mUnits;
                $totalSales += $mSales;
                $totalCost += $mCost;
                $totalOther += $mOther;
                $totalProfit += $mProfit;

                fputcsv($handle, [
                    $m,
                    $monthName,
                    $mOrders,
                    $mUnits,
                    number_format($mSales, 2, '.', ''),
                    number_format($mCost, 2, '.', ''),
                    number_format($mOther, 2, '.', ''),
                    number_format($mProfit, 2, '.', ''),
                    $mMargin.'%',
                ]);
            }

            fputcsv($handle, []);
            $totalMargin = $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 1) : 0;
            fputcsv($handle, [
                'TOTAL',
                'Full Period Summary',
                $totalOrders,
                $totalUnits,
                number_format($totalSales, 2, '.', ''),
                number_format($totalCost, 2, '.', ''),
                number_format($totalOther, 2, '.', ''),
                number_format($totalProfit, 2, '.', ''),
                $totalMargin.'%',
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportFinancialPdf(Request $request): View
    {
        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = $request->input('month');

        $checkoutsQuery = Checkout::nonPromotional()->whereYear('created_at', $selectedYear);
        if ($selectedMonth) {
            $checkoutsQuery->whereMonth('created_at', (int) $selectedMonth);
        }

        $allYearCheckouts = $checkoutsQuery->get();

        $monthlyData = [];
        for ($m = 1; $m <= 12; $m++) {
            if ($selectedMonth && (int) $selectedMonth !== $m) {
                continue;
            }
            $monthKey = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $monthName = Carbon::createFromDate($selectedYear, $m, 1)->format('F');

            $monthOrders = $allYearCheckouts->filter(function ($c) use ($monthKey) {
                return Carbon::parse($c->created_at)->format('m') === $monthKey;
            });

            $mSales = (float) $monthOrders->sum('total_sale_amount');
            $mCost = (float) $monthOrders->sum('total_purchase_cost');
            $mOther = (float) $monthOrders->sum('total_other_cost');
            $mProfit = (float) $monthOrders->sum('total_profit');

            $monthlyData[$m] = [
                'month_name' => $monthName,
                'orders_count' => $monthOrders->count(),
                'items_sold' => (int) $monthOrders->sum('total_quantity'),
                'sales' => $mSales,
                'cost' => $mCost,
                'other' => $mOther,
                'profit' => $mProfit,
                'margin' => $mSales > 0 ? round(($mProfit / $mSales) * 100, 1) : 0,
            ];
        }

        $totalSales = (float) $allYearCheckouts->sum('total_sale_amount');
        $totalCost = (float) $allYearCheckouts->sum('total_purchase_cost');
        $totalOther = (float) $allYearCheckouts->sum('total_other_cost');
        $totalProfit = (float) $allYearCheckouts->sum('total_profit');
        $totalOrders = $allYearCheckouts->count();
        $totalUnits = (int) $allYearCheckouts->sum('total_quantity');

        return view('reports.pdf.financial', compact(
            'selectedYear',
            'selectedMonth',
            'monthlyData',
            'totalSales',
            'totalCost',
            'totalOther',
            'totalProfit',
            'totalOrders',
            'totalUnits'
        ));
    }

    public function exportProductSalesCsv(Request $request): StreamedResponse
    {
        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = $request->input('month');

        $productSales = $this->getProductSalesData($selectedYear, $selectedMonth);
        $filename = "CherryAdorn_Product_Sales_{$selectedYear}".($selectedMonth ? "_M{$selectedMonth}" : '').'.csv';

        return response()->streamDownload(function () use ($selectedYear, $selectedMonth, $productSales) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Cherry Adorn - The Little Jewellery Studio']);
            fputcsv($handle, ['Product Sales & Profit Performance Report', "Year: {$selectedYear}".($selectedMonth ? ", Month: {$selectedMonth}" : '')]);
            fputcsv($handle, ['Generated At', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Product Code',
                'Product Name',
                'Current Stock',
                'Sale Rate (₹)',
                'Purchase Rate (₹)',
                'Default Overhead (₹)',
                'Units Sold',
                'Total Revenue (₹)',
                'Total Profit Generated (₹)',
            ]);

            $totalSold = 0;
            $totalRevenue = 0.0;
            $totalProfit = 0.0;

            foreach ($productSales as $item) {
                $totalSold += $item->units_sold;
                $totalRevenue += $item->total_revenue;
                $totalProfit += $item->total_profit_generated;

                fputcsv($handle, [
                    $item->product_number,
                    $item->name,
                    $item->stock_quantity,
                    number_format($item->sale_rate, 2, '.', ''),
                    number_format($item->purchase_rate, 2, '.', ''),
                    number_format($item->other_rate, 2, '.', ''),
                    $item->units_sold,
                    number_format($item->total_revenue, 2, '.', ''),
                    number_format($item->total_profit_generated, 2, '.', ''),
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL',
                'All Products Summary',
                '',
                '',
                '',
                '',
                $totalSold,
                number_format($totalRevenue, 2, '.', ''),
                number_format($totalProfit, 2, '.', ''),
            ]);

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportProductSalesPdf(Request $request): View
    {
        $selectedYear = (int) $request->input('year', date('Y'));
        $selectedMonth = $request->input('month');

        $productSales = $this->getProductSalesData($selectedYear, $selectedMonth);

        return view('reports.pdf.product_sales', compact('selectedYear', 'selectedMonth', 'productSales'));
    }

    protected function getProductSalesData(int $selectedYear, ?string $selectedMonth)
    {
        return Product::withCount(['checkoutItems as units_sold' => function ($q) use ($selectedYear, $selectedMonth) {
            $q->whereHas('checkout', function ($c) use ($selectedYear, $selectedMonth) {
                $c->where('is_promotion', false)
                    ->whereYear('created_at', $selectedYear);
                if ($selectedMonth) {
                    $c->whereMonth('created_at', (int) $selectedMonth);
                }
            });
        }])->withSum(['checkoutItems as total_revenue' => function ($q) use ($selectedYear, $selectedMonth) {
            $q->whereHas('checkout', function ($c) use ($selectedYear, $selectedMonth) {
                $c->where('is_promotion', false)
                    ->whereYear('created_at', $selectedYear);
                if ($selectedMonth) {
                    $c->whereMonth('created_at', (int) $selectedMonth);
                }
            });
        }], 'subtotal_sale')
            ->withSum(['checkoutItems as total_profit_generated' => function ($q) use ($selectedYear, $selectedMonth) {
                $q->whereHas('checkout', function ($c) use ($selectedYear, $selectedMonth) {
                    $c->where('is_promotion', false)
                        ->whereYear('created_at', $selectedYear);
                    if ($selectedMonth) {
                        $c->whereMonth('created_at', (int) $selectedMonth);
                    }
                });
            }], 'subtotal_profit')
            ->orderByDesc('units_sold')
            ->orderBy('name')
            ->get();
    }
}
