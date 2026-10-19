<?php


use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StripeCheckoutController;
use App\Http\Controllers\AiInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    // Dashboard homepage
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // AI Auto-Invoice Modal submission route
    Route::post('/ai/auto-invoice', [AiInvoiceController::class, 'generate'])->name('ai.auto-invoice');
});

Route::get('/', function () {
    return view('welcome');
});


// Merge these routes into your existing routes/web.php; do not replace the whole file.
Route::middleware(['auth', 'throttle:10,1'])->group(function (): void {
    Route::post('/billing/checkout', [StripeCheckoutController::class, 'store'])
        ->name('billing.checkout');
});

// These return pages are informational only. Do not grant access based on a redirect.
Route::view('/billing/success', 'billing.success')->name('billing.success');
Route::view('/billing/cancel', 'billing.cancelled')->name('billing.cancel');

