@extends('layouts.app')

@section('title', 'Products & Inventory')
@section('page_heading', 'Products & Stock Inventory')

@section('content')
<div class="space-y-6">
    <!-- Top Statistics Badges -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="{{ route('products.index') }}" class="p-4 rounded-xl bg-white border border-slate-200 shadow-xs hover:border-indigo-300 transition">
            <div class="flex items-center justify-between text-slate-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider">Total Products</span>
                <i class="fa-solid fa-boxes-stacked text-indigo-500"></i>
            </div>
            <div class="text-2xl font-bold text-slate-800">{{ $stats['total'] }}</div>
        </a>

        <a href="{{ route('products.index', ['status' => 'in_stock']) }}" class="p-4 rounded-xl bg-white border border-slate-200 shadow-xs hover:border-emerald-300 transition">
            <div class="flex items-center justify-between text-slate-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600">In Stock</span>
                <i class="fa-solid fa-circle-check text-emerald-500"></i>
            </div>
            <div class="text-2xl font-bold text-emerald-700">{{ $stats['in_stock'] }}</div>
        </a>

        <a href="{{ route('products.index', ['status' => 'low_stock']) }}" class="p-4 rounded-xl bg-white border border-slate-200 shadow-xs hover:border-amber-300 transition">
            <div class="flex items-center justify-between text-slate-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-amber-600">Low Stock Alert</span>
                <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
            </div>
            <div class="text-2xl font-bold text-amber-700">{{ $stats['low_stock'] }}</div>
        </a>

        <a href="{{ route('products.index', ['status' => 'out_of_stock']) }}" class="p-4 rounded-xl bg-white border border-slate-200 shadow-xs hover:border-rose-300 transition">
            <div class="flex items-center justify-between text-slate-500 mb-1">
                <span class="text-xs font-semibold uppercase tracking-wider text-rose-600">Out of Stock</span>
                <i class="fa-solid fa-circle-xmark text-rose-500"></i>
            </div>
            <div class="text-2xl font-bold text-rose-700">{{ $stats['out_of_stock'] }}</div>
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('products.index') }}" class="w-full md:w-auto flex-1 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[240px]">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name, product code, quality..."
                       class="w-full pl-9 pr-4 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
            </div>

            <select name="status" class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                <option value="">All Stock Statuses</option>
                <option value="in_stock" {{ request('status') === 'in_stock' ? 'selected' : '' }}>In Stock Only</option>
                <option value="low_stock" {{ request('status') === 'low_stock' ? 'selected' : '' }}>Low Stock Alerts</option>
                <option value="out_of_stock" {{ request('status') === 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
            </select>

            @if(isset($users) && $users->count() > 0)
                <select name="user_id" class="py-2 px-3 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white">
                    <option value="">All Users / Creators</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ (string)request('user_id') === (string)$user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            @endif

            <button type="submit" class="px-4 py-2 text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                Filter
            </button>

            @if (request()->hasAny(['search', 'status', 'user_id']))
                <a href="{{ route('products.index') }}" class="px-3 py-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                    Clear
                </a>
            @endif
        </form>

        <a href="{{ route('products.create') }}" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 shadow transition">
            <i class="fa-solid fa-plus"></i>
            <span>Add New Product</span>
        </a>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3.5">Product Photo</th>
                        <th class="px-4 py-3.5">Product Info</th>
                        <th class="px-4 py-3.5">Quality</th>
                        <th class="px-4 py-3.5 text-right">Rates & Cost</th>
                        <th class="px-4 py-3.5 text-right">Unit Profit</th>
                        <th class="px-4 py-3.5 text-center">Stock Level</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @forelse ($products as $product)
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- Photo -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="w-14 h-14 rounded-lg overflow-hidden border border-slate-200 bg-slate-100 flex items-center justify-center">
                                    @if ($product->photo_url)
                                        <img src="{{ $product->photo_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="fa-regular fa-image text-slate-400 text-xl"></i>
                                    @endif
                                </div>
                            </td>

                            <!-- Product Name & Number -->
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800">{{ $product->name }}</div>
                                <div class="flex items-center gap-1.5 flex-wrap mt-1">
                                    <span class="font-mono text-xs text-indigo-600 font-semibold bg-indigo-50 px-2 py-0.5 rounded">
                                        {{ $product->product_number }}
                                    </span>
                                    @if ($product->user_name || $product->user)
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-600 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded" title="Added by user">
                                            <i class="fa-solid fa-user-tag text-[9px] text-slate-400"></i>
                                            {{ $product->user_name ?? $product->user->name }}
                                        </span>
                                    @endif
                                </div>
                                @if ($product->description)
                                    <div class="text-xs text-slate-400 truncate max-w-xs mt-1">{{ $product->description }}</div>
                                @endif
                            </td>

                            <!-- Quality -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-1 rounded text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $product->quality ?: 'Standard' }}
                                </span>
                            </td>

                            <!-- Rates Breakdown -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="text-xs space-y-0.5">
                                    <div><span class="text-slate-400">Sale:</span> <strong class="text-slate-800">₹{{ number_format($product->sale_rate, 2) }}</strong></div>
                                    <div><span class="text-slate-400">Cost:</span> ₹{{ number_format($product->purchase_rate, 2) }}</div>
                                    @if ($product->other_rate > 0)
                                        <div><span class="text-slate-400">Other:</span> ₹{{ number_format($product->other_rate, 2) }}</div>
                                    @endif
                                </div>
                            </td>

                            <!-- Auto Profit -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $product->profit_per_unit >= 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                    +₹{{ number_format($product->profit_per_unit, 2) }}
                                </span>
                            </td>

                            <!-- Stock Status -->
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if ($product->stock_quantity <= 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Out of Stock (0)
                                    </span>
                                @elseif ($product->stock_quantity <= $product->low_stock_threshold)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        Low: {{ $product->stock_quantity }} units
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $product->stock_quantity }} in stock
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Quick Check-in trigger -->
                                    <a href="{{ route('stock.index', ['product_id' => $product->id]) }}" title="Check-In Stock"
                                       class="p-2 rounded text-emerald-600 hover:bg-emerald-50 transition">
                                        <i class="fa-solid fa-arrow-down"></i>
                                    </a>

                                    <a href="{{ route('products.edit', $product) }}" title="Edit Product"
                                       class="p-2 rounded text-indigo-600 hover:bg-indigo-50 transition">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    <form method="POST" action="{{ route('products.destroy', $product) }}"
                                          onsubmit="return confirm('Are you sure you want to delete this product?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Delete Product" class="p-2 rounded text-rose-600 hover:bg-rose-50 transition">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-400">
                                <i class="fa-solid fa-box-open text-4xl mb-3 text-slate-300 block"></i>
                                <p class="text-base font-medium text-slate-600">No products found</p>
                                <p class="text-xs text-slate-400 mt-1">Get started by creating your first product with rates and photo.</p>
                                <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 text-sm font-semibold rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition">
                                    <i class="fa-solid fa-plus"></i> Add Product
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
