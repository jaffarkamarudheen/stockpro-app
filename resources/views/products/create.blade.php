@extends('layouts.app')

@section('title', 'Add New Product')
@section('page_heading', 'Add New Product')

@section('content')
<div class="max-w-4xl mx-auto"
     x-data="{
        purchaseRate: {{ (float) old('purchase_rate', 0) }},
        saleRate: {{ (float) old('sale_rate', 0) }},
        otherRate: {{ (float) old('other_rate', 0) }},
        stockQty: {{ (int) old('stock_quantity', 0) }},
        photoPreview: null,
        photoUrlInput: '',
        isCombo: {{ old('is_combo', '0') ? 'true' : 'false' }},
        comboItems: {{ Js::from(old('combo_items', [])) }},
        availableProducts: {{ Js::from($individualProducts->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'product_number' => $p->product_number, 'stock_quantity' => $p->stock_quantity, 'purchase_rate' => (float)$p->purchase_rate, 'sale_rate' => (float)$p->sale_rate])) }},
        addComponent() {
            if (this.availableProducts.length > 0) {
                this.comboItems.push({
                    product_id: this.availableProducts[0].id,
                    quantity: 1
                });
            }
        },
        removeComponent(index) {
            this.comboItems.splice(index, 1);
        },
        calcComponentPurchaseRate() {
            let total = 0;
            this.comboItems.forEach(ci => {
                const p = this.availableProducts.find(prod => prod.id == ci.product_id);
                if (p) {
                    total += (p.purchase_rate * parseInt(ci.quantity || 1));
                }
            });
            this.purchaseRate = total.toFixed(2);
        },
        calcComponentSaleRate() {
            let total = 0;
            this.comboItems.forEach(ci => {
                const p = this.availableProducts.find(prod => prod.id == ci.product_id);
                if (p) {
                    total += (p.sale_rate * parseInt(ci.quantity || 1));
                }
            });
            this.saleRate = total.toFixed(2);
        },
        get unitProfit() {
            return (parseFloat(this.saleRate || 0) - parseFloat(this.purchaseRate || 0) - parseFloat(this.otherRate || 0)).toFixed(2);
        },
        get totalBatchProfit() {
            return (this.unitProfit * parseInt(this.stockQty || 0)).toFixed(2);
        },
        generateProductNumber() {
            const random = Math.floor(1000 + Math.random() * 9000);
            return 'PRD-' + Date.now().toString().slice(-4) + '-' + random;
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
            <h3 class="text-xl font-bold text-slate-800">Product Information & Rates</h3>
            <p class="text-sm text-slate-500">Add product details, stock count, rates, and upload photos.</p>
        </div>
        <a href="{{ route('products.index') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> Back to Products
        </a>
    </div>

    <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Photo Upload & Profit Preview Widget -->
            <div class="space-y-6">
                <!-- Photo Upload Box -->
                <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Product Photo</label>
                    <div class="flex justify-center px-4 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl relative hover:border-indigo-400 transition bg-slate-50">
                        <template x-if="photoPreview">
                            <div class="relative w-full aspect-square rounded-lg overflow-hidden border border-slate-200">
                                <img :src="photoPreview" class="w-full h-full object-cover">
                                <button type="button" @click="photoPreview = null; photoUrlInput = ''; if ($refs.photoInput) $refs.photoInput.value = ''"
                                        class="absolute top-2 right-2 p-1.5 rounded-full bg-slate-900/70 text-white hover:bg-slate-900">
                                    <i class="fa-solid fa-xmark text-xs"></i>
                                </button>
                            </div>
                        </template>

                        <div x-show="!photoPreview" class="space-y-2 text-center py-4">
                            <i class="fa-solid fa-cloud-arrow-up text-3xl text-indigo-500"></i>
                            <div class="flex text-xs text-slate-600 justify-center">
                                <label for="photo" class="relative cursor-pointer rounded font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none">
                                    <span>Upload a photo</span>
                                    <input id="photo" name="photo" type="file" accept="image/*" class="sr-only" x-ref="photoInput" @change="fileChosen">
                                </label>
                            </div>
                            <p class="text-[11px] text-slate-400">PNG, JPG, WEBP (Saved directly to DB)</p>
                        </div>
                    </div>

                    <!-- OR Image URL Option -->
                    <div class="pt-2 border-t border-slate-100">
                        <label for="photo_url_input" class="block text-[11px] font-semibold text-slate-500 mb-1">Or Paste Direct Image URL</label>
                        <input type="url" id="photo_url_input" name="photo_url_input" x-model="photoUrlInput"
                               @input="if (photoUrlInput.trim().startsWith('http')) photoPreview = photoUrlInput.trim()"
                               placeholder="https://example.com/image.jpg"
                               class="w-full px-3 py-1.5 text-xs rounded-lg border border-slate-300 focus:ring-1 focus:ring-indigo-500 outline-none">
                        <span class="text-[10px] text-slate-400 mt-0.5 block">Web URLs never expire on deployment</span>
                    </div>
                </div>

                <!-- Live Profit Calculation Card -->
                <div class="bg-gradient-to-br from-slate-900 to-indigo-950 p-5 rounded-xl text-white shadow-lg space-y-4">
                    <div class="flex items-center justify-between border-b border-indigo-900/60 pb-3">
                        <span class="text-xs font-semibold uppercase tracking-wider text-indigo-300">Automated Profit Calculator</span>
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
                        <div class="text-[11px] uppercase font-semibold text-emerald-400 tracking-wider">Calculated Profit Per Unit</div>
                        <div class="text-2xl font-black mt-1" :class="unitProfit >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                             x-text="(unitProfit >= 0 ? '+₹' : '-₹') + Math.abs(unitProfit)">
                            +₹0.00
                        </div>
                    </div>

                    <div class="bg-indigo-900/40 p-2.5 rounded-lg text-xs flex justify-between items-center">
                        <span class="text-slate-300">Batch Potential:</span>
                        <span class="font-bold text-emerald-300" x-text="'₹' + totalBatchProfit"></span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Product Fields -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
                    <!-- Product Name & Number -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Product Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                   placeholder="e.g. Wireless Noise Canceling Headphones"
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label for="product_number" class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                                    Product Code / Barcode <span class="text-rose-500">*</span>
                                </label>
                                <button type="button" @click="$refs.prodNumber.value = generateProductNumber()" class="text-[11px] text-indigo-600 hover:underline">
                                    Auto Generate
                                </button>
                            </div>
                            <input type="text" id="product_number" name="product_number" x-ref="prodNumber" value="{{ old('product_number', 'PRD-' . date('ymd') . '-' . rand(100, 999)) }}" required
                                   class="w-full font-mono px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Quality & Retail Price -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="quality" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Quality Grade / Type
                            </label>
                            <input type="text" id="quality" name="quality" value="{{ old('quality', 'Grade A') }}"
                                   placeholder="e.g. Grade A, Premium, Standard, OEM"
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>

                        <div>
                            <label for="price" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Retail / Display Price (₹) <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', 0) }}" required
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Financial Rates: Purchase, Sale, Other -->
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Financial Rates</div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="purchase_rate" class="block text-[11px] font-semibold text-slate-500 mb-1">
                                    Purchase Rate (Cost) <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" min="0" id="purchase_rate" name="purchase_rate"
                                       x-model="purchaseRate" value="{{ old('purchase_rate', 0) }}" required
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div>
                                <label for="sale_rate" class="block text-[11px] font-semibold text-slate-500 mb-1">
                                    Sale Rate <span class="text-rose-500">*</span>
                                </label>
                                <input type="number" step="0.01" min="0" id="sale_rate" name="sale_rate"
                                       x-model="saleRate" value="{{ old('sale_rate', 0) }}" required
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>

                            <div>
                                <label for="other_rate" class="block text-[11px] font-semibold text-slate-500 mb-1">
                                    Other Expenses / Overhead
                                </label>
                                <input type="number" step="0.01" min="0" id="other_rate" name="other_rate"
                                       x-model="otherRate" value="{{ old('other_rate', 0) }}"
                                       class="w-full px-3 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Stock Quantities -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="stock_quantity" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Initial Stock Count
                            </label>
                            <input type="number" min="0" id="stock_quantity" name="stock_quantity"
                                   x-model="stockQty" value="{{ old('stock_quantity', 0) }}"
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>

                        <div>
                            <label for="low_stock_threshold" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                                Low Stock Alert Threshold
                            </label>
                            <input type="number" min="1" id="low_stock_threshold" name="low_stock_threshold"
                                   value="{{ old('low_stock_threshold', 5) }}"
                                   class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Combo / Bundle Product Option Card -->
                    <div class="p-4 rounded-xl border border-indigo-200 bg-indigo-50/50 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="relative inline-flex items-center cursor-pointer gap-3">
                                <input type="checkbox" name="is_combo" value="1" x-model="isCombo" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                <div>
                                    <span class="text-sm font-bold text-slate-800">Combo / Bundle Product</span>
                                    <span class="block text-xs text-slate-500">Sell multiple individual items bundled together (e.g. Earring + Locket set). Stock deducts from both!</span>
                                </div>
                            </label>
                            <span x-show="isCombo" class="px-2.5 py-0.5 text-xs font-bold uppercase rounded-full bg-indigo-100 text-indigo-700">
                                Combo Active
                            </span>
                        </div>

                        <!-- Interactive Component List -->
                        <div x-show="isCombo" x-cloak class="pt-3 border-t border-indigo-200/60 space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Constituent Items</h4>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click="calcComponentPurchaseRate()" class="px-2 py-1 text-[11px] font-semibold text-indigo-700 bg-white border border-indigo-300 rounded hover:bg-indigo-50 transition" title="Sum constituent purchase rates">
                                        Auto Purchase Cost
                                    </button>
                                    <button type="button" @click="calcComponentSaleRate()" class="px-2 py-1 text-[11px] font-semibold text-indigo-700 bg-white border border-indigo-300 rounded hover:bg-indigo-50 transition" title="Sum constituent sale rates">
                                        Auto Sale Rate
                                    </button>
                                    <button type="button" @click="addComponent()" class="px-2.5 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700 transition flex items-center gap-1">
                                        <i class="fa-solid fa-plus text-[10px]"></i> Add Component
                                    </button>
                                </div>
                            </div>

                            <template x-if="comboItems.length === 0">
                                <div class="p-4 text-center border-2 border-dashed border-indigo-200 rounded-lg text-xs text-slate-500 bg-white">
                                    No items added yet. Click <strong>Add Component</strong> to select constituent products for this combo.
                                </div>
                            </template>

                            <div class="space-y-2">
                                <template x-for="(item, index) in comboItems" :key="index">
                                    <div class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-lg shadow-xs">
                                        <div class="flex-1">
                                            <label class="block text-[10px] font-semibold uppercase text-slate-400 mb-1">Component Product</label>
                                            <select :name="'combo_items[' + index + '][product_id]'" x-model="item.product_id"
                                                    class="w-full text-xs px-2.5 py-1.5 rounded border border-slate-300 focus:ring-1 focus:ring-indigo-500 outline-none">
                                                <template x-for="prod in availableProducts" :key="prod.id">
                                                    <option :value="prod.id" x-text="prod.name + ' (' + prod.product_number + ') - Stock: ' + prod.stock_quantity" :selected="prod.id == item.product_id"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <div class="w-24">
                                            <label class="block text-[10px] font-semibold uppercase text-slate-400 mb-1">Qty in Set</label>
                                            <input type="number" min="1" :name="'combo_items[' + index + '][quantity]'" x-model="item.quantity"
                                                   class="w-full text-xs px-2.5 py-1.5 rounded border border-slate-300 focus:ring-1 focus:ring-indigo-500 outline-none">
                                        </div>

                                        <div class="pt-5">
                                            <button type="button" @click="removeComponent(index)" class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded transition" title="Remove component">
                                                <i class="fa-solid fa-trash-can text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-xs font-semibold uppercase tracking-wider text-slate-600 mb-1.5">
                            Product Description / Specifications
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  placeholder="Enter optional description, specifications, color, model details..."
                                  class="w-full px-3.5 py-2 text-sm rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('products.index') }}" class="px-5 py-2.5 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 shadow transition flex items-center gap-2">
                        <i class="fa-solid fa-save"></i> Save Product
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
