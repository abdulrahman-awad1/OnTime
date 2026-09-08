<?php

use App\Http\Controllers\Admin\AdminAppointmentController;
use Illuminate\Support\Facades\Route;
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // ... باقي الـ routes الموجودة زي ما هي

    Route::post('/appointments/walk-in', [AdminAppointmentController::class, 'bookWalkIn']);
    Route::post('/appointments/{appointment}/cancel', [AdminAppointmentController::class, 'cancel']);
    Route::post('/appointments/{appointment}/confirm-cash-payment', [AdminAppointmentController::class, 'confirmCashPayment']);

    Route::get('/appointments', [AdminAppointmentController::class, 'index']);
    Route::patch('/appointments/{appointment}', [AdminAppointmentController::class, 'updateStatus']);

    Route::post('/staff', [StaffController::class, 'store']);
    Route::get('/staff', [StaffController::class, 'index']);
});
