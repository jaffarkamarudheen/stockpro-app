@extends('layouts.app')

@section('title', 'Create Customer Checkout')
@section('page_heading', 'New Customer Checkout')

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        products: {{ Js::from($products) }},
        isPromotion: false,
        discountAmount: 0,
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
        get subtotalAmount() {
            return this.items.reduce((sum, item) => sum + ((parseFloat(item.unit_sale_rate) || 0) * (parseInt(item.quantity) || 0)), 0);
        },
        get totalSaleAmount() {
            if (this.isPromotion) return (0).toFixed(2);
            const sub = this.subtotalAmount;
            const disc = parseFloat(this.discountAmount) || 0;
            return Math.max(0, sub - disc).toFixed(2);
        },
        get totalProfit() {
            if (this.isPromotion) return (0).toFixed(2);
            const subProfit = this.items.reduce((sum, item) => {
                const found = this.products.find(p => p.id == item.product_id);
                if (!found) return sum;
                const sale = parseFloat(item.unit_sale_rate) || 0;
                const purchase = parseFloat(found.purchase_rate) || 0;
                const other = parseFloat(found.other_rate) || 0;
                const unitProfit = sale - purchase - other;
                return sum + (unitProfit * (parseInt(item.quantity) || 0));
            }, 0);
            const disc = parseFloat(this.discountAmount) || 0;
            return (subProfit - disc).toFixed(2);
        }
     }">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Record Customer Sale & Checkout</h3>
            <p class="text-sm text-slate-500">Record order dates, delivery status, promotions without money, and discounts with automatic stock deduction.</p>
        </div>
        <a href="{{ route('checkouts.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Ledger
        </a>
    </div>

    <form method="POST" action="{{ route('checkouts.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Customer Information, Dates & Status -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <i class="fa-solid fa-user-tag text-indigo-600"></i>
                    <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Buyer & Order Details</h4>
                </div>

                <!-- Customer Name -->
                <div>
                    <label for="customer_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Customer / Buyer Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}" required
                           placeholder="Full name of customer"
                           class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Customer Address -->
                <div>
                    <label for="customer_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Customer Address <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="customer_address" name="customer_address" rows="3" required
                              placeholder="Full delivery address, street, city, postal code..."
                              class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('customer_address') }}</textarea>
                </div>

                <!-- Phone -->
                <div>
                    <label for="customer_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Phone / WhatsApp
                    </label>
                    <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone') }}"
                           placeholder="e.g. +91 9876543210"
                           class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Enquiry From -->
                <div>
                    <label for="enquiry_from" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Enquiry From / Source <span class="text-rose-500">*</span>
                    </label>
                    <select id="enquiry_from" name="enquiry_from" required
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        <option value="">-- Select Source --</option>
                        @foreach ($defaultSources as $source)
                            <option value="{{ $source }}" {{ old('enquiry_from') === $source ? 'selected' : '' }}>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Order Status & Dates -->
                <div class="pt-2 border-t border-slate-100 space-y-3">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Order Dates & Status</div>

                    <!-- Status Selector -->
                    <div>
                        <label for="status" class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                        <select id="status" name="status"
                                class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <option value="ordered" selected>Ordered (Pending Delivery)</option>
                            <option value="waiting_for_delivery">Waiting for Delivery</option>
                            <option value="delivered">Delivered</option>
                            <option value="received">Received</option>
                        </select>
                    </div>

                    <!-- Ordered Date -->
                    <div>
                        <label for="ordered_at" class="block text-xs font-medium text-slate-600 mb-1">Order Date</label>
                        <input type="datetime-local" id="ordered_at" name="ordered_at" value="{{ now()->format('Y-m-d\TH:i') }}"
                               class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    </div>

                    <!-- Expected Delivery Date -->
                    <div>
                        <label for="expected_delivery_date" class="block text-xs font-medium text-slate-600 mb-1">Expected Delivery Date</label>
                        <input type="date" id="expected_delivery_date" name="expected_delivery_date" value="{{ old('expected_delivery_date') }}"
                               class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Order Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="2" placeholder="Packaging instructions, tracking number..."
                              class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('notes') }}</textarea>
                </div>
            </div>

            <!-- Right: Order Line Items, Promotions, Discounts & Financial Summary -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-boxes-stacked text-indigo-600"></i>
                            <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Products in Order</h4>
                        </div>
                        <button type="button" @click="addItem()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition">
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
                                            class="w-full px-3 py-2 text-xs md:text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                        <option value="">-- Choose Product --</option>
                                        @foreach ($products as $p)
                                            <option value="{{ $p->id }}" {{ $p->stock_quantity <= 0 ? 'disabled' : '' }}>
                                                {{ $p->name }} ({{ $p->product_number }}) - {{ $p->stock_quantity }} in stock
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Quantity -->
                                <div class="w-full sm:w-28">
                                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Quantity</label>
                                    <input type="number" min="1" :max="item.stock || 9999" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required
                                           class="w-full px-3 py-2 text-xs md:text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>

                                <!-- Sale Rate (editable override) -->
                                <div class="w-full sm:w-32">
                                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Sale Rate (₹)</label>
                                    <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_sale_rate]'" x-model="item.unit_sale_rate" required
                                           class="w-full px-3 py-2 text-xs md:text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
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

                    <!-- Promotion and Discount Controls -->
                    <div class="pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Promotion Toggle (Without Money) -->
                        <div class="p-3.5 rounded-xl border border-pink-200 bg-pink-50/50 flex items-start gap-3">
                            <input type="checkbox" id="is_promotion" name="is_promotion" value="1" x-model="isPromotion"
                                   class="mt-1 w-4 h-4 text-pink-600 rounded border-pink-300 focus:ring-pink-500 cursor-pointer">
                            <label for="is_promotion" class="cursor-pointer text-xs">
                                <span class="font-bold text-pink-900 block flex items-center gap-1.5">
                                    <i class="fa-solid fa-gift text-pink-600"></i> Promotional Checkout (Free / Without Money)
                                </span>
                                <span class="text-pink-700 mt-0.5 block leading-normal">
                                    Deducts stock without charging customer. Will not deduct money on revenue reports.
                                </span>
                            </label>
                        </div>

                        <!-- Discount Field -->
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70" x-show="!isPromotion">
                            <label for="discount_amount" class="block text-xs font-semibold text-slate-700 mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-tag text-indigo-500"></i>
                                <span>Order Discount (₹)</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="discount_amount" name="discount_amount" x-model="discountAmount"
                                   placeholder="0.00"
                                   class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <span class="text-[10px] text-slate-400 mt-1 block">Deducted from subtotal amount</span>
                        </div>
                    </div>

                    <!-- Financial Summary Bar -->
                    <div class="mt-4 p-5 rounded-2xl bg-slate-900 text-white space-y-3">
                        <div class="flex justify-between text-sm text-slate-300">
                            <span>Total Units to Check Out:</span>
                            <span class="font-semibold text-white" x-text="totalQuantity + ' units'"></span>
                        </div>

                        <div class="flex justify-between text-sm text-slate-400">
                            <span>Items Subtotal:</span>
                            <span class="font-medium text-slate-200" x-text="'₹' + subtotalAmount.toFixed(2)"></span>
                        </div>

                        <div class="flex justify-between text-sm text-rose-400" x-show="!isPromotion && parseFloat(discountAmount) > 0">
                            <span>Discount Applied:</span>
                            <span class="font-bold" x-text="'-₹' + (parseFloat(discountAmount) || 0).toFixed(2)"></span>
                        </div>

                        <div class="flex justify-between text-base pt-2 border-t border-slate-800">
                            <span class="font-bold text-slate-200">Final Sale Amount:</span>
                            <span class="font-black text-2xl" :class="isPromotion ? 'text-pink-400' : 'text-white'"
                                  x-text="isPromotion ? 'FREE (₹0.00)' : '₹' + totalSaleAmount"></span>
                        </div>

                        <div class="pt-2 border-t border-slate-800/80 flex justify-between items-center" x-show="!isPromotion">
                            <div>
                                <span class="text-xs uppercase tracking-wider font-semibold text-emerald-400">Net Profit</span>
                                <p class="text-[11px] text-slate-400">Calculated after cost & discount</p>
                            </div>
                            <span class="text-xl font-black text-emerald-400" x-text="'+₹' + totalProfit"></span>
                        </div>

                        <div class="pt-2 border-t border-slate-800/80 text-center" x-show="isPromotion">
                            <span class="text-xs text-pink-300 font-semibold">🎁 Promotional Order (Stock deducted only, no money recorded in sales reports)</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('checkouts.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow transition flex items-center gap-2">
                        <i class="fa-solid fa-check-circle"></i>
                        <span>Complete Checkout & Deduct Stock</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
