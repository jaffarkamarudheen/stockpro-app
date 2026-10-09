<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cherry Adorn - Financial Report {{ $selectedYear }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            .report-card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 font-sans text-slate-800">
    <!-- Top Action Toolbar -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print">
        <a href="{{ route('reports.index', ['year' => $selectedYear, 'month' => $selectedMonth]) }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Analytics
        </a>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print
            </button>
            <button type="button" id="downloadPdfBtn" onclick="generatePdf()" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-file-pdf"></i> Download PDF Report
            </button>
        </div>
    </div>

    <!-- Printable Report Container -->
    <div id="reportContainer" class="max-w-4xl mx-auto bg-white rounded-3xl border border-slate-200 shadow-sm p-8 sm:p-10 space-y-6 report-card">
        <!-- Report Header -->
        <div class="flex flex-col sm:flex-row items-start justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/cherry-adorn-logo.jpg') }}" alt="Cherry Adorn" class="w-16 h-16 rounded-full object-cover border border-pink-200 shadow-xs">
                <div>
                    <h2 class="text-xl font-black text-rose-950 tracking-tight">Cherry Adorn</h2>
                    <p class="text-xs uppercase font-bold tracking-wider text-pink-600">The Little Jewellery Studio</p>
                    <p class="text-[11px] text-slate-400">Executive Financial & Sales Performance Report</p>
                </div>
            </div>

            <div class="sm:text-right">
                <div class="text-xs uppercase font-bold tracking-wider text-indigo-600">Period: {{ $selectedYear }}{{ $selectedMonth ? ' - Month ' . $selectedMonth : ' (Full Year)' }}</div>
                <div class="text-[11px] text-slate-400 mt-1">Generated: {{ now()->format('M d, Y - h:i A') }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">Report Currency: <strong>INR (₹)</strong></div>
            </div>
        </div>

        <!-- Period Summary KPI Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Sales</span>
                <div class="text-base font-black text-slate-900 mt-0.5">₹{{ number_format($totalSales, 2) }}</div>
                <span class="text-[10px] text-slate-500">{{ $totalOrders }} orders ({{ $totalUnits }} units)</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Goods Cost</span>
                <div class="text-base font-black text-slate-900 mt-0.5">₹{{ number_format($totalCost, 2) }}</div>
                <span class="text-[10px] text-slate-400">Cost of inventory</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Other Overhead</span>
                <div class="text-base font-black text-slate-900 mt-0.5">₹{{ number_format($totalOther, 2) }}</div>
                <span class="text-[10px] text-slate-400">Delivery & Packaging</span>
            </div>
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Net Profit</span>
                <div class="text-base font-black text-emerald-700 mt-0.5">+₹{{ number_format($totalProfit, 2) }}</div>
                <span class="text-[10px] text-emerald-600 font-bold">
                    {{ $totalSales > 0 ? number_format(($totalProfit / $totalSales) * 100, 1) : 0 }}% Margin
                </span>
            </div>
        </div>

        <!-- Monthly Breakdown Table -->
        <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Monthly Sales & Profit Breakdown</h4>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-100 uppercase font-bold text-slate-600 border-b border-slate-200 text-[10px]">
                        <tr>
                            <th class="px-3 py-2.5">Month</th>
                            <th class="px-3 py-2.5 text-center">Orders</th>
                            <th class="px-3 py-2.5 text-center">Units</th>
                            <th class="px-3 py-2.5 text-right">Gross Sales</th>
                            <th class="px-3 py-2.5 text-right">Goods Cost</th>
                            <th class="px-3 py-2.5 text-right">Overhead</th>
                            <th class="px-3 py-2.5 text-right">Net Profit</th>
                            <th class="px-3 py-2.5 text-right">Margin</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($monthlyData as $row)
                            <tr class="{{ $row['sales'] > 0 ? 'bg-white' : 'bg-slate-50/50' }}">
                                <td class="px-3 py-2 font-bold text-slate-800">{{ $row['month_name'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['orders_count'] }}</td>
                                <td class="px-3 py-2 text-center">{{ $row['items_sold'] }}</td>
                                <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format($row['sales'], 2) }}</td>
                                <td class="px-3 py-2 text-right text-slate-600">₹{{ number_format($row['cost'], 2) }}</td>
                                <td class="px-3 py-2 text-right text-slate-500">₹{{ number_format($row['other'], 2) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-emerald-600">₹{{ number_format($row['profit'], 2) }}</td>
                                <td class="px-3 py-2 text-right text-slate-500">{{ $row['margin'] }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-900 text-white font-bold text-[11px]">
                        <tr>
                            <td class="px-3 py-2.5">TOTAL</td>
                            <td class="px-3 py-2.5 text-center">{{ $totalOrders }}</td>
                            <td class="px-3 py-2.5 text-center">{{ $totalUnits }}</td>
                            <td class="px-3 py-2.5 text-right text-white">₹{{ number_format($totalSales, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-slate-300">₹{{ number_format($totalCost, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-slate-300">₹{{ number_format($totalOther, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-emerald-400">+₹{{ number_format($totalProfit, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-slate-300">
                                {{ $totalSales > 0 ? number_format(($totalProfit / $totalSales) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="border-t border-slate-200 pt-4 flex items-center justify-between text-[10px] text-slate-400">
            <span>Cherry Adorn • The Little Jewellery Studio</span>
            <span>Generated from StockPro Inventory System</span>
        </div>
    </div>

    <script>
        function generatePdf() {
            const btn = document.getElementById('downloadPdfBtn');
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;

            const element = document.getElementById('reportContainer');
            const opt = {
                margin: [8, 8, 8, 8],
                filename: 'CherryAdorn_Financial_Report_{{ $selectedYear }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Download PDF Report';
                btn.disabled = false;
            }).catch(err => {
                alert('PDF generation error: ' + err.message);
                btn.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Download PDF Report';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>
