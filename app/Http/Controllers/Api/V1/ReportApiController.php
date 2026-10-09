<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $month = $request->input('month');

        $query = Checkout::nonPromotional()->whereYear('created_at', $year);

        if ($month) {
            $query->whereMonth('created_at', (int) $month);
        }

        $checkouts = $query->get();

        $allYearCheckouts = Checkout::nonPromotional()->whereYear('created_at', $year)->get();
        $monthlyBreakdown = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthKey = str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            $monthName = Carbon::createFromDate($year, $m, 1)->format('M');

            $mOrders = $allYearCheckouts->filter(function ($c) use ($monthKey) {
                return Carbon::parse($c->created_at)->format('m') === $monthKey;
            });

            $monthlyBreakdown[] = [
                'month' => $m,
                'month_name' => $monthName,
                'sales' => (float) $mOrders->sum('total_sale_amount'),
                'profit' => (float) $mOrders->sum('total_profit'),
                'orders_count' => $mOrders->count(),
                'items_sold' => (int) $mOrders->sum('total_quantity'),
            ];
        }

        return response()->json([
            'year' => $year,
            'month' => $month ? (int) $month : null,
            'totals' => [
                'total_sales' => (float) $checkouts->sum('total_sale_amount'),
                'total_purchase_cost' => (float) $checkouts->sum('total_purchase_cost'),
                'total_other_cost' => (float) $checkouts->sum('total_other_cost'),
                'total_profit' => (float) $checkouts->sum('total_profit'),
                'orders_count' => $checkouts->count(),
                'items_sold' => (int) $checkouts->sum('total_quantity'),
            ],
            'monthly_breakdown' => $monthlyBreakdown,
        ]);
    }

    public function stockStatus(): JsonResponse
    {
        $inStock = Product::where('stock_quantity', '>', 0)->get();
        $lowStock = Product::where('stock_quantity', '>', 0)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->get();
        $outOfStock = Product::where('stock_quantity', '<=', 0)->get();

        return response()->json([
            'in_stock_count' => $inStock->count(),
            'total_units_in_stock' => (int) $inStock->sum('stock_quantity'),
            'inventory_cost_value' => (float) $inStock->sum(fn ($p) => $p->stock_quantity * $p->purchase_rate),
            'inventory_sale_value' => (float) $inStock->sum(fn ($p) => $p->stock_quantity * $p->sale_rate),
            'potential_profit' => (float) $inStock->sum(fn ($p) => $p->stock_quantity * $p->profit_per_unit),
            'low_stock_count' => $lowStock->count(),
            'out_of_stock_count' => $outOfStock->count(),
        ]);
    }
}
