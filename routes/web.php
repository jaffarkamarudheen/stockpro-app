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

    // Products Management
    Route::resource('products', ProductController::class);

    // Stock Movements & Check-In
    Route::get('/stock', [StockMovementController::class, 'index'])->name('stock.index');
    Route::post('/stock/check-in', [StockMovementController::class, 'store'])->name('stock.check-in');

    // Checkouts / Sales Ledger
    Route::resource('checkouts', CheckoutController::class)->only(['index', 'create', 'store', 'show']);

    // Reports (Yearly, Monthly, In-Stock, Out-of-Stock)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

    // User Management & App Permissions
    Route::resource('admin/users', UserManagementController::class)->names('users');

    // Activity & Audit Logs
    Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])->name('activity.index');
});

// Mobile Camera App / Scanner - Protected by Auth & EnsureCanAccessApp
Route::middleware(['auth', EnsureCanAccessApp::class])->group(function (): void {
    Route::get('/app', [MobileAppController::class, 'index'])->name('app.index');
});
