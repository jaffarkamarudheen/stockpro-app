@extends('layouts.app')

@section('title', 'Stock Movements & Check-In')
@section('page_heading', 'Stock Management & Check-In')

@section('content')
<div class="space-y-6" x-data="{ checkInModalOpen: false, selectedProduct: '{{ request('product_id', '') }}' }">
    <!-- Top Action Bar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-800">Inventory Movement & Audit History</h3>
            <p class="text-xs text-slate-500">Track check-ins, sales check-outs, and batch additions in real-time.</p>
        </div>

        <button type="button" @click="checkInModalOpen = true"
                class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-lg text-white bg-emerald-600 hover:bg-emerald-700 shadow transition">
            <i class="fa-solid fa-plus-circle"></i>
            <span>Check In Stock</span>
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center gap-3">
        <form method="GET" action="{{ route('stock.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="relative flex-1 min-w-[200px]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search product name or code..."
                       class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>

            <select name="type" class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                <option value="">All Movement Types</option>
                <option value="CHECK_IN" {{ request('type') === 'CHECK_IN' ? 'selected' : '' }}>Check-In (Inward +)</option>
                <option value="CHECK_OUT" {{ request('type') === 'CHECK_OUT' ? 'selected' : '' }}>Check-Out (Sales -)</option>
            </select>

            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                Filter
            </button>

            @if (request()->hasAny(['search', 'type', 'product_id']))
                <a href="{{ route('stock.index') }}" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Movement Records Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Date & Time</th>
                        <th class="px-4 py-3.5">Product</th>
                        <th class="px-4 py-3.5 text-center">Movement Type</th>
                        <th class="px-4 py-3.5 text-right">Quantity</th>
                        <th class="px-4 py-3.5">Reference</th>
                        <th class="px-4 py-3.5">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($movements as $movement)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-500">
                                {{ $movement->created_at->format('M d, Y - h:i A') }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($movement->product)
                                    <div class="font-semibold text-slate-800">{{ $movement->product->name }}</div>
                                    <div class="font-mono text-xs text-indigo-600 font-semibold">{{ $movement->product->product_number }}</div>
                                @else
                                    <span class="text-slate-400 italic">Product deleted</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if ($movement->type === 'CHECK_IN')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-arrow-down text-[10px]"></i> Check In
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                        <i class="fa-solid fa-arrow-up text-[10px]"></i> Check Out
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap font-bold">
                                @if ($movement->quantity > 0)
                                    <span class="text-emerald-600">+{{ $movement->quantity }}</span>
                                @else
                                    <span class="text-rose-600">{{ $movement->quantity }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-slate-700">
                                {{ $movement->reference ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500 max-w-xs truncate">
                                {{ $movement->notes ?: '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-boxes-packing text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-base font-medium text-slate-600">No stock movement recorded yet</p>
                                <p class="text-xs text-slate-400 mt-1">Click "Check In Stock" or make a sale to start tracking stock movements.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movements->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Check-In Modal -->
    <div x-show="checkInModalOpen" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="checkInModalOpen = false"></div>

        <div class="fixed inset-0 z-10 overflow-y-auto flex items-center justify-center p-4">
            <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all"
                 @click.away="checkInModalOpen = false">
                <div class="px-6 py-4 bg-emerald-600 text-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-arrow-down-to-bracket text-lg"></i>
                        <h4 class="font-bold text-base">Check In Product Stock</h4>
                    </div>
                    <button type="button" @click="checkInModalOpen = false" class="text-emerald-100 hover:text-white p-1">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('stock.check-in') }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label for="product_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Select Product <span class="text-rose-500">*</span>
                        </label>
                        <select id="product_id" name="product_id" required x-model="selectedProduct"
                                class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none bg-white">
                            <option value="">-- Choose a Product --</option>
                            @foreach ($products as $p)
                                <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} ({{ $p->product_number }}) - Current Stock: {{ $p->stock_quantity }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="quantity" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Quantity to Add <span class="text-rose-500">*</span>
                        </label>
                        <input type="number" min="1" id="quantity" name="quantity" value="10" required
                               class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>

                    <div>
                        <label for="reference" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Reference / Supplier / Invoice
                        </label>
                        <input type="text" id="reference" name="reference" placeholder="e.g. Batch #2026-A, Supplier PO 440"
                               class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Notes
                        </label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Optional notes about this check-in..."
                                  class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-3">
                        <button type="button" @click="checkInModalOpen = false"
                                class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-5 py-2.5 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Confirm Stock Check-In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
