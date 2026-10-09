<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\MobileAppController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\UserManagementController;
use App\Http\Middleware\EnsureCanAccessAdmin;
use App\Http\Middleware\EnsureCanAccessApp;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Root Route
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->canAccessAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->canAccessApp()) {
            return redirect()->route('app.index');
        }
    }

    return redirect()->route('login');
});

// Admin Panel Routes - Protected by Auth & EnsureCanAccessAdmin
Route::middleware(['auth', EnsureCanAccessAdmin::class])->group(function (): void {
    Route::get('/admin', [ProductController::class, 'index'])->name('admin.dashboard');

    // Products Management & Export
    Route::get('/products/export/csv', [ProductController::class, 'exportCsv'])->name('products.export.csv');
    Route::get('/products/export/pdf', [ProductController::class, 'exportPdf'])->name('products.export.pdf');
    Route::resource('products', ProductController::class);

    // Stock Movements & Check-In
    Route::get('/stock', [StockMovementController::class, 'index'])->name('stock.index');
    Route::post('/stock/check-in', [StockMovementController::class, 'store'])->name('stock.check-in');

    // Checkouts / Sales Ledger & Export
    Route::get('/checkouts/export/csv', [CheckoutController::class, 'exportCsv'])->name('checkouts.export.csv');
    Route::get('/checkouts/export/pdf', [CheckoutController::class, 'exportPdf'])->name('checkouts.export.pdf');
    Route::post('/checkouts/{checkout}/status', [CheckoutController::class, 'quickUpdateStatus'])->name('checkouts.status');
    Route::get('/checkouts/{checkout}/receipt', [CheckoutController::class, 'clientReceipt'])->name('checkouts.receipt');
    Route::resource('checkouts', CheckoutController::class);

    // Reports (Yearly, Monthly, In-Stock, Out-of-Stock, Product Sales & Exports)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export/financial-csv', [ReportController::class, 'exportFinancialCsv'])->name('reports.export.financial.csv');
    Route::get('/reports/export/financial-pdf', [ReportController::class, 'exportFinancialPdf'])->name('reports.export.financial.pdf');
    Route::get('/reports/export/product-sales-csv', [ReportController::class, 'exportProductSalesCsv'])->name('reports.export.product_sales.csv');
    Route::get('/reports/export/product-sales-pdf', [ReportController::class, 'exportProductSalesPdf'])->name('reports.export.product_sales.pdf');

    // User Management & App Permissions
    Route::resource('admin/users', UserManagementController::class)->names('users');

    // Activity & Audit Logs
    Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])->name('activity.index');
});

// Client Printable / Downloadable Receipt (Accessible by any authenticated user)
Route::middleware(['auth'])->group(function (): void {
    Route::get('/receipt/{checkout}', [CheckoutController::class, 'clientReceipt'])->name('client.receipt');
});

// Mobile Camera App / Scanner - Protected by Auth & EnsureCanAccessApp
Route::middleware(['auth', EnsureCanAccessApp::class])->group(function (): void {
    Route::get('/app', [MobileAppController::class, 'index'])->name('app.index');
});
