<?php

use App\Http\Controllers\PaymentSummaryController;
use App\Http\Controllers\user\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payments/pay', [PaymentController::class, 'pay']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/payments/summary', [PaymentSummaryController::class, 'summary']);
});

Route::post('/payments/callback', [PaymentController::class, 'callback'])->name('callback');
Route::get('/payments/redirect', [PaymentController::class, 'redirect'])->name('redirect');
