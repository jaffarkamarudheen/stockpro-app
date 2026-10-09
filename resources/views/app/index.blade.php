<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>StockPro Mobile - Visual Stock & Checkout</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="h-full text-slate-100 flex flex-col font-sans select-none overflow-x-hidden"
      x-data="mobileApp()" x-init="initApp()">

    <!-- Top Mobile App Bar -->
    <header class="h-14 bg-slate-950 border-b border-slate-800 px-4 flex items-center justify-between sticky top-0 z-40">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <h1 class="text-sm font-bold text-white tracking-wide">StockPro Mobile</h1>
                <p class="text-[10px] text-emerald-400 font-medium">Visual Photo & Name Lookup (No AI)</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if (auth()->user()?->canAccessAdmin())
                <a href="{{ route('admin.dashboard') }}" class="px-2.5 py-1 text-xs rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-gauge text-xs"></i>
                    <span>Admin</span>
                </a>
            @endif
            @auth
                <div class="flex items-center gap-1.5 pl-1">
                    <span class="text-xs text-emerald-400 font-semibold flex items-center gap-1 bg-slate-800/90 px-2.5 py-1 rounded-lg border border-slate-700">
                        <i class="fa-solid fa-user-check text-[10px]"></i>
                        <span>{{ auth()->user()->name }}</span>
                    </span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="p-1.5 text-xs text-rose-400 hover:text-rose-300 hover:bg-rose-500/10 rounded-lg transition" title="Logout">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1 overflow-y-auto p-4 space-y-4 max-w-lg mx-auto w-full pb-24">

        <!-- Notification Toast Message -->
        <template x-if="toastMessage">
            <div class="p-3.5 rounded-xl shadow-lg flex items-center justify-between transition-all"
                 :class="toastType === 'success' ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white'">
                <div class="flex items-center gap-2 text-xs font-semibold">
                    <i :class="toastType === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-triangle-exclamation'"></i>
                    <span x-text="toastMessage"></span>
                </div>
                <button type="button" @click="toastMessage = ''" class="text-white/80 hover:text-white p-1">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        </template>

        <!-- Search by Name or Number & Camera Snapshot Bar -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-magnifying-glass text-indigo-400"></i>
                    <span>Find Product by Name or Number</span>
                </span>
                <span class="text-[11px] text-slate-500" x-text="products.length + ' Products Loaded'"></span>
            </div>

            <!-- Search Input Box -->
            <div class="relative">
                <input type="text" x-model="searchQuery" @input="filterProducts()"
                       placeholder="Enter Product Name (e.g. Headphones) or Code..."
                       class="w-full bg-slate-900 border border-slate-700 rounded-xl pl-10 pr-10 py-3 text-sm text-white placeholder-slate-500 focus:ring-2 focus:ring-emerald-500 outline-none">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <i class="fa-solid fa-search"></i>
                </span>
                <button type="button" x-show="searchQuery" @click="searchQuery = ''; filterProducts()"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Instant Search Suggestions Dropdown -->
            <div x-show="filteredProducts.length > 0 && searchQuery"
                 class="bg-slate-900 border border-slate-700 rounded-xl shadow-2xl max-h-56 overflow-y-auto divide-y divide-slate-800 z-30">
                <template x-for="p in filteredProducts" :key="p.id">
                    <div @click="selectProduct(p)"
                         class="p-3 hover:bg-slate-800 cursor-pointer flex items-center justify-between text-xs transition">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-slate-800 flex-shrink-0 flex items-center justify-center border border-slate-700">
                                <template x-if="p.photo_url">
                                    <img :src="p.photo_url" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!p.photo_url">
                                    <i class="fa-regular fa-image text-slate-500"></i>
                                </template>
                            </div>
                            <div>
                                <span class="font-bold text-white text-sm block" x-text="p.name"></span>
                                <span class="font-mono text-[11px] text-indigo-400 font-semibold" x-text="p.product_number"></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="font-bold text-emerald-400 block text-sm" x-text="'₹' + parseFloat(p.sale_rate).toFixed(2)"></span>
                            <span class="text-[11px] px-2 py-0.5 rounded font-semibold"
                                  :class="p.stock_quantity > 0 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'"
                                  x-text="p.stock_quantity + ' in stock'"></span>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Camera Snapshot Feature (No 3rd-party AI needed, uses device camera directly) -->
            <div class="pt-1 flex items-center gap-2">
                <label class="flex-1 py-2.5 px-3 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs text-slate-300 font-semibold flex items-center justify-center gap-2 cursor-pointer transition">
                    <i class="fa-solid fa-camera text-emerald-400 text-sm"></i>
                    <span>Take Photo of Product</span>
                    <input type="file" accept="image/*" capture="environment" class="sr-only" @change="captureDevicePhoto($event)">
                </label>

                <button type="button" @click="toggleLiveCamera()"
                        class="px-3.5 py-2.5 rounded-xl border text-xs font-semibold flex items-center gap-1.5 transition"
                        :class="liveCameraActive ? 'bg-rose-600 text-white border-rose-500' : 'bg-slate-900 border-slate-700 text-slate-300 hover:bg-slate-800'">
                    <i :class="liveCameraActive ? 'fa-solid fa-video-slash' : 'fa-solid fa-video'"></i>
                    <span x-text="liveCameraActive ? 'Close' : 'Live Camera'"></span>
                </button>
            </div>

            <!-- Live Camera Viewfinder Box -->
            <div x-show="liveCameraActive" class="rounded-xl overflow-hidden border border-slate-700 bg-black relative min-h-[220px]">
                <video id="liveVideo" autoplay playsinline class="w-full h-56 object-cover"></video>
                <div class="absolute bottom-2 inset-x-0 flex justify-center gap-2">
                    <button type="button" @click="snapFromLiveCamera()" class="px-4 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-bold shadow flex items-center gap-1.5">
                        <i class="fa-solid fa-camera"></i> Capture This
                    </button>
                    <button type="button" @click="toggleLiveCamera()" class="px-3 py-1.5 rounded-lg bg-slate-800/80 text-white text-xs font-semibold">
                        Cancel
                    </button>
                </div>
            </div>

            <!-- Captured Photo Display (if snapped) -->
            <template x-if="capturedPhoto">
                <div class="p-3 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img :src="capturedPhoto" class="w-12 h-12 rounded-lg object-cover border border-slate-700">
                        <div>
                            <span class="text-xs font-bold text-white block">Captured Photo</span>
                            <span class="text-[10px] text-slate-400">Match this against products below</span>
                        </div>
                    </div>
                    <button type="button" @click="capturedPhoto = null" class="text-rose-400 hover:text-rose-300 text-xs p-1">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
            </template>
        </div>

        <!-- Visual Product Photo Gallery / Quick Selector (Tap any photo to load!) -->
        <div class="bg-slate-950 p-4 rounded-2xl border border-slate-800 shadow-xl space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                    <i class="fa-solid fa-images text-emerald-400"></i>
                    <span>Product Photos (Tap to Select)</span>
                </span>
                <span class="text-[11px] text-slate-500">Visual Selector</span>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5 max-h-56 overflow-y-auto no-scrollbar pr-1">
                <template x-for="p in products" :key="p.id">
                    <div @click="selectProduct(p)"
                         class="cursor-pointer p-2 rounded-xl border text-center transition flex flex-col items-center justify-between"
                         :class="activeProduct?.id === p.id ? 'bg-indigo-950/80 border-indigo-500 shadow-md ring-2 ring-indigo-500' : 'bg-slate-900 border-slate-800 hover:border-slate-700'">
                        <div class="w-16 h-16 rounded-lg overflow-hidden bg-slate-800 flex items-center justify-center border border-slate-700/60 mb-1">
                            <template x-if="p.photo_url">
                                <img :src="p.photo_url" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!p.photo_url">
                                <i class="fa-regular fa-image text-slate-500 text-xl"></i>
                            </template>
                        </div>
                        <span class="text-[11px] font-bold text-white line-clamp-1 w-full text-center" x-text="p.name"></span>
                        <span class="font-mono text-[9px] text-indigo-400 font-semibold" x-text="p.product_number"></span>
                        <span class="text-[10px] font-bold text-emerald-400 mt-0.5" x-text="'₹' + parseFloat(p.sale_rate).toFixed(0)"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Selected Product Card & Big Action Buttons -->
        <template x-if="activeProduct">
            <div class="bg-slate-950 rounded-2xl border border-slate-800 shadow-2xl p-5 space-y-4 animate-fadeIn">
                <!-- Top Header: Photo & Name -->
                <div class="flex items-start gap-4">
                    <div class="w-24 h-24 rounded-xl overflow-hidden bg-slate-900 border border-slate-700 flex-shrink-0 flex items-center justify-center shadow">
                        <template x-if="activeProduct.photo_url">
                            <img :src="activeProduct.photo_url" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!activeProduct.photo_url">
                            <i class="fa-regular fa-image text-3xl text-slate-600"></i>
                        </template>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 font-bold"
                                  x-text="activeProduct.product_number"></span>
                            <span class="text-[11px] px-2 py-0.5 rounded font-semibold bg-slate-800 text-slate-300"
                                  x-text="activeProduct.quality || 'Standard'"></span>
                            <template x-if="activeProduct.user_name">
                                <span class="text-[10px] px-2 py-0.5 rounded font-medium bg-slate-800 text-slate-300 flex items-center gap-1 border border-slate-700">
                                    <i class="fa-solid fa-user-tag text-[9px] text-indigo-400"></i>
                                    <span x-text="'By: ' + activeProduct.user_name"></span>
                                </span>
                            </template>
                        </div>
                        <h3 class="text-base font-bold text-white mt-1.5 leading-snug" x-text="activeProduct.name"></h3>
                        <p class="text-xs text-slate-400 mt-1 line-clamp-2" x-text="activeProduct.description || 'No description provided.'"></p>
                    </div>
                </div>

                <!-- Rates & Profit in Indian Rupees (₹) -->
                <div class="grid grid-cols-3 gap-2 p-3 rounded-xl bg-slate-900 border border-slate-800 text-center">
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Sale Rate</span>
                        <span class="text-sm font-bold text-white" x-text="'₹' + parseFloat(activeProduct.sale_rate).toFixed(2)"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">Unit Profit</span>
                        <span class="text-sm font-bold text-emerald-400" x-text="'+₹' + parseFloat(activeProduct.profit_per_unit).toFixed(2)"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-400 uppercase tracking-wider block">In Stock</span>
                        <span class="text-sm font-extrabold"
                              :class="activeProduct.stock_quantity > 0 ? 'text-emerald-400' : 'text-rose-400'"
                              x-text="activeProduct.stock_quantity + ' units'"></span>
                    </div>
                </div>

                <!-- Rates Breakdown Detail -->
                <div class="px-3 py-2 rounded-lg bg-slate-900/60 border border-slate-800/80 text-[11px] flex justify-between text-slate-400">
                    <span>Cost: ₹<strong class="text-slate-300" x-text="parseFloat(activeProduct.purchase_rate).toFixed(2)"></strong></span>
                    <span>Other: ₹<strong class="text-slate-300" x-text="parseFloat(activeProduct.other_rate || 0).toFixed(2)"></strong></span>
                    <span>Retail MRP: ₹<strong class="text-slate-300" x-text="parseFloat(activeProduct.price).toFixed(2)"></strong></span>
                </div>

                <!-- ACTION BUTTONS: CHECK IN & MULTI-PRODUCT CART / CHECK OUT -->
                <div class="grid grid-cols-3 gap-2 pt-1">
                    <!-- Check-In (Stock Add) -->
                    <button type="button" @click="openCheckInModal()"
                            class="py-3 px-2 rounded-xl font-bold text-[11px] uppercase tracking-wider bg-emerald-600 hover:bg-emerald-500 text-white shadow-lg shadow-emerald-900/40 flex items-center justify-center gap-1.5 transition active:scale-95">
                        <i class="fa-solid fa-arrow-down-to-bracket text-xs"></i>
                        <span>Check In</span>
                    </button>

                    <!-- Add To Cart -->
                    <button type="button" @click="addToCart(activeProduct)"
                            :disabled="activeProduct.stock_quantity <= 0"
                            class="py-3 px-2 rounded-xl font-bold text-[11px] uppercase tracking-wider bg-slate-800 hover:bg-slate-700 disabled:opacity-40 disabled:pointer-events-none text-slate-200 border border-slate-700 flex items-center justify-center gap-1.5 transition active:scale-95">
                        <i class="fa-solid fa-cart-plus text-xs text-indigo-400"></i>
                        <span>+ Add</span>
                    </button>

                    <!-- Check-Out Modal -->
                    <button type="button" @click="openCheckOutModal(activeProduct)"
                            :disabled="activeProduct.stock_quantity <= 0 && cart.length === 0"
                            class="py-3 px-2 rounded-xl font-bold text-[11px] uppercase tracking-wider bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:pointer-events-none text-white shadow-lg shadow-indigo-900/40 flex items-center justify-center gap-1.5 transition active:scale-95">
                        <i class="fa-solid fa-cash-register text-xs"></i>
                        <span>Check Out</span>
                    </button>
                </div>
            </div>
        </template>
    </main>

    <!-- Sticky Bottom Multi-Item Cart Bar -->
    <div x-show="cart.length > 0" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-full opacity-0"
         class="fixed bottom-0 inset-x-0 z-40 bg-slate-950/95 backdrop-blur-md border-t border-slate-800 p-3 shadow-2xl">
        <div class="max-w-lg mx-auto flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
                <div>
                    <div class="text-xs font-bold text-white flex items-center gap-1.5">
                        <span x-text="totalCartCount + ' items in cart'"></span>
                        <span class="text-emerald-400" x-text="'(₹' + totalCartAmount + ')'"></span>
                    </div>
                    <span class="text-[10px] text-slate-400" x-text="cart.length + ' unique products'"></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="cart = []" class="text-xs text-rose-400 hover:text-rose-300 px-2 py-1">
                    Clear
                </button>
                <button type="button" @click="openCheckOutModal()"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 text-white flex items-center gap-1.5 shadow-lg shadow-indigo-600/30">
                    <span>Checkout Now</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal 1: Check-In Stock Modal -->
    <div x-show="showCheckInModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-xs" @click="showCheckInModal = false"></div>

        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-sm p-5 space-y-4 shadow-2xl"
                 @click.away="showCheckInModal = false">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
                        <i class="fa-solid fa-plus-circle"></i>
                        <span>Stock Check-In</span>
                    </div>
                    <button type="button" @click="showCheckInModal = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <span class="text-xs text-slate-400">Product:</span>
                        <div class="font-bold text-white text-sm" x-text="activeProduct?.name"></div>
                        <div class="text-[11px] text-slate-500" x-text="'Current stock: ' + activeProduct?.stock_quantity"></div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Quantity to Add</label>
                        <input type="number" min="1" x-model.number="checkInQty"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white font-bold text-lg outline-none focus:border-emerald-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Supplier / Batch Reference</label>
                        <input type="text" x-model="checkInNotes" placeholder="e.g. Batch #2026, New Delivery"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCheckInModal = false" class="flex-1 py-2.5 rounded-xl text-xs font-semibold bg-slate-800 text-slate-300">
                        Cancel
                    </button>
                    <button type="button" @click="submitCheckIn()" :disabled="loading"
                            class="flex-1 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center gap-1.5 shadow">
                        <i class="fa-solid fa-check" x-show="!loading"></i>
                        <span x-text="loading ? 'Adding...' : 'Add Stock'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2: Multi-Product Customer Check-Out Modal -->
    <div x-show="showCheckOutModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-xs" @click="showCheckOutModal = false"></div>

        <div class="fixed inset-0 flex items-center justify-center p-3 sm:p-4 overflow-y-auto">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-lg p-5 space-y-4 shadow-2xl my-auto"
                 @click.away="showCheckOutModal = false">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center gap-2 text-indigo-400 font-bold text-sm">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>Customer Check-Out (Multi-Product)</span>
                    </div>
                    <button type="button" @click="showCheckOutModal = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <!-- Products in Checkout List -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-300 uppercase tracking-wider text-[11px]">Products to Purchase (<span x-text="totalCartCount"></span> units)</span>
                        <span class="text-emerald-400 font-bold text-[11px]" x-text="'Subtotal: ₹' + totalCartAmount"></span>
                    </div>

                    <div class="max-h-48 overflow-y-auto space-y-2 pr-1 no-scrollbar">
                        <template x-for="(item, idx) in cart" :key="item.product.id">
                            <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800 space-y-2">
                                <div class="flex items-center gap-2.5 justify-between">
                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                        <div class="w-10 h-10 rounded-lg overflow-hidden bg-slate-800 flex-shrink-0 flex items-center justify-center border border-slate-700">
                                            <template x-if="item.product.photo_url">
                                                <img :src="item.product.photo_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!item.product.photo_url">
                                                <i class="fa-regular fa-image text-slate-500 text-xs"></i>
                                            </template>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-white truncate" x-text="item.product.name"></p>
                                            <div class="text-[10px] text-slate-400 flex items-center gap-2">
                                                <span class="font-mono text-indigo-400" x-text="item.product.product_number"></span>
                                                <span>₹<span x-text="parseFloat(item.unit_sale_rate).toFixed(0)"></span> / unit</span>
                                                <span class="text-slate-500">(Max: <span x-text="item.product.stock_quantity"></span>)</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quantity Controls -->
                                    <div class="flex items-center gap-1.5 flex-shrink-0">
                                        <button type="button" @click="updateCartQty(idx, -1)"
                                                class="w-6 h-6 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs flex items-center justify-center">
                                            -
                                        </button>
                                        <span class="w-6 text-center text-xs font-bold text-white" x-text="item.quantity"></span>
                                        <button type="button" @click="updateCartQty(idx, 1)"
                                                class="w-6 h-6 rounded-md bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs flex items-center justify-center">
                                            +
                                        </button>
                                        <span class="text-xs font-bold text-emerald-400 w-16 text-right"
                                              x-text="'₹' + (item.unit_sale_rate * item.quantity).toFixed(0)"></span>
                                        <button type="button" @click="removeFromCart(idx)"
                                                class="p-1 text-rose-400 hover:text-rose-300 text-xs">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Overhead Toggles (Delivery ₹50 & Box ₹40) -->
                                <div class="pt-1.5 border-t border-slate-900 flex items-center justify-between text-[10px]">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-slate-500 font-semibold uppercase text-[9px]">Overhead:</span>
                                        <button type="button" @click="toggleCartDelivery(idx)"
                                                :class="item.has_delivery ? 'bg-indigo-950 text-indigo-300 border-indigo-700 font-bold' : 'bg-slate-900 text-slate-500 border-slate-800 line-through'"
                                                class="px-1.5 py-0.5 rounded border flex items-center gap-1 transition">
                                            <i class="fa-solid fa-truck-fast text-[9px]"></i>
                                            <span>Deliv (₹50)</span>
                                        </button>
                                        <button type="button" @click="toggleCartPackaging(idx)"
                                                :class="item.has_packaging ? 'bg-amber-950 text-amber-300 border-amber-700 font-bold' : 'bg-slate-900 text-slate-500 border-slate-800 line-through'"
                                                class="px-1.5 py-0.5 rounded border flex items-center gap-1 transition">
                                            <i class="fa-solid fa-box-open text-[9px]"></i>
                                            <span>Box (₹40)</span>
                                        </button>
                                    </div>
                                    <div class="text-slate-400 text-[10px]">
                                        Exp: <span class="font-bold text-white">₹<span x-text="item.unit_other_rate"></span></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="cart.length === 0" class="text-center py-4 text-xs text-slate-500">
                            No products in cart yet. Select a product below to add it.
                        </div>
                    </div>

                    <!-- Add More Products Dropdown -->
                    <div class="pt-1">
                        <div class="flex items-center gap-2">
                            <select x-model="selectedProductToAdd"
                                    class="flex-1 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
                                <option value="">+ Add another product from catalog...</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}" {{ $p->stock_quantity <= 0 ? 'disabled' : '' }}>
                                        {{ $p->name }} ({{ $p->product_number }}) - ₹{{ number_format($p->sale_rate, 0) }} [{{ $p->stock_quantity }} stock]
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" @click="addProductById(selectedProductToAdd)"
                                    class="px-3 py-2 text-xs font-semibold rounded-xl bg-slate-800 hover:bg-slate-700 text-white transition">
                                Add
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Customer Details Form -->
                <div class="space-y-3 pt-2 border-t border-slate-800">
                    <div class="text-[11px] font-semibold text-slate-300 uppercase tracking-wider">Buyer Information</div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <!-- Purchaser Name -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Customer / Buyer Name <span class="text-rose-400">*</span></label>
                            <input type="text" x-model="customerName" placeholder="Full name of purchaser"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-300 mb-1">Phone / WhatsApp</label>
                            <input type="text" x-model="customerPhone" placeholder="Mobile / WhatsApp number"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Purchaser Address -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Customer Address <span class="text-rose-400">*</span></label>
                        <textarea rows="2" x-model="customerAddress" placeholder="Full delivery address, street, city..."
                                  class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500"></textarea>
                    </div>

                    <!-- Enquiry From -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Enquiry From <span class="text-rose-400">*</span></label>
                        <select x-model="enquiryFrom"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
                            @foreach ($enquirySources as $source)
                                <option value="{{ $source }}">{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Expected Delivery Date -->
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-300 mb-1">Expected Delivery Date (Optional)</label>
                        <input type="date" x-model="expectedDeliveryDate"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
                    </div>

                    <!-- Promotion & Discount Options -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                        <div class="p-2.5 rounded-xl border border-pink-900/60 bg-pink-950/30 flex items-start gap-2">
                            <input type="checkbox" id="pos_is_promo" x-model="isPromotion"
                                   class="mt-0.5 rounded border-pink-700 text-pink-600 focus:ring-pink-500 cursor-pointer">
                            <label for="pos_is_promo" class="text-[11px] text-pink-300 cursor-pointer">
                                <span class="font-bold block flex items-center gap-1">
                                    <i class="fa-solid fa-gift text-pink-400"></i> Promotional (Free / ₹0)
                                </span>
                                <span class="text-[10px] text-pink-400/80">Stock only, no fee charged</span>
                            </label>
                        </div>

                        <div x-show="!isPromotion">
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-[11px] font-semibold text-slate-300">Discount (₹)</label>
                                <div class="flex items-center gap-1 text-[10px]">
                                    <button type="button" @click="applyPresetDiscount(50)"
                                            class="px-1.5 py-0.5 rounded bg-indigo-900/60 hover:bg-indigo-900 text-indigo-300 font-semibold" title="Pass delivery discount to customer">
                                        +₹50 (Deliv)
                                    </button>
                                    <button type="button" @click="applyPresetDiscount(40)"
                                            class="px-1.5 py-0.5 rounded bg-amber-900/60 hover:bg-amber-900 text-amber-300 font-semibold" title="Pass box discount to customer">
                                        +₹40 (Box)
                                    </button>
                                    <button type="button" @click="discountAmount = 0"
                                            class="px-1.5 py-0.5 rounded bg-slate-800 hover:bg-slate-700 text-slate-400">
                                        Reset
                                    </button>
                                </div>
                            </div>
                            <input type="number" step="0.01" min="0" x-model="discountAmount" placeholder="0.00"
                                   class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white outline-none focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Order Financial Breakdown in INR (₹) -->
                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs space-y-1">
                        <div class="flex justify-between text-slate-400">
                            <span>Total Units:</span>
                            <span class="font-bold text-white" x-text="totalCartCount + ' items'"></span>
                        </div>
                        <div class="flex justify-between text-slate-300 font-semibold">
                            <span>Total Checkout Amount:</span>
                            <span class="font-bold text-white text-sm"
                                  :class="isPromotion ? 'text-pink-400' : 'text-white'"
                                  x-text="isPromotion ? 'FREE (₹0.00)' : '₹' + totalCartAmount"></span>
                        </div>
                        <div class="flex justify-between text-emerald-400 font-bold border-t border-slate-800/80 pt-1 mt-1" x-show="!isPromotion">
                            <span>Calculated Net Profit:</span>
                            <span x-text="'+₹' + totalCartProfit"></span>
                        </div>
                        <div class="text-center text-[10px] text-pink-400 font-semibold border-t border-slate-800/80 pt-1 mt-1" x-show="isPromotion">
                            <span>🎁 Promotion - Not calculated in sales reports</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showCheckOutModal = false" class="flex-1 py-2.5 rounded-xl text-xs font-semibold bg-slate-800 text-slate-300">
                        Cancel
                    </button>
                    <button type="button" @click="submitCheckOut()" :disabled="loading || cart.length === 0"
                             class="flex-1 py-2.5 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:pointer-events-none text-white flex items-center justify-center gap-1.5 shadow">
                        <i class="fa-solid fa-check" x-show="!loading"></i>
                        <span x-text="loading ? 'Processing...' : 'Complete Checkout (' + totalCartCount + ' items)'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 3: Order Completed & Download PDF Receipt Modal -->
    <div x-show="showSuccessReceiptModal" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/80 backdrop-blur-xs" @click="showSuccessReceiptModal = false"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-slate-900 border border-pink-500/30 rounded-3xl w-full max-w-sm p-6 text-center space-y-4 shadow-2xl animate-fadeIn">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl mx-auto border border-emerald-500/30">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white">Order Processed!</h3>
                    <p class="font-mono text-sm text-pink-400 font-bold mt-1" x-text="completedOrder?.order_number"></p>
                    <p class="text-xs text-slate-400 mt-1">Stock deducted successfully.</p>
                </div>
                <div class="pt-2 space-y-2">
                    <a :href="'/receipt/' + (completedOrder ? completedOrder.id : '')" target="_blank"
                       class="w-full py-3 px-4 rounded-xl text-xs font-bold text-white bg-pink-600 hover:bg-pink-500 flex items-center justify-center gap-2 shadow-lg transition">
                        <i class="fa-solid fa-file-pdf text-sm"></i>
                        <span>View & Download Client PDF Receipt</span>
                    </a>
                    <button type="button" @click="showSuccessReceiptModal = false"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 transition">
                        Done / Continue
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Application JavaScript (100% Native, No 3rd Party AI) -->
    <script>
        function mobileApp() {
            return {
                products: @json($products),
                filteredProducts: [],
                searchQuery: '',
                activeProduct: null,
                capturedPhoto: null,
                liveCameraActive: false,
                mediaStream: null,
                toastMessage: '',
                toastType: 'success',
                loading: false,

                // Multi-product checkout cart
                cart: [],
                selectedProductToAdd: '',

                // Check-in modal
                showCheckInModal: false,
                checkInQty: 10,
                checkInNotes: '',

                // Checkout modal
                showCheckOutModal: false,
                customerName: '',
                customerAddress: '',
                customerPhone: '',
                enquiryFrom: 'WhatsApp',
                isPromotion: false,
                discountAmount: 0,
                expectedDeliveryDate: '',
                showSuccessReceiptModal: false,
                completedOrder: null,

                initApp() {
                    if (this.products.length > 0) {
                        this.activeProduct = this.products[0];
                    }
                },

                get totalCartCount() {
                    return this.cart.reduce((sum, item) => sum + (parseInt(item.quantity) || 0), 0);
                },

                get totalCartAmount() {
                    if (this.isPromotion) return '0.00';
                    const total = this.cart.reduce((sum, item) => {
                        return sum + ((parseFloat(item.unit_sale_rate) || 0) * (parseInt(item.quantity) || 0));
                    }, 0);
                    const disc = parseFloat(this.discountAmount) || 0;
                    return Math.max(0, total - disc).toFixed(2);
                },

                get totalCartProfit() {
                    if (this.isPromotion) return '0.00';
                    const profit = this.cart.reduce((sum, item) => {
                        const sale = parseFloat(item.unit_sale_rate) || 0;
                        const cost = parseFloat(item.product.purchase_rate) || 0;
                        const other = (item.unit_other_rate !== undefined && item.unit_other_rate !== null && item.unit_other_rate !== '')
                            ? parseFloat(item.unit_other_rate)
                            : (parseFloat(item.product.other_rate) || 0);
                        const unitProfit = sale - cost - other;
                        return sum + (unitProfit * (parseInt(item.quantity) || 0));
                    }, 0);
                    const disc = parseFloat(this.discountAmount) || 0;
                    return (profit - disc).toFixed(2);
                },

                addToCart(p, qty = 1) {
                    if (!p || p.stock_quantity <= 0) {
                        this.showToast('Item is out of stock', 'error');
                        return;
                    }

                    const existing = this.cart.find(item => item.product.id === p.id);
                    if (existing) {
                        if (existing.quantity + qty > p.stock_quantity) {
                            this.showToast('Max available stock is ' + p.stock_quantity, 'error');
                            return;
                        }
                        existing.quantity += qty;
                    } else {
                        const otherRate = parseFloat(p.other_rate) || 90;
                        this.cart.push({
                            product: p,
                            quantity: Math.min(qty, p.stock_quantity),
                            unit_sale_rate: parseFloat(p.sale_rate),
                            unit_other_rate: otherRate,
                            has_delivery: otherRate >= 50,
                            has_packaging: (otherRate % 50 === 40) || (otherRate >= 90) || (otherRate === 40)
                        });
                    }

                    this.showToast('Added ' + p.name + ' to checkout (' + this.totalCartCount + ' units)', 'success');
                },

                toggleCartDelivery(idx) {
                    const item = this.cart[idx];
                    if (!item) return;
                    item.has_delivery = !item.has_delivery;
                    this.recomputeCartOverhead(idx);
                },

                toggleCartPackaging(idx) {
                    const item = this.cart[idx];
                    if (!item) return;
                    item.has_packaging = !item.has_packaging;
                    this.recomputeCartOverhead(idx);
                },

                recomputeCartOverhead(idx) {
                    const item = this.cart[idx];
                    if (!item) return;
                    let rate = 0;
                    if (item.has_delivery) rate += 50;
                    if (item.has_packaging) rate += 40;
                    item.unit_other_rate = rate;
                },

                applyPresetDiscount(amount) {
                    this.discountAmount = (parseFloat(this.discountAmount || 0) + amount).toFixed(2);
                },

                addProductById(productId) {
                    if (!productId) return;
                    const p = this.products.find(item => item.id == productId);
                    if (p) {
                        this.addToCart(p, 1);
                        this.selectedProductToAdd = '';
                    }
                },

                removeFromCart(index) {
                    this.cart.splice(index, 1);
                },

                updateCartQty(index, delta) {
                    const item = this.cart[index];
                    if (!item) return;

                    const newQty = item.quantity + delta;
                    if (newQty <= 0) {
                        this.removeFromCart(index);
                        return;
                    }

                    if (newQty > item.product.stock_quantity) {
                        this.showToast('Max stock reached (' + item.product.stock_quantity + ')', 'error');
                        return;
                    }

                    item.quantity = newQty;
                },

                filterProducts() {
                    const q = this.searchQuery.toLowerCase().trim();
                    if (!q) {
                        this.filteredProducts = [];
                        return;
                    }
                    this.filteredProducts = this.products.filter(p =>
                        p.name.toLowerCase().includes(q) ||
                        p.product_number.toLowerCase().includes(q)
                    ).slice(0, 10);
                },

                selectProduct(p) {
                    this.activeProduct = p;
                    this.searchQuery = '';
                    this.filteredProducts = [];
                    this.showToast('Selected ' + p.name, 'success');
                },

                // Device Camera Snap
                captureDevicePhoto(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.capturedPhoto = e.target.result;
                        this.showToast('Photo captured! Tap matching product from gallery below.', 'success');
                    };
                    reader.readAsDataURL(file);
                },

                // Live Camera Stream
                async toggleLiveCamera() {
                    if (this.liveCameraActive) {
                        if (this.mediaStream) {
                            this.mediaStream.getTracks().forEach(track => track.stop());
                        }
                        this.liveCameraActive = false;
                    } else {
                        try {
                            this.mediaStream = await navigator.mediaDevices.getUserMedia({
                                video: { facingMode: 'environment' }
                            });
                            const video = document.getElementById('liveVideo');
                            video.srcObject = this.mediaStream;
                            this.liveCameraActive = true;
                        } catch (err) {
                            this.showToast('Camera error: ' + err.message, 'error');
                        }
                    }
                },

                snapFromLiveCamera() {
                    const video = document.getElementById('liveVideo');
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 640;
                    canvas.height = video.videoHeight || 480;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.capturedPhoto = canvas.toDataURL('image/jpeg');
                    this.toggleLiveCamera();
                    this.showToast('Photo captured! Tap matching product from gallery below.', 'success');
                },

                openCheckInModal() {
                    if (!this.activeProduct) return;
                    this.checkInQty = 10;
                    this.checkInNotes = '';
                    this.showCheckInModal = true;
                },

                async submitCheckIn() {
                    if (!this.activeProduct || this.checkInQty < 1) return;
                    this.loading = true;

                    try {
                        const res = await fetch('/api/v1/stock/check-in', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({
                                product_id: this.activeProduct.id,
                                quantity: this.checkInQty,
                                notes: this.checkInNotes
                            })
                        });

                        const data = await res.json();
                        if (res.ok) {
                            this.activeProduct.stock_quantity = data.product.stock_quantity;
                            const found = this.products.find(p => p.id === this.activeProduct.id);
                            if (found) found.stock_quantity = data.product.stock_quantity;

                            this.showCheckInModal = false;
                            this.showToast(data.message || 'Stock checked in successfully!', 'success');
                        } else {
                            this.showToast(data.message || 'Check-in failed', 'error');
                        }
                    } catch (e) {
                        this.showToast('Network error on check-in', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                openCheckOutModal(directProduct = null) {
                    if (directProduct && directProduct.stock_quantity > 0) {
                        const exists = this.cart.find(i => i.product.id === directProduct.id);
                        if (!exists) {
                            const otherRate = parseFloat(directProduct.other_rate) || 90;
                            this.cart.push({
                                product: directProduct,
                                quantity: 1,
                                unit_sale_rate: parseFloat(directProduct.sale_rate),
                                unit_other_rate: otherRate,
                                has_delivery: otherRate >= 50,
                                has_packaging: (otherRate % 50 === 40) || (otherRate >= 90) || (otherRate === 40)
                            });
                        }
                    } else if (this.cart.length === 0 && this.activeProduct && this.activeProduct.stock_quantity > 0) {
                        const otherRate = parseFloat(this.activeProduct.other_rate) || 90;
                        this.cart.push({
                            product: this.activeProduct,
                            quantity: 1,
                            unit_sale_rate: parseFloat(this.activeProduct.sale_rate),
                            unit_other_rate: otherRate,
                            has_delivery: otherRate >= 50,
                            has_packaging: (otherRate % 50 === 40) || (otherRate >= 90) || (otherRate === 40)
                        });
                    }

                    this.showCheckOutModal = true;
                },

                async submitCheckOut() {
                    if (!this.customerName.trim()) {
                        this.showToast('Please enter customer name', 'error');
                        return;
                    }
                    if (!this.customerAddress.trim()) {
                        this.showToast('Please enter customer address', 'error');
                        return;
                    }
                    if (this.cart.length === 0) {
                        this.showToast('Please add at least 1 product to checkout', 'error');
                        return;
                    }

                    this.loading = true;

                    try {
                        const payload = {
                            customer_name: this.customerName,
                            customer_address: this.customerAddress,
                            customer_phone: this.customerPhone,
                            enquiry_from: this.enquiryFrom,
                            is_promotion: this.isPromotion,
                            discount_amount: parseFloat(this.discountAmount) || 0,
                            expected_delivery_date: this.expectedDeliveryDate || null,
                            items: this.cart.map(item => ({
                                product_id: item.product.id,
                                quantity: item.quantity,
                                unit_sale_rate: item.unit_sale_rate,
                                unit_other_rate: item.unit_other_rate
                            }))
                        };

                        const res = await fetch('/api/v1/checkouts', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await res.json();
                        if (res.ok) {
                            // Update local stock quantities
                            this.cart.forEach(item => {
                                const found = this.products.find(p => p.id === item.product.id);
                                if (found) found.stock_quantity -= item.quantity;
                                if (this.activeProduct && this.activeProduct.id === item.product.id) {
                                    this.activeProduct.stock_quantity -= item.quantity;
                                }
                            });

                            const totalUnits = this.totalCartCount;
                            this.cart = [];
                            this.showCheckOutModal = false;

                            // Set completed order for receipt modal
                            this.completedOrder = data.data;
                            this.showSuccessReceiptModal = true;

                            this.showToast('Order #' + (data.data?.order_number || '') + ' created (' + totalUnits + ' items)!', 'success');

                            // Reset customer inputs
                            this.customerName = '';
                            this.customerAddress = '';
                            this.customerPhone = '';
                            this.isPromotion = false;
                            this.discountAmount = 0;
                            this.expectedDeliveryDate = '';
                        } else {
                            this.showToast(data.message || 'Checkout failed', 'error');
                        }
                    } catch (e) {
                        this.showToast('Network error on checkout', 'error');
                    } finally {
                        this.loading = false;
                    }
                },

                showToast(msg, type = 'success') {
                    this.toastMessage = msg;
                    this.toastType = type;
                    setTimeout(() => {
                        if (this.toastMessage === msg) {
                            this.toastMessage = '';
                        }
                    }, 4000);
                }
            };
        }
    </script>
</body>
</html>
