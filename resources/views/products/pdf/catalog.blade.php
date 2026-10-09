<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cherry Adorn - Product Inventory Catalog</title>
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
        <a href="{{ route('products.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Products
        </a>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> Print
            </button>
            <button type="button" id="downloadPdfBtn" onclick="generatePdf()" class="px-4 py-1.5 text-xs font-bold text-white bg-pink-600 rounded-xl hover:bg-pink-700 transition shadow-xs flex items-center gap-1.5">
                <i class="fa-solid fa-file-pdf"></i> Download PDF Catalog
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
                    <p class="text-[11px] text-slate-400">Inventory Catalog & Stock Valuation</p>
                </div>
            </div>

            <div class="sm:text-right">
                <div class="text-xs uppercase font-bold tracking-wider text-pink-600">Total Items: {{ $products->count() }}</div>
                <div class="text-[11px] text-slate-400 mt-1">Generated: {{ now()->format('M d, Y - h:i A') }}</div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">Report Currency: <strong>INR (₹)</strong></div>
            </div>
        </div>

        @php
            $totalStock = $products->sum('stock_quantity');
            $totalPurchaseVal = $products->sum(fn($p) => $p->stock_quantity * $p->purchase_rate);
            $totalSaleVal = $products->sum(fn($p) => $p->stock_quantity * $p->sale_rate);
            $totalPotentialProfit = $products->sum(fn($p) => $p->stock_quantity * $p->profit_per_unit);
        @endphp

        <!-- Inventory KPI Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Products</span>
                <div class="text-base font-black text-slate-900 mt-0.5">{{ $products->count() }}</div>
                <span class="text-[10px] text-slate-500">{{ number_format($totalStock) }} units in stock</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Purchase Value</span>
                <div class="text-base font-black text-slate-900 mt-0.5">₹{{ number_format($totalPurchaseVal, 2) }}</div>
                <span class="text-[10px] text-slate-400">Total cost invested</span>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Retail Value</span>
                <div class="text-base font-black text-slate-900 mt-0.5">₹{{ number_format($totalSaleVal, 2) }}</div>
                <span class="text-[10px] text-slate-400">Total expected revenue</span>
            </div>
            <div class="p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Potential Profit</span>
                <div class="text-base font-black text-emerald-700 mt-0.5">+₹{{ number_format($totalPotentialProfit, 2) }}</div>
                <span class="text-[10px] text-emerald-600 font-bold">Unrealized profit</span>
            </div>
        </div>

        <!-- Catalog Table -->
        <div class="space-y-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Product Inventory Ledger</h4>
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-100 uppercase font-bold text-slate-600 border-b border-slate-200 text-[10px]">
                        <tr>
                            <th class="px-3 py-2.5">Code</th>
                            <th class="px-3 py-2.5">Product Name</th>
                            <th class="px-3 py-2.5 text-center">Quality</th>
                            <th class="px-3 py-2.5 text-center">Stock</th>
                            <th class="px-3 py-2.5 text-center">Status</th>
                            <th class="px-3 py-2.5 text-right">Cost (₹)</th>
                            <th class="px-3 py-2.5 text-right">Sale Price (₹)</th>
                            <th class="px-3 py-2.5 text-right">Stock Value (₹)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @forelse ($products as $p)
                            @php
                                $stockVal = $p->stock_quantity * $p->sale_rate;
                            @endphp
                            <tr class="{{ $p->stock_quantity <= 0 ? 'bg-red-50/40' : ($p->stock_quantity <= $p->low_stock_threshold ? 'bg-amber-50/40' : 'bg-white') }}">
                                <td class="px-3 py-2 font-mono text-[11px] font-bold text-slate-700">{{ $p->product_number }}</td>
                                <td class="px-3 py-2 font-semibold text-slate-900">{{ $p->name }}</td>
                                <td class="px-3 py-2 text-center text-slate-500">{{ $p->quality ?? 'Standard' }}</td>
                                <td class="px-3 py-2 text-center font-bold {{ $p->stock_quantity <= 0 ? 'text-rose-600' : ($p->stock_quantity <= $p->low_stock_threshold ? 'text-amber-600' : 'text-slate-800') }}">
                                    {{ $p->stock_quantity }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if ($p->stock_quantity <= 0)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold bg-rose-100 text-rose-700">Out of Stock</span>
                                    @elseif ($p->stock_quantity <= $p->low_stock_threshold)
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold bg-amber-100 text-amber-700">Low Stock</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700">In Stock</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">₹{{ number_format($p->purchase_rate, 2) }}</td>
                                <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format($p->sale_rate, 2) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-slate-900">₹{{ number_format($stockVal, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-slate-400 italic">No products found matching the criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-slate-900">
                        <tr>
                            <td class="px-3 py-2.5" colspan="3">TOTAL ({{ $products->count() }} Items)</td>
                            <td class="px-3 py-2.5 text-center text-rose-950 font-black">{{ number_format($totalStock) }}</td>
                            <td class="px-3 py-2.5"></td>
                            <td class="px-3 py-2.5 text-right">₹{{ number_format($totalPurchaseVal, 2) }}</td>
                            <td class="px-3 py-2.5 text-right">₹{{ number_format($totalSaleVal, 2) }}</td>
                            <td class="px-3 py-2.5 text-right font-black text-rose-950">₹{{ number_format($totalSaleVal, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Footer / Confidentiality -->
        <div class="pt-4 border-t border-slate-200 text-center text-[10px] text-slate-400 space-y-1">
            <p>Cherry Adorn - The Little Jewellery Studio &copy; {{ date('Y') }}. Confidential Catalog & Inventory Ledger.</p>
            <p>For internal management and stock control purposes only.</p>
        </div>
    </div>

    <script>
        function generatePdf() {
            const element = document.getElementById('reportContainer');
            const btn = document.getElementById('downloadPdfBtn');
            const originalContent = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparing PDF...';

            const opt = {
                margin:       [10, 10, 10, 10],
                filename:     'Cherry_Adorn_Inventory_Catalog_{{ now()->format("Y_m_d") }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'landscape' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.disabled = false;
                btn.innerHTML = originalContent;
            }).catch(err => {
                console.error('PDF generation error:', err);
                btn.disabled = false;
                btn.innerHTML = originalContent;
                alert('An error occurred while generating PDF. Please use the Print button instead.');
            });
        }
    </script>
</body>
</html>
