@extends('layouts.app')

@section('title', 'Edit Order #' . $checkout->order_number)
@section('page_heading', 'Edit Order #' . $checkout->order_number)

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        products: {{ Js::from($products) }},
        isPromotion: {{ $checkout->is_promotion ? 'true' : 'false' }},
        discountAmount: {{ (float) $checkout->discount_amount }},
        status: '{{ $checkout->status }}',
        items: {{ Js::from($checkout->items->map(fn($item) => [
            'product_id' => (string) $item->product_id,
            'quantity' => (int) $item->quantity,
            'unit_sale_rate' => (float) $item->unit_sale_rate,
            'stock' => (int) ($item->product?->stock_quantity ?? 0) + (int) $item->quantity,
            'purchase_rate' => (float) ($item->unit_purchase_rate ?? $item->product?->purchase_rate ?? 0),
            'unit_other_rate' => (float) ($item->unit_other_rate ?? $item->product?->other_rate ?? 90),
            'has_delivery' => ((float) ($item->unit_other_rate ?? $item->product?->other_rate ?? 90)) >= 50,
            'has_packaging' => ((((float) ($item->unit_other_rate ?? $item->product?->other_rate ?? 90)) % 50 === 40.0) || ((float) ($item->unit_other_rate ?? $item->product?->other_rate ?? 90)) >= 90 || ((float) ($item->unit_other_rate ?? $item->product?->other_rate ?? 90)) === 40.0),
            'unit_profit' => (float) $item->unit_profit
        ])) }},
        addItem() {
            this.items.push({
                product_id: '',
                quantity: 1,
                unit_sale_rate: 0,
                stock: 0,
                unit_profit: 0,
                purchase_rate: 0,
                has_delivery: true,
                has_packaging: true,
                unit_other_rate: 90
            });
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
                this.items[index].purchase_rate = parseFloat(found.purchase_rate) || 0;
                this.items[index].unit_other_rate = parseFloat(found.other_rate) || 90;
                this.items[index].has_delivery = this.items[index].unit_other_rate >= 50;
                this.items[index].has_packaging = (this.items[index].unit_other_rate % 50 === 40) || (this.items[index].unit_other_rate >= 90) || (this.items[index].unit_other_rate === 40);
                this.recalcItemProfit(index);
                if (this.items[index].quantity > this.items[index].stock) {
                    this.items[index].quantity = this.items[index].stock > 0 ? 1 : 0;
                }
            } else {
                this.items[index].unit_sale_rate = 0;
                this.items[index].stock = 0;
                this.items[index].purchase_rate = 0;
                this.items[index].unit_other_rate = 0;
                this.items[index].unit_profit = 0;
            }
        },
        toggleDelivery(index) {
            const item = this.items[index];
            item.has_delivery = !item.has_delivery;
            this.recomputeOverhead(index);
        },
        togglePackaging(index) {
            const item = this.items[index];
            item.has_packaging = !item.has_packaging;
            this.recomputeOverhead(index);
        },
        recomputeOverhead(index) {
            const item = this.items[index];
            let rate = 0;
            if (item.has_delivery) rate += 50;
            if (item.has_packaging) rate += 40;
            item.unit_other_rate = rate;
            this.recalcItemProfit(index);
        },
        recalcItemProfit(index) {
            const item = this.items[index];
            const sale = parseFloat(item.unit_sale_rate) || 0;
            const purchase = parseFloat(item.purchase_rate) || 0;
            const other = (item.unit_other_rate !== undefined && item.unit_other_rate !== null && item.unit_other_rate !== '')
                ? (parseFloat(item.unit_other_rate) || 0)
                : 0;
            item.unit_profit = parseFloat((sale - purchase - other).toFixed(2));
        },
        applyPresetDiscount(amount) {
            this.discountAmount = (parseFloat(this.discountAmount || 0) + amount).toFixed(2);
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
                if (!found && !item.unit_sale_rate) return sum;
                const sale = parseFloat(item.unit_sale_rate) || 0;
                const purchase = parseFloat(item.purchase_rate ?? found?.purchase_rate) || 0;
                const other = (item.unit_other_rate !== undefined && item.unit_other_rate !== null && item.unit_other_rate !== '')
                    ? (parseFloat(item.unit_other_rate) || 0)
                    : (parseFloat(found?.other_rate) || 0);
                const unitProfit = sale - purchase - other;
                return sum + (unitProfit * (parseInt(item.quantity) || 0));
            }, 0);
            const disc = parseFloat(this.discountAmount) || 0;
            return (subProfit - disc).toFixed(2);
        }
     }">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-slate-800">Edit Order #{{ $checkout->order_number }}</h3>
            <p class="text-sm text-slate-500">Update customer information, line items, delivery dates, or change order status.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('checkouts.show', $checkout) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i> Back to Invoice
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('checkouts.update', $checkout) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Buyer Details, Status & Dates -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                    <i class="fa-solid fa-user-pen text-indigo-600"></i>
                    <h4 class="text-sm font-bold text-slate-800 uppercase tracking-wide">Buyer & Status</h4>
                </div>

                <!-- Customer Name -->
                <div>
                    <label for="customer_name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Customer / Buyer Name <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name', $checkout->customer_name) }}" required
                           class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Customer Address -->
                <div>
                    <label for="customer_address" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Customer Address <span class="text-rose-500">*</span>
                    </label>
                    <textarea id="customer_address" name="customer_address" rows="3" required
                              class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('customer_address', $checkout->customer_address) }}</textarea>
                </div>

                <!-- Phone -->
                <div>
                    <label for="customer_phone" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Phone / WhatsApp
                    </label>
                    <input type="text" id="customer_phone" name="customer_phone" value="{{ old('customer_phone', $checkout->customer_phone) }}"
                           class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <!-- Enquiry From -->
                <div>
                    <label for="enquiry_from" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Enquiry From / Source <span class="text-rose-500">*</span>
                    </label>
                    <select id="enquiry_from" name="enquiry_from" required
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                        @foreach ($defaultSources as $source)
                            <option value="{{ $source }}" {{ old('enquiry_from', $checkout->enquiry_from) === $source ? 'selected' : '' }}>
                                {{ $source }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status & Dates -->
                <div class="pt-3 border-t border-slate-100 space-y-3">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Order Status & Timeline</div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                        <select id="status" name="status" x-model="status"
                                class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white font-semibold">
                            <option value="ordered">Ordered</option>
                            <option value="waiting_for_delivery">Waiting for Delivery</option>
                            <option value="delivered">Delivered</option>
                            <option value="received">Received</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>

                    <!-- Ordered Date -->
                    <div>
                        <label for="ordered_at" class="block text-xs font-medium text-slate-600 mb-1">Order Date</label>
                        <input type="datetime-local" id="ordered_at" name="ordered_at"
                               value="{{ $checkout->ordered_at ? $checkout->ordered_at->format('Y-m-d\TH:i') : '' }}"
                               class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    </div>

                    <!-- Expected Delivery Date -->
                    <div>
                        <label for="expected_delivery_date" class="block text-xs font-medium text-slate-600 mb-1">Expected Delivery Date</label>
                        <input type="date" id="expected_delivery_date" name="expected_delivery_date"
                               value="{{ $checkout->expected_delivery_date ? $checkout->expected_delivery_date->format('Y-m-d') : '' }}"
                               class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    </div>

                    <!-- Delivered Date -->
                    <div x-show="status === 'delivered' || status === 'received'">
                        <label for="delivered_at" class="block text-xs font-medium text-slate-600 mb-1">Delivered On</label>
                        <input type="datetime-local" id="delivered_at" name="delivered_at"
                               value="{{ $checkout->delivered_at ? $checkout->delivered_at->format('Y-m-d\TH:i') : '' }}"
                               class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    </div>

                    <!-- Received Date -->
                    <div x-show="status === 'received'">
                        <label for="received_at" class="block text-xs font-medium text-slate-600 mb-1">Received On</label>
                        <input type="datetime-local" id="received_at" name="received_at"
                               value="{{ $checkout->received_at ? $checkout->received_at->format('Y-m-d\TH:i') : '' }}"
                               class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                        Order Notes (Optional)
                    </label>
                    <textarea id="notes" name="notes" rows="2" class="w-full px-3.5 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">{{ old('notes', $checkout->notes) }}</textarea>
                </div>
            </div>

            <!-- Right: Line Items & Discounts -->
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
                            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/80 space-y-3">
                                <div class="flex flex-col sm:flex-row items-center gap-3">
                                    <!-- Product Select -->
                                    <div class="flex-1 w-full">
                                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Product</label>
                                        <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" @change="productChanged(index)" required
                                                class="w-full px-3 py-2 text-xs md:text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                            <option value="">-- Choose Product --</option>
                                            @foreach ($products as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->product_number }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Quantity -->
                                    <div class="w-full sm:w-28">
                                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Quantity</label>
                                        <input type="number" min="1" :name="'items[' + index + '][quantity]'" x-model="item.quantity" required
                                               class="w-full px-3 py-2 text-xs md:text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                    </div>

                                    <!-- Sale Rate -->
                                    <div class="w-full sm:w-32">
                                        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Sale Rate (₹)</label>
                                        <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_sale_rate]'" x-model="item.unit_sale_rate" @input="recalcItemProfit(index)" required
                                               class="w-full px-3 py-2 text-xs md:text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                                    </div>

                                    <!-- Subtotal -->
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

                                <!-- Overhead / Expenses Toggle Row (Delivery ₹50 & Box/Pack ₹40) -->
                                <div class="pt-2.5 border-t border-slate-200 flex flex-wrap items-center justify-between gap-2.5">
                                    <div class="flex items-center flex-wrap gap-2">
                                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                            Overhead / Expenses:
                                        </span>

                                        <!-- Delivery Toggle (₹50) -->
                                        <button type="button" @click="toggleDelivery(index)"
                                                :class="item.has_delivery ? 'bg-indigo-100 text-indigo-700 border-indigo-300 font-semibold shadow-xs' : 'bg-slate-200/80 text-slate-400 border-slate-300 line-through'"
                                                class="px-2.5 py-1 rounded-lg border text-xs flex items-center gap-1.5 transition select-none">
                                            <i class="fa-solid fa-truck-fast"></i>
                                            <span>Delivery (₹50)</span>
                                            <i :class="item.has_delivery ? 'fa-solid fa-check text-indigo-600' : 'fa-solid fa-xmark text-slate-400'"></i>
                                        </button>

                                        <!-- Box & Packaging Toggle (₹40) -->
                                        <button type="button" @click="togglePackaging(index)"
                                                :class="item.has_packaging ? 'bg-amber-100 text-amber-800 border-amber-300 font-semibold shadow-xs' : 'bg-slate-200/80 text-slate-400 border-slate-300 line-through'"
                                                class="px-2.5 py-1 rounded-lg border text-xs flex items-center gap-1.5 transition select-none">
                                            <i class="fa-solid fa-box-open"></i>
                                            <span>Box & Pack (₹40)</span>
                                            <i :class="item.has_packaging ? 'fa-solid fa-check text-amber-700' : 'fa-solid fa-xmark text-slate-400'"></i>
                                        </button>
                                    </div>

                                    <!-- Exact Overhead Input & Unit Profit Indicator -->
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1.5">
                                            <label class="text-[11px] text-slate-600 font-medium">Expense/unit:</label>
                                            <div class="relative w-20">
                                                <span class="absolute inset-y-0 left-0 pl-2 flex items-center text-slate-400 text-xs">₹</span>
                                                <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_other_rate]'"
                                                       x-model="item.unit_other_rate" @input="recalcItemProfit(index)"
                                                       class="w-full pl-5 pr-2 py-1 text-xs font-semibold rounded-lg border border-slate-300 focus:ring-1 focus:ring-indigo-500 outline-none bg-white">
                                            </div>
                                        </div>
                                        <div class="text-[11px] text-slate-500 bg-white px-2 py-1 rounded-lg border border-slate-200">
                                            Profit/unit: <span class="font-bold text-emerald-600" x-text="'₹' + item.unit_profit"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Promotion & Discount -->
                    <div class="pt-4 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="p-3.5 rounded-xl border border-pink-200 bg-pink-50/50 flex items-start gap-3">
                            <input type="checkbox" id="is_promotion" name="is_promotion" value="1" x-model="isPromotion"
                                   class="mt-1 w-4 h-4 text-pink-600 rounded border-pink-300 focus:ring-pink-500 cursor-pointer">
                            <label for="is_promotion" class="cursor-pointer text-xs">
                                <span class="font-bold text-pink-900 block flex items-center gap-1.5">
                                    <i class="fa-solid fa-gift text-pink-600"></i> Promotional Checkout (Free / Without Money)
                                </span>
                                <span class="text-pink-700 mt-0.5 block">
                                    Only deducts stock, sets sale to ₹0 without impacting sales reports.
                                </span>
                            </label>
                        </div>

                        <!-- Discount Field with Quick Buttons -->
                        <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/70" x-show="!isPromotion">
                            <div class="flex items-center justify-between mb-1">
                                <label for="discount_amount" class="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <i class="fa-solid fa-tag text-indigo-500"></i>
                                    <span>Order Discount (₹)</span>
                                </label>
                                <!-- Quick Discount Preset Buttons -->
                                <div class="flex items-center gap-1 text-[10px]">
                                    <button type="button" @click="applyPresetDiscount(50)"
                                            class="px-1.5 py-0.5 rounded bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-semibold" title="Pass delivery discount to customer">
                                        +₹50 (Deliv)
                                    </button>
                                    <button type="button" @click="applyPresetDiscount(40)"
                                            class="px-1.5 py-0.5 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 font-semibold" title="Pass box discount to customer">
                                        +₹40 (Box)
                                    </button>
                                    <button type="button" @click="discountAmount = 0"
                                            class="px-1.5 py-0.5 rounded bg-slate-200 hover:bg-slate-300 text-slate-600">
                                        Reset
                                    </button>
                                </div>
                            </div>
                            <input type="number" step="0.01" min="0" id="discount_amount" name="discount_amount" x-model="discountAmount"
                                   class="w-full px-3 py-2 text-sm rounded-xl border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                            <span class="text-[10px] text-slate-400 mt-1 block">Deducted from subtotal amount. Profit updates live.</span>
                        </div>
                    </div>

                    <!-- Financial Summary Bar -->
                    <div class="mt-4 p-5 rounded-2xl bg-slate-900 text-white space-y-3">
                        <div class="flex justify-between text-sm text-slate-300">
                            <span>Total Units:</span>
                            <span class="font-semibold text-white" x-text="totalQuantity + ' units'"></span>
                        </div>

                        <div class="flex justify-between text-sm text-slate-400">
                            <span>Subtotal:</span>
                            <span class="font-medium text-slate-200" x-text="'₹' + subtotalAmount.toFixed(2)"></span>
                        </div>

                        <div class="flex justify-between text-sm text-rose-400" x-show="!isPromotion && parseFloat(discountAmount) > 0">
                            <span>Discount:</span>
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
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('checkouts.show', $checkout) }}" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 shadow transition flex items-center gap-2">
                        <i class="fa-solid fa-save"></i>
                        <span>Update Order & Adjust Stock</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
