@extends('layouts.app')

@section('title', 'Create Customer Checkout')
@section('page_heading', 'New Customer Checkout')

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        products: {{ Js::from($products) }},
        items: [
            { product_id: '', quantity: 1, unit_sale_rate: 0, stock: 0, unit_profit: 0 }
        ],
        addItem() {
            this.items.push({ product_id: '', quantity: 1, unit_sale_rate: 0, stock: 0, unit_profit: 0 });
        },
        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
            }
        },
        productChanged(index) {
            const prodId = this.items[index].product_id;
            const found = this.products.find(p => p.id == prodId);
            if (found) {
                this.items[index].unit_sale_rate = parseFloat(found.sale_rate);
                this.items[index].stock = parseInt(found.stock_quantity);
                this.items[index].unit_profit = parseFloat(found.profit_per_unit);
                if (this.items[index].quantity > found.stock_quantity) {
                    this.items[index].quantity = found.stock_quantity > 0 ? 1 : 0;
                }
            } else {
                this.items[index].unit_sale_rate = 0;
                this.items[index].stock = 0;
                this.items[index].unit_profit = 0;
            }
        },
        get totalQuantity() {
            return this.items.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
        },
        get totalSaleAmount() {
            return this.items.reduce((sum, item) => sum + ((parseFloat(item.unit_sale_rate) || 0) * (parseInt(item.quantity) || 0)), 0).toFixed(2);
        },
        get totalProfit() {
            return this.items.reduce((sum, item) => {
                const found = this.products.find(p => p.id == item.product_id);
                if (!found) return sum;
                const sale = parseFloat(item.unit_sale_rate) || 0;
                const purchase = parseFloat(found.purchase_rate) || 0;
                const other = parseFloat(found.other_rate) || 0;
                const unitProfit = sale - purchase - other;
                return sum + (unitProfit * (parseInt(item.quantity) || 0));
            }, 0).toFixed(2);
        }
     }">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Record Customer Sale & Checkout</h3>
            <p class="text-sm text-slate-500">Record buyer address, enquiry source, and line items with automatic profit calculation.</p>
        </div>
        <a href="{{ route('checkouts.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Ledger
        </a>
    </div>

    <form method="POST" action="{{ route('checkouts.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Customer Information & Enquiry Source -->
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <i class="fa-solid fa-user-tag text-indigo-600"></i>
                    <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Buyer Information</h4>
                </div>

                <div>
                    <label for="customer_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Customer / Buyer Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required
                           placeholder="Full name of customer"
                           class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label for="customer_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Customer Address <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="customer_address" name="customer_address" rows="3" required
                              placeholder="Full delivery address, street, city, country..."
                              class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('customer_address') }}</textarea>
                </div>

                <div>
                    <label for="customer_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Phone / Contact Number
                    </label>
                    <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}"
                           placeholder="e.g. +1 555-0199 or WhatsApp number"
                           class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label for="enquiry_from" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Enquiry From / Source <span class="text-rose-500">*</span>
                    </label>
                    <select id="enquiry_from" name="enquiry_from" required
                            class="w-full px-3.5 py-2.5 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">-- Select Source --</option>
                        @foreach ($defaultSources as $source)
                            <option value="{{ $source }}" {{ old('enquiry_from') === $source ? 'selected' : '' }}>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Order Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="2" placeholder="Tracking number, delivery notes..."
                              class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Right: Order Line Items & Financial Summary -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-boxes-stacked text-indigo-600"></i>
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Products to Purchase</h4>
                        </div>
                        <button type="button" @click="addItem()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition">
                            <i class="fa-solid fa-plus"></i> Add Item
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70 flex flex-col sm:flex-row items-center gap-3">
                                <!-- Product Select -->
                                <div class="flex-1 w-full">
                                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Product</label>
                                    <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" @change="productChanged(index)" required
                                            class="w-full px-3 py-2 text-xs md:text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                        <option value="">-- Choose Product --</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" :disabled="p.stock_quantity <= 0"
                                                    x-text="p.name + ' (' + p.product_number + ') - ' + p.stock_quantity + ' in stock'"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Quantity -->
                                <div class="w-full sm:w-28">
                                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Quantity</label>
                                    <input type="number" min="1" :max="item.stock || 9999" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required
                                           class="w-full px-3 py-2 text-xs md:text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>

                                <!-- Sale Rate (editable override) -->
                                <div class="w-full sm:w-32">
                                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Sale Rate (₹)</label>
                                    <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_sale_rate]'" x-model="item.unit_sale_rate" required
                                           class="w-full px-3 py-2 text-xs md:text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>

                                <!-- Subtotal Preview -->
                                <div class="w-full sm:w-28 text-right self-end sm:self-center pt-2 sm:pt-0">
                                    <span class="block text-[11px] text-slate-400">Subtotal:</span>
                                    <span class="font-bold text-slate-800 text-sm" x-text="'₹' + ((parseFloat(item.unit_sale_rate) || 0) * (parseInt(item.quantity) || 0)).toFixed(2)"></span>
                                </div>

                                <!-- Remove -->
                                <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                        class="p-2 text-rose-500 hover:bg-rose-50 rounded-lg self-end sm:self-center">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Financial Summary & Profit Bar -->
                    <div class="mt-6 p-5 rounded-xl bg-slate-900 text-white space-y-3">
                        <div class="flex justify-between text-sm text-slate-300">
                            <span>Total Units to Check Out:</span>
                            <span class="font-semibold text-white" x-text="totalQuantity"></span>
                        </div>
                        <div class="flex justify-between text-base">
                            <span class="font-medium text-slate-300">Total Checkout Amount:</span>
                            <span class="font-bold text-xl text-white" x-text="'₹' + totalSaleAmount"></span>
                        </div>
                        <div class="pt-3 border-t border-slate-800 flex justify-between items-center">
                            <div>
                                <span class="text-xs uppercase tracking-wider font-semibold text-emerald-400">Calculated Net Profit</span>
                                <p class="text-[11px] text-slate-400">Calculated after cost & expenses</p>
                            </div>
                            <span class="text-2xl font-black text-emerald-400" x-text="'+₹' + totalProfit"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('checkouts.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow transition flex items-center gap-2">
                        <i class="fa-solid fa-check-circle"></i> Complete Checkout & Deduct Stock
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
