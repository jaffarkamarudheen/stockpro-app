<?php

use App\Http\Controllers\Api\V1\CheckoutApiController;
use App\Http\Controllers\Api\V1\ProductApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use App\Http\Controllers\Api\V1\StockApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    // Products
    Route::get('/products', [ProductApiController::class, 'index'])->name('products.index');
    Route::post('/products', [ProductApiController::class, 'store'])->name('products.store');
    Route::get('/products/{identifier}', [ProductApiController::class, 'show'])->name('products.show');
    Route::match(['put', 'patch'], '/products/{product}', [ProductApiController::class, 'update'])->name('products.update');
    Route::delete('/products/{product}', [ProductApiController::class, 'destroy'])->name('products.destroy');

    // Stock Actions
    Route::post('/stock/check-in', [StockApiController::class, 'checkIn'])->name('stock.check-in');
    Route::get('/stock/history', [StockApiController::class, 'history'])->name('stock.history');

    // Checkout / Sales (Customer details & line items)
    Route::get('/checkouts', [CheckoutApiController::class, 'index'])->name('checkouts.index');
    Route::post('/checkouts', [CheckoutApiController::class, 'store'])->name('checkouts.store');
    Route::get('/checkouts/{checkout}', [CheckoutApiController::class, 'show'])->name('checkouts.show');
    Route::match(['put', 'patch'], '/checkouts/{checkout}', [CheckoutApiController::class, 'update'])->name('checkouts.update');
    Route::delete('/checkouts/{checkout}', [CheckoutApiController::class, 'destroy'])->name('checkouts.destroy');
    Route::post('/checkouts/{checkout}/status', [CheckoutApiController::class, 'updateStatus'])->name('checkouts.status');

    // Reports (Yearly, Monthly, In-Stock, Out-of-Stock)
    Route::get('/reports/summary', [ReportApiController::class, 'summary'])->name('reports.summary');
    Route::get('/reports/stock-status', [ReportApiController::class, 'stockStatus'])->name('reports.stock-status');
});
