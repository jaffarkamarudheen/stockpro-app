@extends('layouts.app')

@section('title', 'Order Receipt #' . $checkout->order_number)
@section('page_heading', 'Order #' . $checkout->order_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Top Actions -->
    <div class="flex items-center justify-between no-print">
        <a href="{{ route('checkouts.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to All Checkouts
        </a>

        <div class="flex items-center gap-3">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 shadow-xs transition">
                <i class="fa-solid fa-print"></i> Print Invoice
            </button>
            <a href="{{ route('checkouts.create') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition">
                <i class="fa-solid fa-plus"></i> New Sale
            </a>
        </div>
    </div>

    <!-- Invoice Card -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8 space-y-8" id="invoice">
        <!-- Invoice Header -->
        <div class="flex flex-col sm:flex-row items-start justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <span class="text-xl font-black text-slate-900 tracking-tight">StockPro</span>
                </div>
                <p class="text-xs text-slate-400">Inventory & Order Management</p>
            </div>

            <div class="sm:text-right">
                <div class="text-xs uppercase font-bold tracking-wider text-slate-400">Checkout Invoice</div>
                <div class="font-mono text-base font-bold text-indigo-600">{{ $checkout->order_number }}</div>
                <div class="text-xs text-slate-500 mt-1">Date: {{ $checkout->created_at->format('M d, Y - h:i A') }}</div>
            </div>
        </div>

        <!-- Customer & Enquiry Information -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 p-5 rounded-xl border border-slate-200">
            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Customer / Buyer Details</div>
                <div class="font-bold text-slate-900 text-base">{{ $checkout->customer_name }}</div>
                <div class="text-xs text-slate-600 mt-1 whitespace-pre-line leading-relaxed flex items-start gap-1.5">
                    <i class="fa-solid fa-location-dot text-slate-400 mt-0.5"></i>
                    <span>{{ $checkout->customer_address }}</span>
                </div>
                @if ($checkout->customer_phone)
                    <div class="text-xs text-slate-600 mt-1 flex items-center gap-1.5">
                        <i class="fa-solid fa-phone text-slate-400"></i>
                        <span>{{ $checkout->customer_phone }}</span>
                    </div>
                @endif
            </div>

            <div>
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Enquiry Channel & Notes</div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                    <i class="fa-regular fa-comment-dots"></i>
                    <span>Channel: {{ $checkout->enquiry_from }}</span>
                </div>
                @if ($checkout->notes)
                    <div class="text-xs text-slate-600 mt-2 bg-white p-2.5 rounded-lg border border-slate-200">
                        <span class="font-medium text-slate-500">Note:</span> {{ $checkout->notes }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Line Items Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-100 text-xs uppercase font-bold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Item Description</th>
                        <th class="px-4 py-3 text-center">Qty</th>
                        <th class="px-4 py-3 text-right">Unit Sale</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        <th class="px-4 py-3 text-right">Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach ($checkout->items as $item)
                        <tr>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-slate-800">{{ $item->product?->name ?: 'Item' }}</div>
                                <div class="font-mono text-xs text-indigo-600">{{ $item->product?->product_number }}</div>
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold text-slate-800">
                                {{ $item->quantity }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium">
                                ₹{{ number_format($item->unit_sale_rate, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-slate-800">
                                ₹{{ number_format($item->subtotal_sale, 2) }}
                            </td>
                            <td class="px-4 py-3.5 text-right font-bold text-emerald-600">
                                +₹{{ number_format($item->subtotal_profit, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Profit Breakdown -->
        <div class="border-t border-slate-200 pt-6 flex flex-col sm:flex-row justify-between items-start gap-4">
            <div class="space-y-1 text-xs text-slate-500">
                <div><span class="font-medium">Total Cost of Goods:</span> ₹{{ number_format($checkout->total_purchase_cost, 2) }}</div>
                <div><span class="font-medium">Total Other Expenses:</span> ₹{{ number_format($checkout->total_other_cost, 2) }}</div>
                <div class="text-[11px] text-slate-400">Inventory automatically deducted upon checkout.</div>
            </div>

            <div class="w-full sm:w-64 space-y-2">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Total Quantity:</span>
                    <span class="font-bold text-slate-800">{{ $checkout->total_quantity }} units</span>
                </div>
                <div class="flex justify-between text-base font-bold text-slate-900 pt-1 border-t border-slate-200">
                    <span>Total Amount Paid:</span>
                    <span>₹{{ number_format($checkout->total_sale_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-emerald-600 pt-2 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200">
                    <span>Net Profit Earned:</span>
                    <span>+₹{{ number_format($checkout->total_profit, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {
        body { background: white !important; }
        .no-print, aside, header { display: none !important; }
        #invoice { border: none !important; box-shadow: none !important; padding: 0 !important; }
    }
</style>
@endsection
