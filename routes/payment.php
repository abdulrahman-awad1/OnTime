<?php

use App\Http\Controllers\PaymentSummaryController;
use App\Http\Controllers\user\PaymentController;
use Illuminate\Support\Facades\Route;

// محمي - المريض بس اللي يقدر يبدأ دفع
Route::middleware(['auth:sanctum','admin'])->group(function () {
    Route::post('/payments/pay', [PaymentController::class, 'pay']);
    Route::get('/payments/summary', [PaymentSummaryController::class, 'summary']);

});

// عام تماماً - Paymob هي اللي هتناديهم، مش فيهم auth:sanctum خالص
// الحماية هنا HMAC نفسه، مش تسجيل الدخول
Route::post('/payments/callback', [PaymentController::class, 'callback'])->name('callback');
Route::get('/payments/redirect', [PaymentController::class, 'redirect'])->name('redirect');
