<?php

use App\Http\Controllers\Admin\SummaryController;

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/stats/today', [SummaryController::class, 'todayStats']);
    Route::get('/stats/general', [SummaryController::class, 'generalStats']);
});
