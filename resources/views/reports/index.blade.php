@extends('layouts.app')

@section('title', 'Reports & Financial Analytics')
@section('page_heading', 'Reports & Financial Analytics')

@section('content')
<div class="space-y-6">
    <!-- Filter Bar & Export Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col xl:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-800">Financial & Inventory Reports</h3>
            <p class="text-xs text-slate-500">Yearly & monthly sales performance, profit analysis, and in-stock / out-of-stock status.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5">
                    <label for="year" class="text-xs font-semibold text-slate-600">Year:</label>
                    <select id="year" name="year" class="py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        @foreach ($availableYears as $year)
                            <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-1.5">
                    <label for="month" class="text-xs font-semibold text-slate-600">Month:</label>
                    <select id="month" name="month" class="py-1.5 px-2.5 text-xs rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">All Months (Full Year)</option>
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $selectedMonth == $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <button type="submit" class="px-3 py-1.5 text-xs font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                    Filter
                </button>
            </form>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <div class="flex items-center gap-2">
                <a href="{{ route('reports.export.financial.csv', ['year' => $selectedYear, 'month' => $selectedMonth]) }}"
                   class="px-3 py-1.5 text-xs font-bold rounded-lg text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition flex items-center gap-1.5"
                   title="Export Financial Summary to Microsoft Excel (CSV)">
                    <i class="fa-solid fa-file-excel"></i> Excel (CSV)
                </a>
                <a href="{{ route('reports.export.financial.pdf', ['year' => $selectedYear, 'month' => $selectedMonth]) }}"
                   class="px-3 py-1.5 text-xs font-bold rounded-lg text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition flex items-center gap-1.5"
                   title="Download Financial Report PDF">
                    <i class="fa-solid fa-file-pdf"></i> Download PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- Total Sales Revenue -->
        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Sales</span>
            <div class="text-2xl font-bold text-slate-800 mt-1">₹{{ number_format($totalSales, 2) }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $totalOrders }} orders ({{ $totalItemsSold }} units)</div>
        </div>

        <!-- Purchase Cost -->
        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Goods Purchase Cost</span>
            <div class="text-2xl font-bold text-slate-800 mt-1">₹{{ number_format($totalPurchase, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">Cost of stock sold</div>
        </div>

        <!-- Other Expenses -->
        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Other Rates & Overhead</span>
            <div class="text-2xl font-bold text-slate-800 mt-1">₹{{ number_format($totalOther, 2) }}</div>
            <div class="text-xs text-slate-400 mt-1">Shipping & other charges</div>
        </div>

        <!-- Net Profit -->
        <div class="p-5 rounded-xl bg-gradient-to-br from-emerald-600 to-teal-700 text-white shadow-md">
            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-100">Calculated Net Profit</span>
            <div class="text-2xl font-black mt-1">+₹{{ number_format($totalProfit, 2) }}</div>
            <div class="text-xs text-emerald-100 mt-1">
                @if ($totalSales > 0)
                    Margin: {{ number_format(($totalProfit / $totalSales) * 100, 1) }}%
                @else
                    Margin: 0%
                @endif
            </div>
        </div>
    </div>

    <!-- Stock Status & Inventory Valuation Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Stock Overview Stats -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Stock Inventory Valuation</h4>
                <i class="fa-solid fa-warehouse text-indigo-500"></i>
            </div>

            <div class="space-y-3 text-sm">
                <div class="flex justify-between items-center py-1 border-b border-slate-100">
                    <span class="text-slate-500">Products in Stock:</span>
                    <span class="font-bold text-slate-800">{{ $stockStats['in_stock_count'] }} products</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100">
                    <span class="text-slate-500">Total Units in Stock:</span>
                    <span class="font-bold text-slate-800">{{ $stockStats['total_units_in_stock'] }} units</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100">
                    <span class="text-slate-500">Inventory Cost Value:</span>
                    <span class="font-bold text-slate-800">₹{{ number_format($stockStats['total_inventory_purchase_value'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-1 border-b border-slate-100">
                    <span class="text-slate-500">Inventory Retail Value:</span>
                    <span class="font-bold text-slate-800">₹{{ number_format($stockStats['total_inventory_sale_value'], 2) }}</span>
                </div>
                <div class="flex justify-between items-center py-1 text-emerald-700 font-semibold bg-emerald-50 px-2 rounded-lg">
                    <span>Unrealized Potential Profit:</span>
                    <span>+₹{{ number_format($stockStats['potential_profit'], 2) }}</span>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-2">
                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-center">
                    <span class="text-xs text-amber-700 font-medium">Low Stock Items</span>
                    <div class="text-xl font-bold text-amber-800 mt-0.5">{{ $stockStats['low_stock_count'] }}</div>
                </div>
                <div class="p-3 rounded-lg bg-rose-50 border border-rose-200 text-center">
                    <span class="text-xs text-rose-700 font-medium">Out of Stock</span>
                    <div class="text-xl font-bold text-rose-800 mt-0.5">{{ $stockStats['out_of_stock_count'] }}</div>
                </div>
            </div>
        </div>

        <!-- 12-Month Sales & Profit Chart -->
        <div class="lg:col-span-2 bg-white p-6 rounded-xl border border-slate-200 shadow-xs flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">{{ $selectedYear }} Monthly Performance Chart</h4>
                <div class="flex items-center gap-4 text-xs font-medium">
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-indigo-600 inline-block"></span> Sales</span>
                    <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Profit</span>
                </div>
            </div>
            <div class="flex-1 min-h-[260px] pt-4">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Monthly Breakdown Table for Selected Year -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">{{ $selectedYear }} Monthly Breakdown Table</h4>
            <span class="text-xs text-slate-500">12 Months Summary</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100/70 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Month</th>
                        <th class="px-4 py-3 text-center">Orders Count</th>
                        <th class="px-4 py-3 text-center">Items Sold</th>
                        <th class="px-4 py-3 text-right">Total Sales</th>
                        <th class="px-4 py-3 text-right">Net Profit</th>
                        <th class="px-4 py-3 text-right">Profit Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($monthlyData as $m)
                        <tr class="hover:bg-slate-50/80 transition {{ $selectedMonth == $m['month_number'] ? 'bg-indigo-50/50' : '' }}">
                            <td class="px-4 py-3 font-bold text-slate-800">
                                {{ $m['month_name'] }} {{ $selectedYear }}
                            </td>
                            <td class="px-4 py-3 text-center">{{ $m['orders_count'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $m['items_sold'] }}</td>
                            <td class="px-4 py-3 text-right font-medium text-slate-800">
                                ₹{{ number_format($m['sales'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold {{ $m['profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $m['profit'] >= 0 ? '+' : '' }}₹{{ number_format($m['profit'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-slate-500">
                                @if ($m['sales'] > 0)
                                    {{ number_format(($m['profit'] / $m['sales']) * 100, 1) }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Product Sales & Profit Breakdown Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Product Sales & Profit Breakdown</h4>
                <p class="text-xs text-slate-500">Sales volume, revenue, and gross profit generated per product in {{ $selectedYear }}{{ $selectedMonth ? ' (Month ' . $selectedMonth . ')' : '' }}.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.export.product_sales.csv', ['year' => $selectedYear, 'month' => $selectedMonth]) }}"
                   class="px-3 py-1.5 text-xs font-bold rounded-lg text-emerald-700 bg-white border border-emerald-300 hover:bg-emerald-50 transition flex items-center gap-1.5"
                   title="Export Product Sales Breakdown to Excel (CSV)">
                    <i class="fa-solid fa-file-excel"></i> Export Sales Excel
                </a>
                <a href="{{ route('reports.export.product_sales.pdf', ['year' => $selectedYear, 'month' => $selectedMonth]) }}"
                   class="px-3 py-1.5 text-xs font-bold rounded-lg text-rose-700 bg-white border border-rose-300 hover:bg-rose-50 transition flex items-center gap-1.5"
                   title="Download Product Sales PDF Report">
                    <i class="fa-solid fa-file-pdf"></i> Download Sales PDF
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100/70 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Product Name & Code</th>
                        <th class="px-4 py-3 text-center">Current Stock</th>
                        <th class="px-4 py-3 text-center">Units Sold</th>
                        <th class="px-4 py-3 text-right">Selling Rate</th>
                        <th class="px-4 py-3 text-right">Total Revenue</th>
                        <th class="px-4 py-3 text-right">Profit Generated</th>
                        <th class="px-4 py-3 text-right">Margin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @php
                        $totUnits = 0;
                        $totRev = 0;
                        $totProf = 0;
                    @endphp
                    @forelse ($productSalesData as $ps)
                        @php
                            $totUnits += $ps['units_sold'];
                            $totRev += $ps['revenue'];
                            $totProf += $ps['profit'];
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition {{ $ps['units_sold'] > 0 ? 'bg-white' : 'bg-slate-50/30' }}">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800">{{ $ps['product']->name }}</div>
                                <div class="font-mono text-xs text-slate-400">{{ $ps['product']->product_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold {{ $ps['product']->stock_quantity <= 0 ? 'text-rose-600' : 'text-slate-700' }}">
                                {{ $ps['product']->stock_quantity }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-indigo-700">
                                {{ $ps['units_sold'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">
                                ₹{{ number_format($ps['product']->sale_rate, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-slate-800">
                                ₹{{ number_format($ps['revenue'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold {{ $ps['profit'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $ps['profit'] >= 0 ? '+' : '' }}₹{{ number_format($ps['profit'], 2) }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-slate-500">
                                @if ($ps['revenue'] > 0)
                                    {{ number_format(($ps['profit'] / $ps['revenue']) * 100, 1) }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 italic">No products available in the catalog.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if (count($productSalesData) > 0)
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-slate-900">
                        <tr>
                            <td class="px-4 py-3" colspan="2">TOTAL SOLD</td>
                            <td class="px-4 py-3 text-center text-indigo-700">{{ $totUnits }} units</td>
                            <td class="px-4 py-3"></td>
                            <td class="px-4 py-3 text-right">₹{{ number_format($totRev, 2) }}</td>
                            <td class="px-4 py-3 text-right text-emerald-700">+₹{{ number_format($totProf, 2) }}</td>
                            <td class="px-4 py-3 text-right text-xs">
                                {{ $totRev > 0 ? number_format(($totProf / $totRev) * 100, 1) . '%' : '—' }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <!-- In-Stock, Low Stock & Out-of-Stock Alert Lists -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Low Stock Alert List -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 bg-amber-50 border-b border-amber-200 flex items-center justify-between">
                <div class="flex items-center gap-2 text-amber-800 font-bold text-sm">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Low Stock Items ({{ $lowStockProducts->count() }})</span>
                </div>
                <a href="{{ route('products.index', ['status' => 'low_stock']) }}" class="text-xs text-amber-700 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                @forelse ($lowStockProducts as $p)
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                        <div>
                            <div class="font-semibold text-slate-800 text-sm">{{ $p->name }}</div>
                            <div class="font-mono text-xs text-slate-400">{{ $p->product_number }}</div>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800">
                                {{ $p->stock_quantity }} left
                            </span>
                            <div class="text-[11px] text-slate-400 mt-0.5">Threshold: {{ $p->low_stock_threshold }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-1 block"></i>
                        No low stock alerts. All inventory is healthy.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Out-of-Stock Alert List -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-5 py-3.5 bg-rose-50 border-b border-rose-200 flex items-center justify-between">
                <div class="flex items-center gap-2 text-rose-800 font-bold text-sm">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <span>Out of Stock Items ({{ $outOfStockProducts->count() }})</span>
                </div>
                <a href="{{ route('products.index', ['status' => 'out_of_stock']) }}" class="text-xs text-rose-700 hover:underline">View all</a>
            </div>
            <div class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                @forelse ($outOfStockProducts as $p)
                    <div class="p-3.5 flex items-center justify-between hover:bg-slate-50 transition">
                        <div>
                            <div class="font-semibold text-slate-800 text-sm">{{ $p->name }}</div>
                            <div class="font-mono text-xs text-slate-400">{{ $p->product_number }}</div>
                        </div>
                        <div class="text-right">
                            <a href="{{ route('stock.index', ['product_id' => $p->id]) }}"
                               class="px-2.5 py-1 rounded text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                                <i class="fa-solid fa-plus mr-1"></i> Check In
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="p-6 text-center text-xs text-slate-400">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-1 block"></i>
                        No out of stock items.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const monthlyData = @json(array_values($monthlyData));
        const labels = monthlyData.map(m => m.month_name);
        const sales = monthlyData.map(m => m.sales);
        const profit = monthlyData.map(m => m.profit);

        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Sales Revenue (₹)',
                        data: sales,
                        backgroundColor: 'rgba(79, 70, 229, 0.8)',
                        borderRadius: 6,
                    },
                    {
                        label: 'Net Profit (₹)',
                        data: profit,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₹' + value; }
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
