@extends('layouts.app')

@section('title', 'Order Receipt #' . $checkout->order_number)
@section('page_heading', 'Order #' . $checkout->order_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Top Actions -->
    <div class="flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="{{ route('checkouts.index') }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Ledger
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Client PDF / Printable Receipt -->
            <a href="{{ route('checkouts.receipt', $checkout) }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold rounded-xl text-white bg-pink-600 hover:bg-pink-700 shadow transition">
                <i class="fa-solid fa-file-pdf"></i>
                <span>Client PDF Receipt</span>
            </a>

            <!-- Edit Button -->
            <a href="{{ route('checkouts.edit', $checkout) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-xl text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 transition">
                <i class="fa-solid fa-pen-to-square"></i>
                <span>Edit</span>
            </a>

            <!-- Print Button -->
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-xl text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition">
                <i class="fa-solid fa-print"></i>
                <span>Print</span>
            </button>

            <!-- Delete Button -->
            <form method="POST" action="{{ route('checkouts.destroy', $checkout) }}" class="inline"
                  onsubmit="return confirm('Are you sure you want to delete Order #{{ $checkout->order_number }}? All {{ $checkout->total_quantity }} product units will be RESTORED back into inventory.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold rounded-xl text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>Delete</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Overdue Reminder Banners -->
    @if ($checkout->is_overdue_ordered)
        <div class="p-4 rounded-2xl bg-amber-500/10 border-2 border-amber-400 text-amber-900 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xl"></i>
                <div>
                    <h5 class="font-bold text-sm">Action Needed: Order placed over 1 day ago</h5>
                    <p class="text-xs text-amber-800">This order is still in "Ordered" status. Has it been packed and sent for delivery?</p>
                </div>
            </div>
            <form method="POST" action="{{ route('checkouts.status', $checkout) }}" class="flex-shrink-0">
                @csrf
                <input type="hidden" name="status" value="waiting_for_delivery">
                <button type="submit" class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-amber-600 text-white hover:bg-amber-700 transition">
                    Mark as Waiting for Delivery
                </button>
            </form>
        </div>
    @endif

    @if ($checkout->is_overdue_delivered)
        <div class="p-4 rounded-2xl bg-purple-500/10 border-2 border-purple-400 text-purple-900 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <i class="fa-solid fa-bell text-purple-600 text-xl"></i>
                <div>
                    <h5 class="font-bold text-sm">Follow-up Needed: Delivered over 2 days ago</h5>
                    <p class="text-xs text-purple-800">Customer received confirmation needed. Has the client confirmed receipt?</p>
                </div>
            </div>
            <form method="POST" action="{{ route('checkouts.status', $checkout) }}" class="flex-shrink-0">
                @csrf
                <input type="hidden" name="status" value="received">
                <button type="submit" class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-purple-600 text-white hover:bg-purple-700 transition">
                    Confirm Received
                </button>
            </form>
        </div>
    @endif

    <!-- Quick Status Transition Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 no-print">
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Status:</span>
            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $checkout->getStatusBadgeClasses() }}">
                {{ $checkout->status_label }}
            </span>
        </div>

        <!-- Quick Status Buttons -->
        <div class="flex flex-wrap items-center gap-1.5">
            @if ($checkout->status !== 'ordered')
                <form method="POST" action="{{ route('checkouts.status', $checkout) }}">
                    @csrf
                    <input type="hidden" name="status" value="ordered">
                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-indigo-700 hover:bg-indigo-50 border border-indigo-200 transition">
                        Ordered
                    </button>
                </form>
            @endif

            @if ($checkout->status !== 'waiting_for_delivery')
                <form method="POST" action="{{ route('checkouts.status', $checkout) }}">
                    @csrf
                    <input type="hidden" name="status" value="waiting_for_delivery">
                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-amber-700 hover:bg-amber-50 border border-amber-200 transition">
                        Waiting for Delivery
                    </button>
                </form>
            @endif

            @if ($checkout->status !== 'delivered')
                <form method="POST" action="{{ route('checkouts.status', $checkout) }}">
                    @csrf
                    <input type="hidden" name="status" value="delivered">
                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-purple-700 hover:bg-purple-50 border border-purple-200 transition">
                        Mark Delivered
                    </button>
                </form>
            @endif

            @if ($checkout->status !== 'received')
                <form method="POST" action="{{ route('checkouts.status', $checkout) }}">
                    @csrf
                    <input type="hidden" name="status" value="received">
                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs font-semibold text-emerald-700 hover:bg-emerald-50 border border-emerald-200 transition">
                        Mark Received
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Invoice Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-8 space-y-8" id="invoice">
        <!-- Invoice Header -->
        <div class="flex flex-col sm:flex-row items-start justify-between gap-4 border-b border-slate-200 pb-6">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/cherry-adorn-logo.jpg') }}" alt="Cherry Adorn" class="w-16 h-16 rounded-full object-cover border border-pink-200 shadow-xs">
                <div>
                    <h2 class="text-xl font-black text-rose-950 tracking-tight">Cherry Adorn</h2>
                    <p class="text-xs uppercase font-bold tracking-wider text-pink-600">The Little Jewellery Studio</p>
                    <p class="text-[11px] text-slate-400">Inventory & Customer Order Management</p>
                </div>
            </div>

            <div class="sm:text-right">
                <div class="text-xs uppercase font-bold tracking-wider text-slate-400">Invoice / Order</div>
                <div class="font-mono text-base font-bold text-indigo-600">{{ $checkout->order_number }}</div>
                <div class="text-xs text-slate-500 mt-1">
                    Ordered: {{ $checkout->ordered_at ? $checkout->ordered_at->format('M d, Y - h:i A') : $checkout->created_at->format('M d, Y') }}
                </div>
                @if ($checkout->user_name || $checkout->user)
                    <div class="text-xs text-slate-600 mt-1 font-medium">
                        Processed by: <span class="text-slate-900 font-semibold">{{ $checkout->user_name ?? $checkout->user->name }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Customer & Order Information -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 p-5 rounded-2xl border border-slate-200">
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
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">Channel & Timeline</div>
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 mb-2">
                    <i class="fa-regular fa-comment-dots"></i>
                    <span>Channel: {{ $checkout->enquiry_from }}</span>
                </div>

                <div class="space-y-1 text-xs text-slate-600 mt-1">
                    @if ($checkout->expected_delivery_date)
                        <div><span class="text-slate-400">Expected Delivery:</span> <strong class="text-slate-800">{{ $checkout->expected_delivery_date->format('M d, Y') }}</strong></div>
                    @endif
                    @if ($checkout->delivered_at)
                        <div><span class="text-slate-400">Delivered At:</span> <strong class="text-purple-700">{{ $checkout->delivered_at->format('M d, Y h:i A') }}</strong></div>
                    @endif
                    @if ($checkout->received_at)
                        <div><span class="text-slate-400">Received At:</span> <strong class="text-emerald-700">{{ $checkout->received_at->format('M d, Y h:i A') }}</strong></div>
                    @endif
                </div>

                @if ($checkout->is_promotion)
                    <div class="mt-2 inline-block px-2.5 py-1 rounded-lg text-xs font-black bg-pink-100 text-pink-800 border border-pink-200">
                        🎁 Free Promotional Checkout
                    </div>
                @endif

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
                                @if ($checkout->is_promotion)
                                    <span class="text-xs text-slate-400">Promo</span>
                                @else
                                    +₹{{ number_format($item->subtotal_profit, 2) }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals & Summary -->
        <div class="border-t border-slate-200 pt-6 flex flex-col sm:flex-row justify-between items-start gap-4">
            <div class="space-y-1 text-xs text-slate-500">
                @if (! $checkout->is_promotion)
                    <div><span class="font-medium">Total Cost of Goods:</span> ₹{{ number_format($checkout->total_purchase_cost, 2) }}</div>
                    <div><span class="font-medium">Total Other Expenses:</span> ₹{{ number_format($checkout->total_other_cost, 2) }}</div>
                @else
                    <div class="italic text-pink-700">Promotional order - inventory deducted without financial report deduction.</div>
                @endif
                <div class="text-[11px] text-slate-400">Inventory automatically deducted upon checkout.</div>
            </div>

            <div class="w-full sm:w-64 space-y-2">
                <div class="flex justify-between text-sm text-slate-600">
                    <span>Total Quantity:</span>
                    <span class="font-bold text-slate-800">{{ $checkout->total_quantity }} units</span>
                </div>

                <div class="flex justify-between text-sm text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-semibold text-slate-800">₹{{ number_format($checkout->subtotal_amount > 0 ? $checkout->subtotal_amount : $checkout->total_sale_amount, 2) }}</span>
                </div>

                @if ($checkout->discount_amount > 0)
                    <div class="flex justify-between text-sm text-rose-600 font-semibold">
                        <span>Discount:</span>
                        <span>-₹{{ number_format($checkout->discount_amount, 2) }}</span>
                    </div>
                @endif

                <div class="flex justify-between text-base font-bold text-slate-900 pt-1 border-t border-slate-200">
                    <span>Total Amount Paid:</span>
                    @if ($checkout->is_promotion)
                        <span class="text-pink-600 font-black">FREE (₹0.00)</span>
                    @else
                        <span>₹{{ number_format($checkout->total_sale_amount, 2) }}</span>
                    @endif
                </div>

                @if (! $checkout->is_promotion)
                    <div class="flex justify-between text-sm font-bold text-emerald-600 pt-2 bg-emerald-50 px-3 py-2 rounded-xl border border-emerald-200">
                        <span>Net Profit Earned:</span>
                        <span>+₹{{ number_format($checkout->total_profit, 2) }}</span>
                    </div>
                @endif
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
