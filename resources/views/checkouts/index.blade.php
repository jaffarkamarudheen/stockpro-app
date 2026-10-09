@extends('layouts.app')

@section('title', 'Sales & Checkouts Ledger')
@section('page_heading', 'Sales & Customer Checkouts')

@section('content')
<div class="space-y-6">
    <!-- Attention & Overdue Reminder Banner -->
    @if ($totalNeedsAttention > 0)
        <div class="p-4 rounded-2xl bg-amber-500/10 border-2 border-amber-400 text-amber-900 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm animate-pulse">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-lg flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <div>
                    <h4 class="font-bold text-sm text-amber-950 flex items-center gap-2">
                        <span>Action Required: {{ $totalNeedsAttention }} Order(s) Need Follow-up / Status Update</span>
                    </h4>
                    <div class="text-xs text-amber-800 mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                        @if ($overdueOrderedCount > 0)
                            <span class="inline-flex items-center gap-1 font-semibold text-indigo-900">
                                <i class="fa-solid fa-clock text-indigo-600"></i>
                                {{ $overdueOrderedCount }} in "Ordered" for 1+ day (Move to Delivery)
                            </span>
                        @endif
                        @if ($overdueDeliveredCount > 0)
                            <span class="inline-flex items-center gap-1 font-semibold text-purple-900">
                                <i class="fa-solid fa-truck text-purple-600"></i>
                                {{ $overdueDeliveredCount }} in "Delivered" for 2+ days (Confirm Received)
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                @if (request()->boolean('needs_attention'))
                    <a href="{{ route('checkouts.index') }}" class="px-3.5 py-1.5 text-xs font-bold rounded-xl bg-white border border-amber-300 text-amber-900 hover:bg-amber-100 transition">
                        Show All Orders
                    </a>
                @else
                    <a href="{{ route('checkouts.index', ['needs_attention' => 1]) }}" class="px-4 py-2 text-xs font-bold rounded-xl bg-amber-600 hover:bg-amber-700 text-white shadow transition flex items-center gap-1.5">
                        <i class="fa-solid fa-filter"></i>
                        <span>Filter Overdue Orders ({{ $totalNeedsAttention }})</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Financial Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Sales Revenue</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">₹{{ number_format($totalSales, 2) }}</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Paid customer checkouts</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Net Profit</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">+₹{{ number_format($totalProfit, 2) }}</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Sale - Costs (Excl. Promo)</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Orders</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalOrders }}</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">All customer checkouts</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Promotions (Free)</p>
                <h3 class="text-2xl font-bold text-pink-600 mt-1">{{ $totalPromotions }}</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Stock-only promotional orders</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-pink-50 text-pink-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-gift"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs space-y-3">
        <form method="GET" action="{{ route('checkouts.index') }}" class="flex flex-wrap items-center gap-2.5">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Customer, phone, order #..."
                       class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <!-- Status Filter -->
            <select name="status" class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Statuses</option>
                <option value="ordered" {{ request('status') === 'ordered' ? 'selected' : '' }}>Ordered</option>
                <option value="waiting_for_delivery" {{ request('status') === 'waiting_for_delivery' ? 'selected' : '' }}>Waiting for Delivery</option>
                <option value="delivered" {{ request('status') === 'delivered' ? 'selected' : '' }}>Delivered</option>
                <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Received</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <!-- Promotion Filter -->
            <select name="is_promotion" class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Types</option>
                <option value="0" {{ request('is_promotion') === '0' ? 'selected' : '' }}>Sales Only</option>
                <option value="1" {{ request('is_promotion') === '1' ? 'selected' : '' }}>Promos Only</option>
            </select>

            <!-- Sort By -->
            <select name="sort" class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Date: Latest First</option>
                <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Date: Oldest First</option>
                <option value="amount_desc" {{ request('sort') === 'amount_desc' ? 'selected' : '' }}>Amount: High to Low</option>
                <option value="amount_asc" {{ request('sort') === 'amount_asc' ? 'selected' : '' }}>Amount: Low to High</option>
                <option value="profit_desc" {{ request('sort') === 'profit_desc' ? 'selected' : '' }}>Profit: High to Low</option>
                <option value="customer_asc" {{ request('sort') === 'customer_asc' ? 'selected' : '' }}>Customer (A-Z)</option>
                <option value="customer_desc" {{ request('sort') === 'customer_desc' ? 'selected' : '' }}>Customer (Z-A)</option>
            </select>

            <!-- Per Page -->
            <select name="per_page" class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="15" {{ request('per_page', '15') == '15' ? 'selected' : '' }}>15 / Page</option>
                <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>25 / Page</option>
                <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 / Page</option>
                <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100 / Page</option>
            </select>

            <select name="enquiry_from" class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Channels</option>
                @foreach ($enquirySources as $source)
                    <option value="{{ $source }}" {{ request('enquiry_from') === $source ? 'selected' : '' }}>{{ $source }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Date From"
                   class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">

            <input type="date" name="date_to" value="{{ request('date_to') }}" title="Date To"
                   class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">

            @if(isset($users) && $users->count() > 0)
                <select name="user_id" class="py-2 px-2.5 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="">All Staff</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ (string)request('user_id') === (string)$user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <button type="submit" class="px-3.5 py-2 text-xs font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition">
                Filter
            </button>

            @if (request()->hasAny(['search', 'status', 'is_promotion', 'enquiry_from', 'date_from', 'date_to', 'user_id', 'needs_attention', 'sort']) && (request('sort') !== 'latest' || request('search') || request('status') || request('is_promotion') || request('enquiry_from') || request('date_from') || request('date_to') || request('user_id') || request('needs_attention')))
                <a href="{{ route('checkouts.index') }}" class="px-2.5 py-2 text-xs font-medium text-slate-500 hover:text-slate-800 transition">
                    Clear
                </a>
            @endif

            <div class="ml-auto flex items-center gap-2">
                <a href="{{ route('checkouts.export.csv', request()->query()) }}"
                   class="px-3 py-2 text-xs font-bold rounded-xl text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition flex items-center gap-1.5"
                   title="Export Sales Ledger to Excel (CSV)">
                    <i class="fa-solid fa-file-excel"></i> Excel (CSV)
                </a>
                <a href="{{ route('checkouts.export.pdf', request()->query()) }}"
                   class="px-3 py-2 text-xs font-bold rounded-xl text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition flex items-center gap-1.5"
                   title="Download Sales Ledger PDF">
                    <i class="fa-solid fa-file-pdf"></i> PDF Ledger
                </a>
                <a href="{{ route('checkouts.create') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-xs transition">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>New Checkout</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Order / Dates</th>
                        <th class="px-4 py-3.5">Status & Reminders</th>
                        <th class="px-4 py-3.5">Customer & Channel</th>
                        <th class="px-4 py-3.5">Items Purchased</th>
                        <th class="px-4 py-3.5 text-right">Amount</th>
                        <th class="px-4 py-3.5 text-right">Net Profit</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($checkouts as $checkout)
                        <tr class="hover:bg-slate-50/80 transition {{ $checkout->needs_attention ? 'bg-amber-50/30' : '' }}">
                            <!-- Order # & Dates -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('checkouts.show', $checkout) }}" class="font-bold text-indigo-600 hover:underline">
                                    {{ $checkout->order_number }}
                                </a>

                                <div class="text-xs text-slate-500 mt-0.5">
                                    <i class="fa-regular fa-calendar text-[10px] text-slate-400"></i>
                                    {{ $checkout->ordered_at ? $checkout->ordered_at->format('M d, Y') : $checkout->created_at->format('M d, Y') }}
                                </div>

                                @if ($checkout->expected_delivery_date)
                                    <div class="text-[11px] text-indigo-600 mt-0.5 font-medium flex items-center gap-1">
                                        <i class="fa-solid fa-truck-fast text-[9px]"></i>
                                        <span>Delivery: {{ $checkout->expected_delivery_date->format('M d, Y') }}</span>
                                    </div>
                                @endif

                                @if ($checkout->user_name || $checkout->user)
                                    <div class="text-[11px] font-medium text-slate-500 flex items-center gap-1 mt-1">
                                        <i class="fa-solid fa-user-check text-[9px] text-indigo-400"></i>
                                        <span>{{ $checkout->user_name ?? $checkout->user->name }}</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Status & Overdue Reminders -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="space-y-1">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border {{ $checkout->getStatusBadgeClasses() }}">
                                        {{ $checkout->status_label }}
                                    </span>

                                    <!-- Overdue Ordered Reminder Badge -->
                                    @if ($checkout->is_overdue_ordered)
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-300 animate-pulse">
                                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                                                <span>Ordered 1+ day ago</span>
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Overdue Delivered Reminder Badge -->
                                    @if ($checkout->is_overdue_delivered)
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-bold bg-purple-100 text-purple-900 border border-purple-300 animate-pulse">
                                                <i class="fa-solid fa-bell text-purple-600"></i>
                                                <span>Delivered 2+ days ago</span>
                                            </span>
                                        </div>
                                    @endif

                                    @if ($checkout->is_promotion)
                                        <div>
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-black bg-pink-100 text-pink-700 border border-pink-200 uppercase">
                                                <i class="fa-solid fa-gift text-pink-500"></i> Promotion
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>

                            <!-- Buyer & Channel -->
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $checkout->customer_name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5 line-clamp-1 flex items-start gap-1">
                                    <i class="fa-solid fa-location-dot text-slate-400 mt-0.5 text-[10px]"></i>
                                    <span>{{ $checkout->customer_address }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    @if ($checkout->customer_phone)
                                        <span class="text-[11px] text-slate-400 flex items-center gap-1">
                                            <i class="fa-solid fa-phone text-[9px]"></i>
                                            {{ $checkout->customer_phone }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-700">
                                        {{ $checkout->enquiry_from }}
                                    </span>
                                </div>
                            </td>

                            <!-- Items Summary -->
                            <td class="px-4 py-3">
                                <div class="text-xs">
                                    <strong class="text-slate-800">{{ $checkout->total_quantity }}</strong> total units
                                    <div class="text-slate-400 truncate max-w-xs mt-0.5">
                                        {{ $checkout->items->pluck('product.name')->filter()->join(', ') }}
                                    </div>
                                </div>
                            </td>

                            <!-- Sale Total -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if ($checkout->is_promotion)
                                    <span class="font-bold text-pink-600 bg-pink-50 px-2 py-0.5 rounded border border-pink-200 text-xs">
                                        FREE (₹0.00)
                                    </span>
                                @else
                                    <span class="font-bold text-slate-800">
                                        ₹{{ number_format($checkout->total_sale_amount, 2) }}
                                    </span>
                                    @if ($checkout->discount_amount > 0)
                                        <div class="text-[10px] text-rose-500">-₹{{ number_format($checkout->discount_amount, 2) }} off</div>
                                    @endif
                                @endif
                            </td>

                            <!-- Net Profit -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if ($checkout->is_promotion)
                                    <span class="text-xs text-slate-400 font-semibold italic">Promo / ₹0</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        +₹{{ number_format($checkout->total_profit, 2) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Action Buttons: Receipt, Edit, Delete -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <!-- Client Receipt Link -->
                                    <a href="{{ route('checkouts.receipt', $checkout) }}" title="View / Download Client PDF Receipt"
                                       class="p-2 rounded-lg text-pink-700 bg-pink-50 hover:bg-pink-100 transition text-xs font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-file-invoice"></i>
                                        <span>Receipt</span>
                                    </a>

                                    <!-- Edit Link -->
                                    <a href="{{ route('checkouts.edit', $checkout) }}" title="Edit Order & Stock"
                                       class="p-2 rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition text-xs font-semibold">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ route('checkouts.destroy', $checkout) }}" class="inline"
                                          onsubmit="return confirm('Delete Order #{{ $checkout->order_number }}? All product quantities ({{ $checkout->total_quantity }} units) will be RESTORED back into inventory.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete Order & Restore Stock"
                                                class="p-2 rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-100 transition text-xs font-semibold">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-cart-shopping text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-base font-medium text-slate-600">No checkout transactions found</p>
                                <p class="text-xs text-slate-400 mt-1">Record a sale or promotional checkout to track inventory and customer delivery.</p>
                                <a href="{{ route('checkouts.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 transition">
                                    <i class="fa-solid fa-plus"></i> New Checkout
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($checkouts->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $checkouts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
