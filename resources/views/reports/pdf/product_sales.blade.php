<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cherry Adorn - Product Sales Report {{ $selectedYear }}</title>
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
    <div class="max-w-5xl mx-auto mb-6 flex items-center justify-between no-print">
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
    <div id="reportContainer" class="max-w-5xl mx-auto bg-white rounded-3xl border border-slate-200 shadow-sm p-8 sm:p-10 space-y-6 report-card">
        <!-- Report Header -->
        <div class="flex flex-col sm:flex-row items-start justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3.5">
                <img src="{{ asset('images/cherry-adorn-logo.jpg') }}" alt="Cherry Adorn" class="w-16 h-16 rounded-full object-cover border border-pink-200 shadow-xs">
                <div>
                    <h2 class="text-xl font-black text-rose-950 tracking-tight">Cherry Adorn</h2>
                    <p class="text-xs uppercase font-bold tracking-wider text-pink-600">The Little Jewellery Studio</p>
                    <p class="text-[11px] text-slate-400">Product Sales & Profit Contribution Report</p>
                </div>
            </div>

            <div class="sm:text-right">
                <div class="text-xs uppercase font-bold tracking-wider text-indigo-600">Period: {{ $selectedYear }}{{ $selectedMonth ? ' - Month ' . $selectedMonth : ' (Full Year)' }}</div>
                <div class="text-[11px] text-slate-400 mt-1">Generated: {{ now()->format('M d, Y - h:i A') }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">Report Currency: <strong>INR (₹)</strong></div>
            </div>
        </div>

        <!-- Product Sales Performance Table -->
        <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Product Sales, Volume & Net Profit</h4>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-100 uppercase font-bold text-slate-600 border-b border-slate-200 text-[10px]">
                        <tr>
                            <th class="px-3 py-2.5">Code</th>
                            <th class="px-3 py-2.5">Product Name</th>
                            <th class="px-3 py-2.5 text-center">In Stock</th>
                            <th class="px-3 py-2.5 text-right">Sale Price</th>
                            <th class="px-3 py-2.5 text-right">Cost Price</th>
                            <th class="px-3 py-2.5 text-right">Overhead</th>
                            <th class="px-3 py-2.5 text-center">Units Sold</th>
                            <th class="px-3 py-2.5 text-right">Gross Revenue</th>
                            <th class="px-3 py-2.5 text-right">Net Profit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @php
                            $totalUnitsSold = 0;
                            $totalGrossRevenue = 0;
                            $totalNetProfit = 0;
                        @endphp
                        @forelse ($productSales as $item)
                            @php
                                $totalUnitsSold += $item->units_sold;
                                $totalGrossRevenue += $item->total_revenue;
                                $totalNetProfit += $item->total_profit_generated;
                            @endphp
                            <tr class="{{ $item->units_sold > 0 ? 'bg-white' : 'bg-slate-50/50' }}">
                                <td class="px-3 py-2 font-mono text-[11px] text-indigo-600 font-semibold">{{ $item->product_number }}</td>
                                <td class="px-3 py-2 font-bold text-slate-800">{{ $item->name }}</td>
                                <td class="px-3 py-2 text-center {{ $item->stock_quantity <= 0 ? 'text-rose-600 font-bold' : 'text-slate-600' }}">
                                    {{ $item->stock_quantity }}
                                </td>
                                <td class="px-3 py-2 text-right">₹{{ number_format($item->sale_rate, 2) }}</td>
                                <td class="px-3 py-2 text-right text-slate-500">₹{{ number_format($item->purchase_rate, 2) }}</td>
                                <td class="px-3 py-2 text-right text-slate-500">₹{{ number_format($item->other_rate, 2) }}</td>
                                <td class="px-3 py-2 text-center font-bold text-slate-900">{{ $item->units_sold }}</td>
                                <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format($item->total_revenue, 2) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-emerald-600">+₹{{ number_format($item->total_profit_generated, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-6 text-center text-slate-400">No products found for this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-900 text-white font-bold text-[11px]">
                        <tr>
                            <td colspan="6" class="px-3 py-2.5">TOTAL ALL PRODUCTS</td>
                            <td class="px-3 py-2.5 text-center text-white">{{ $totalUnitsSold }}</td>
                            <td class="px-3 py-2.5 text-right text-white">₹{{ number_format($totalGrossRevenue, 2) }}</td>
                            <td class="px-3 py-2.5 text-right text-emerald-400">+₹{{ number_format($totalNetProfit, 2) }}</td>
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
                filename: 'CherryAdorn_Product_Sales_{{ $selectedYear }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'landscape' }
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
