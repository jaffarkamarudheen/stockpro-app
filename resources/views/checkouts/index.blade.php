@extends('layouts.app')

@section('title', 'Sales & Checkouts Ledger')
@section('page_heading', 'Sales & Customer Checkouts')

@section('content')
<div class="space-y-6">
    <!-- Financial Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Sales Revenue</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">₹{{ number_format($totalSales, 2) }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">Across all completed checkouts</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Net Profit</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">+₹{{ number_format($totalProfit, 2) }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">Sale - Purchase Cost - Other Rates</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
        </div>

        <div class="p-5 rounded-xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Orders Processed</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalOrders }}</h3>
                <p class="text-xs text-slate-400 mt-0.5">Customer sales recorded</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('checkouts.index') }}" class="w-full md:w-auto flex-1 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by customer name, address, order #..."
                       class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <select name="enquiry_from" class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Enquiry Sources</option>
                @foreach ($enquirySources as $source)
                    <option value="{{ $source }}" {{ request('enquiry_from') === $source ? 'selected' : '' }}>{{ $source }}</option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" title="Date From"
                   class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">

            <input type="date" name="date_to" value="{{ request('date_to') }}" title="Date To"
                   class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">

            @if(isset($users) && $users->count() > 0)
                <select name="user_id" class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    <option value="">All Cashiers / Users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" {{ (string)request('user_id') === (string)$user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                Filter
            </button>

            @if (request()->hasAny(['search', 'enquiry_from', 'date_from', 'date_to', 'user_id']))
                <a href="{{ route('checkouts.index') }}" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                    Clear
                </a>
            @endif
        </form>

        <a href="{{ route('checkouts.create') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow transition">
            <i class="fa-solid fa-cart-plus"></i>
            <span>Create New Checkout</span>
        </a>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Order / Date</th>
                        <th class="px-4 py-3.5">Customer & Address</th>
                        <th class="px-4 py-3.5">Enquiry Source</th>
                        <th class="px-4 py-3.5">Items Purchased</th>
                        <th class="px-4 py-3.5 text-right">Sale Total</th>
                        <th class="px-4 py-3.5 text-right">Net Profit</th>
                        <th class="px-4 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($checkouts as $checkout)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Order # & Date -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('checkouts.show', $checkout) }}" class="font-bold text-indigo-600 hover:underline">
                                    {{ $checkout->order_number }}
                                </a>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ $checkout->created_at->format('M d, Y - h:i A') }}
                                </div>
                                @if ($checkout->user_name || $checkout->user)
                                    <div class="text-[11px] font-medium text-slate-600 flex items-center gap-1 mt-1">
                                        <i class="fa-solid fa-user-check text-[9px] text-indigo-500"></i>
                                        <span>{{ $checkout->user_name ?? $checkout->user->name }}</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Buyer Details -->
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ $checkout->customer_name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5 line-clamp-1 flex items-start gap-1">
                                    <i class="fa-solid fa-location-dot text-slate-400 mt-0.5 text-[10px]"></i>
                                    <span>{{ $checkout->customer_address }}</span>
                                </div>
                                @if ($checkout->customer_phone)
                                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-1">
                                        <i class="fa-solid fa-phone text-[9px]"></i>
                                        <span>{{ $checkout->customer_phone }}</span>
                                    </div>
                                @endif
                            </td>

                            <!-- Enquiry Source -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                    <i class="fa-regular fa-comment-dots mr-1"></i>
                                    {{ $checkout->enquiry_from }}
                                </span>
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
                            <td class="px-4 py-3 text-right whitespace-nowrap font-bold text-slate-800">
                                ₹{{ number_format($checkout->total_sale_amount, 2) }}
                            </td>

                            <!-- Net Profit -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    +₹{{ number_format($checkout->total_profit, 2) }}
                                </span>
                            </td>

                            <!-- View Receipt Button -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('checkouts.show', $checkout) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition">
                                    <i class="fa-solid fa-file-invoice"></i>
                                    <span>Receipt</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-cart-shopping text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-base font-medium text-slate-600">No checkout transactions yet</p>
                                <p class="text-xs text-slate-400 mt-1">Make your first sale to track customer addresses and automatic profit.</p>
                                <a href="{{ route('checkouts.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
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
