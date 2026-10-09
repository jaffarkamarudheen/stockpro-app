<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $checkout->order_number }} - Cherry Adorn</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            #receipt-content { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 font-sans text-slate-800 antialiased">

    <!-- Top Action Bar -->
    <div class="max-w-3xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="{{ url()->previous() ?: route('checkouts.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Back</span>
        </a>

        <div class="flex items-center gap-3">
            <button onclick="downloadPDF()" id="pdfBtn" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-bold text-white bg-pink-600 hover:bg-pink-700 rounded-xl shadow transition">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Download PDF</span>
            </button>
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-xl shadow-xs transition">
                <i class="fa-solid fa-print"></i>
                <span>Print Receipt</span>
            </button>
        </div>
    </div>

    <!-- Printable & PDF-able Receipt Card -->
    <div id="receipt-content" class="max-w-3xl mx-auto bg-white rounded-3xl border border-pink-100 shadow-xl p-8 sm:p-12 space-y-8">
        <!-- Header with Cherry Adorn Logo -->
        <div class="flex flex-col sm:flex-row items-center justify-between pb-6 border-b border-pink-100 gap-6">
            <div class="flex items-center gap-4 text-center sm:text-left">
                <img src="{{ asset('images/cherry-adorn-logo.jpg') }}" alt="Cherry Adorn" class="w-24 h-24 rounded-full object-cover border-2 border-pink-200 shadow-sm mx-auto sm:mx-0">
                <div>
                    <h1 class="text-2xl font-black text-rose-950 tracking-tight">Cherry Adorn</h1>
                    <p class="text-xs uppercase font-bold tracking-widest text-pink-600">The Little Jewellery Studio</p>
                    <p class="text-xs text-slate-400 mt-1">Handcrafted & Curated Jewellery</p>
                </div>
            </div>

            <div class="text-center sm:text-right">
                <div class="text-[11px] uppercase tracking-wider font-bold text-slate-400">Tax Invoice / Receipt</div>
                <div class="text-lg font-mono font-bold text-slate-900">{{ $checkout->order_number }}</div>
                <div class="text-xs text-slate-500 mt-1">Date: {{ $checkout->ordered_at ? $checkout->ordered_at->format('d M Y, h:i A') : $checkout->created_at->format('d M Y') }}</div>
                <div class="mt-2">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $checkout->getStatusBadgeClasses() }}">
                        {{ $checkout->status_label }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Customer & Order Meta Information -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-rose-50/50 p-6 rounded-2xl border border-pink-100/70">
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-pink-700 mb-2">Customer Details</h3>
                <div class="text-base font-bold text-slate-900">{{ $checkout->customer_name }}</div>
                <div class="text-xs text-slate-600 mt-1 leading-relaxed whitespace-pre-line">{{ $checkout->customer_address }}</div>
                @if($checkout->customer_phone)
                    <div class="text-xs text-slate-600 mt-2 font-medium flex items-center gap-1.5">
                        <i class="fa-solid fa-phone text-pink-500 text-[10px]"></i>
                        <span>{{ $checkout->customer_phone }}</span>
                    </div>
                @endif
            </div>

            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-pink-700 mb-2">Delivery & Order Info</h3>
                <div class="space-y-1.5 text-xs text-slate-600">
                    <div class="flex justify-between">
                        <span class="text-slate-400">Order Channel:</span>
                        <span class="font-semibold text-slate-700">{{ $checkout->enquiry_from }}</span>
                    </div>
                    @if($checkout->expected_delivery_date)
                        <div class="flex justify-between">
                            <span class="text-slate-400">Expected Delivery:</span>
                            <span class="font-semibold text-indigo-700">{{ $checkout->expected_delivery_date->format('d M Y') }}</span>
                        </div>
                    @endif
                    @if($checkout->delivered_at)
                        <div class="flex justify-between">
                            <span class="text-slate-400">Delivered On:</span>
                            <span class="font-semibold text-purple-700">{{ $checkout->delivered_at->format('d M Y') }}</span>
                        </div>
                    @endif
                    @if($checkout->received_at)
                        <div class="flex justify-between">
                            <span class="text-slate-400">Received On:</span>
                            <span class="font-semibold text-emerald-700">{{ $checkout->received_at->format('d M Y') }}</span>
                        </div>
                    @endif
                </div>

                @if($checkout->is_promotion)
                    <div class="mt-3 p-2 rounded-lg bg-pink-100/80 border border-pink-200 text-center">
                        <span class="text-xs font-bold text-pink-800">🎁 Complimentary / Promotional Order</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b-2 border-slate-200 text-xs uppercase font-bold text-slate-500">
                        <th class="py-3 px-2">#</th>
                        <th class="py-3 px-2">Item Description</th>
                        <th class="py-3 px-2 text-center">Qty</th>
                        <th class="py-3 px-2 text-right">Unit Price</th>
                        <th class="py-3 px-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($checkout->items as $idx => $item)
                        <tr>
                            <td class="py-3 px-2 text-xs text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-3 px-2">
                                <div class="font-bold text-slate-800">{{ $item->product?->name ?: 'Item' }}</div>
                                <div class="font-mono text-xs text-slate-400">{{ $item->product?->product_number }}</div>
                            </td>
                            <td class="py-3 px-2 text-center font-bold text-slate-700">{{ $item->quantity }}</td>
                            <td class="py-3 px-2 text-right text-slate-600 font-medium">₹{{ number_format($item->unit_sale_rate, 2) }}</td>
                            <td class="py-3 px-2 text-right font-bold text-slate-900">₹{{ number_format($item->subtotal_sale, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Summary -->
        <div class="border-t-2 border-pink-100 pt-6 flex flex-col sm:flex-row justify-between items-start gap-6">
            <div class="text-xs text-slate-500 space-y-1.5 max-w-sm">
                @if($checkout->notes)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <span class="font-bold text-slate-700 block mb-0.5">Notes:</span>
                        <span>{{ $checkout->notes }}</span>
                    </div>
                @endif
                <p class="text-slate-400 italic">For any questions or support regarding this order, please contact our studio.</p>
            </div>

            <div class="w-full sm:w-72 space-y-2">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-semibold text-slate-800">₹{{ number_format($checkout->subtotal_amount > 0 ? $checkout->subtotal_amount : $checkout->total_sale_amount, 2) }}</span>
                </div>

                @if($checkout->discount_amount > 0)
                    <div class="flex justify-between text-sm text-rose-600 font-medium">
                        <span>Discount:</span>
                        <span>-₹{{ number_format($checkout->discount_amount, 2) }}</span>
                    </div>
                @endif

                @if($checkout->is_promotion)
                    <div class="flex justify-between text-sm text-pink-600 font-bold bg-pink-50 px-2 py-1 rounded">
                        <span>Promotional Discount:</span>
                        <span>100% OFF</span>
                    </div>
                @endif

                <div class="flex justify-between text-lg font-black text-rose-950 pt-2 border-t-2 border-slate-200">
                    <span>Total Amount:</span>
                    <span class="text-pink-600 font-black">₹{{ number_format($checkout->total_sale_amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="border-t border-pink-100 pt-6 text-center text-xs text-slate-400 space-y-1">
            <p class="font-semibold text-rose-900">Thank you for shopping with Cherry Adorn! ✨</p>
            <p>Cherry Adorn – The Little Jewellery Studio</p>
        </div>
    </div>

    <script>
        function downloadPDF() {
            const btn = document.getElementById('pdfBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Generating PDF...';
            btn.disabled = true;

            const element = document.getElementById('receipt-content');
            const opt = {
                margin:       [8, 8, 8, 8],
                filename:     'CherryAdorn_Receipt_{{ $checkout->order_number }}.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(err => {
                console.error(err);
                btn.innerHTML = originalText;
                btn.disabled = false;
                window.print();
            });
        }
    </script>
</body>
</html>
