@extends('layouts.app')

@section('title', 'Edit Product - ' . $product->name)
@section('page_heading', 'Edit Product')

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        purchaseRate: {{ (float) $product->purchase_rate }},
        saleRate: {{ (float) $product->sale_rate }},
        otherRate: {{ (float) $product->other_rate }},
        stockQty: {{ (int) $product->stock_quantity }},
        photoPreview: '{{ $product->photo_url }}',
        get unitProfit() {
            return (parseFloat(this.saleRate || 0) - parseFloat(this.purchaseRate || 0) - parseFloat(this.otherRate || 0)).toFixed(2);
        },
        get totalBatchProfit() {
            return (this.unitProfit * parseInt(this.stockQty || 0)).toFixed(2);
        },
        fileChosen(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => { this.photoPreview = e.target.result; };
                reader.readAsDataURL(file);
            }
        }
     }">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Edit Product #{{ $product->product_number }}</h3>
            <p class="text-sm text-slate-500">Update rates, stock parameters, quality, and photo.</p>
        </div>
        <a href="{{ route('products.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Products
        </a>
    </div>

    <form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Photo & Live Profit Preview -->
            <div class="space-y-6">
                <!-- Photo Upload Box -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Product Photo</label>
                    <div class="mt-1 flex justify-center px-4 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl relative hover:border-indigo-400 transition bg-slate-50">
                        <template x-if="photoPreview">
                            <div class="relative w-full aspect-square rounded-lg overflow-hidden border border-slate-200">
                                <img :src="photoPreview" class="w-full h-full object-cover">
                                <label for="photo" class="absolute bottom-2 right-2 px-2.5 py-1 rounded bg-slate-900/80 text-white text-xs font-medium cursor-pointer hover:bg-slate-900">
                                    Change Photo
                                </label>
                            </div>
                        </template>

                        <div x-show="!photoPreview" class="space-y-2 text-center py-4">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-indigo-500"></i>
                            <div class="flex text-xs text-slate-600 justify-center">
                                <label for="photo" class="relative cursor-pointer rounded font-medium text-indigo-600 hover:text-indigo-500">
                                    <span>Upload a photo</span>
                                </label>
                            </div>
                            <p class="text-[11px] text-slate-400">PNG, JPG, WEBP up to 5MB</p>
                        </div>
                        <input id="photo" name="photo" type="file" accept="image/*" class="sr-only" x-ref="photoInput" @change="fileChosen">
                    </div>
                </div>

                <!-- Live Profit Calculation Card -->
                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 p-5 rounded-xl text-white shadow-lg space-y-4">
                    <div class="flex items-center justify-between border-b border-indigo-900/60 pb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-indigo-300">Live Profit Calculation</span>
                        <i class="fa-solid fa-calculator text-indigo-400"></i>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between text-slate-300">
                            <span>Sale Rate:</span>
                            <span class="font-bold text-white" x-text="'₹' + parseFloat(saleRate || 0).toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>- Purchase Rate:</span>
                            <span x-text="'-₹' + parseFloat(purchaseRate || 0).toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>- Other Expenses:</span>
                            <span x-text="'-₹' + parseFloat(otherRate || 0).toFixed(2)"></span>
                        </div>
                    </div>

                    <div class="border-t border-indigo-900/60 pt-3">
                        <div class="text-[11px] uppercase font-semibold text-emerald-400 tracking-wider">Net Profit Per Unit</div>
                        <div class="text-2xl font-black mt-1" :class="unitProfit >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                             x-text="(unitProfit >= 0 ? '+₹' : '-₹') + Math.abs(unitProfit)">
                            +₹0.00
                        </div>
                    </div>

                    <div class="bg-indigo-900/40 p-2.5 rounded-lg text-xs flex justify-between items-center">
                        <span class="text-slate-300">Total Potential Value:</span>
                        <span class="font-bold text-emerald-300" x-text="'₹' + totalBatchProfit"></span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Fields -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Product Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}" required
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>

                        <div>
                            <label for="product_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Product Code / SKU <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="product_number" name="product_number" value="{{ old('product_number', $product->product_number) }}" required
                                   class="w-full font-mono px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="quality" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Quality Grade / Type
                            </label>
                            <input type="text" id="quality" name="quality" value="{{ old('quality', $product->quality) }}"
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>

                        <div>
                            <label for="price" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Retail / Display Price (₹) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', $product->price) }}" required
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Financial Rates -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Financial Rates</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="purchase_rate" class="block text-[11px] font-semibold text-slate-500 mb-1">
                                    Purchase Rate (Cost) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" min="0" id="purchase_rate" name="purchase_rate"
                                       x-model="purchaseRate" value="{{ old('purchase_rate', $product->purchase_rate) }}" required
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div>
                                <label for="sale_rate" class="block text-[11px] font-semibold text-slate-500 mb-1">
                                    Sale Rate <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" min="0" id="sale_rate" name="sale_rate"
                                       x-model="saleRate" value="{{ old('sale_rate', $product->sale_rate) }}" required
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div>
                                <label for="other_rate" class="block text-[11px] font-semibold text-slate-500 mb-1">
                                    Other Expenses / Overhead
                                </label>
                                <input type="number" step="0.01" min="0" id="other_rate" name="other_rate"
                                       x-model="otherRate" value="{{ old('other_rate', $product->other_rate) }}"
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Stock Quantities -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="stock_quantity" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Current Stock Quantity
                            </label>
                            <input type="number" min="0" id="stock_quantity" name="stock_quantity"
                                   x-model="stockQty" value="{{ old('stock_quantity', $product->stock_quantity) }}"
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>

                        <div>
                            <label for="low_stock_threshold" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Low Stock Alert Threshold
                            </label>
                            <input type="number" min="1" id="low_stock_threshold" name="low_stock_threshold"
                                   value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}"
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Product Description / Specifications
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('products.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow transition flex items-center gap-2">
                        <i class="fa-solid fa-save"></i> Update Product
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
