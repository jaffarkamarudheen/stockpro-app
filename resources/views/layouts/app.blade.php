<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Inventory & Sales') - StockPro</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('styles')
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar for Desktop -->
        <aside class="hidden md:flex md:flex-col md:w-64 bg-slate-900 text-slate-200 border-r border-slate-800">
            <!-- Brand Logo -->
            <div class="h-16 flex items-center px-6 bg-slate-950 border-b border-slate-800 gap-3">
                <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                    <i class="fa-solid fa-boxes-stacked text-lg"></i>
                </div>
                <div>
                    <h1 class="font-bold text-base tracking-wide text-white leading-tight">StockPro</h1>
                    <p class="text-[11px] text-slate-400 font-medium">Inventory & Sales System</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <a href="{{ route('products.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('products.*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-box-open w-5 text-center"></i>
                    <span>Products & Stock</span>
                </a>

                <a href="{{ route('stock.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('stock.*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-arrow-down-up-across-line w-5 text-center"></i>
                    <span>Stock Check-In</span>
                </a>

                <a href="{{ route('checkouts.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('checkouts.*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-cart-shopping w-5 text-center"></i>
                    <span>Sales & Checkouts</span>
                </a>

                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('reports.*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                    <span>Reports & Analytics</span>
                </a>

                @if (auth()->user()?->isAdmin())
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('users.*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fa-solid fa-users-gear w-5 text-center"></i>
                        <span>Users & App Permissions</span>
                    </a>

                    <a href="{{ route('activity.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition {{ request()->routeIs('activity.*') ? 'bg-indigo-600 text-white shadow' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                        <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                        <span>Activity & Audit Logs</span>
                    </a>
                @endif

                <div class="pt-4 pb-2">
                    <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Mobile & Scanner</p>
                </div>

                <a href="{{ route('app.index') }}" target="_blank"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold transition bg-emerald-600/20 text-emerald-400 hover:bg-emerald-600 hover:text-white border border-emerald-500/30">
                    <i class="fa-solid fa-camera w-5 text-center"></i>
                    <span>Camera & Stock App</span>
                    <span class="ml-auto text-[10px] px-1.5 py-0.5 rounded bg-emerald-500 text-white font-bold uppercase tracking-wider">Live</span>
                </a>
            </nav>

            <!-- Bottom User / Deployment Info -->
            <div class="p-4 border-t border-slate-800 text-xs text-slate-400 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Ready for Render.com</span>
                </div>
                <span class="font-mono text-[10px] text-slate-400">PGSQL / SQLite</span>
            </div>
        </aside>

        <!-- Mobile Drawer Navigation -->
        <div x-show="sidebarOpen" x-cloak class="relative z-50 md:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm"
                 @click="sidebarOpen = false"></div>

            <div class="fixed inset-0 flex">
                <div x-show="sidebarOpen"
                     x-transition:enter="transition ease-in-out duration-200 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-200 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-16 flex w-full max-w-xs flex-1 bg-slate-900 flex-col">
                    <div class="h-16 flex items-center justify-between px-6 bg-slate-950 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white">
                                <i class="fa-solid fa-boxes-stacked"></i>
                            </div>
                            <span class="font-bold text-white">StockPro</span>
                        </div>
                        <button type="button" @click="sidebarOpen = false" class="text-slate-400 hover:text-white p-2">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                        <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800">
                            <i class="fa-solid fa-box-open w-5 text-center"></i>
                            <span>Products</span>
                        </a>
                        <a href="{{ route('stock.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800">
                            <i class="fa-solid fa-arrow-down-up-across-line w-5 text-center"></i>
                            <span>Stock Check-In</span>
                        </a>
                        <a href="{{ route('checkouts.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800">
                            <i class="fa-solid fa-cart-shopping w-5 text-center"></i>
                            <span>Sales & Checkouts</span>
                        </a>
                        <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800">
                            <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                            <span>Reports</span>
                        </a>

                        @if (auth()->user()?->isAdmin())
                            <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800">
                                <i class="fa-solid fa-users-gear w-5 text-center"></i>
                                <span>Users & Permissions</span>
                            </a>
                            <a href="{{ route('activity.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-300 hover:bg-slate-800">
                                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                                <span>Activity Logs</span>
                            </a>
                        @endif

                        <div class="pt-3 pb-1 border-t border-slate-800 mt-2">
                            <a href="{{ route('app.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-semibold bg-emerald-600 text-white">
                                <i class="fa-solid fa-camera w-5 text-center"></i>
                                <span>Camera Scanner App</span>
                            </a>
                        </div>
                    </nav>

                    @auth
                        <div class="p-4 border-t border-slate-800 bg-slate-950 flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-xs uppercase">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-white leading-tight">{{ auth()->user()->name }}</p>
                                    <span class="inline-block text-[10px] text-indigo-400 capitalize">{{ auth()->user()->role }}</span>
                                </div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 transition" title="Logout">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-y-auto">
            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-slate-200 px-4 md:px-8 flex items-center justify-between sticky top-0 z-30 shadow-xs">
                <div class="flex items-center gap-3">
                    <button type="button" @click="sidebarOpen = true" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <h2 class="text-lg md:text-xl font-bold text-slate-800">@yield('page_heading', 'Dashboard')</h2>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Quick action buttons -->
                    <a href="{{ route('checkouts.create') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs md:text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 transition shadow-sm">
                        <i class="fa-solid fa-cart-arrow-down"></i>
                        <span>New Checkout</span>
                    </a>
                    <a href="{{ route('products.create') }}" class="inline-flex items-center gap-2 px-3 py-2 text-xs md:text-sm font-medium rounded-lg text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-xs">
                        <i class="fa-solid fa-plus text-indigo-600"></i>
                        <span>Add Product</span>
                    </a>
                    <a href="{{ route('app.index') }}" target="_blank" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 text-xs md:text-sm font-medium rounded-lg text-emerald-700 bg-emerald-50 border border-emerald-300 hover:bg-emerald-100 transition">
                        <i class="fa-solid fa-qrcode"></i>
                        <span>Mobile App</span>
                    </a>

                    @auth
                        <div class="h-6 w-px bg-slate-200"></div>
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200 flex items-center justify-center font-bold text-xs uppercase shadow-xs">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <div class="hidden lg:block text-left">
                                    <div class="text-xs font-semibold text-slate-800 leading-tight">{{ auth()->user()->name }}</div>
                                    <div class="text-[10px] text-slate-500 capitalize">{{ auth()->user()->role }}</div>
                                </div>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:text-rose-600 hover:bg-rose-50 rounded-lg border border-slate-200 transition" title="Logout">
                                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                                    <span class="hidden sm:inline">Logout</span>
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </header>

            <!-- Alerts / Flash Messages -->
            <div class="px-4 md:px-8 pt-4">
                @if (session('success'))
                    <div class="mb-4 p-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start gap-3 shadow-xs">
                        <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
                        <div class="flex-1 text-sm font-medium">{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 flex items-start gap-3 shadow-xs">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600 mt-0.5"></i>
                        <div class="flex-1 text-sm font-medium">{{ session('error') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-4 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-sm shadow-xs">
                        <div class="font-semibold flex items-center gap-2 mb-1">
                            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                            <span>Please check the errors below:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700 ml-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Main Page Slot -->
            <main class="flex-1 p-4 md:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
