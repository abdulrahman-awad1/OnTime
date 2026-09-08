<?php

use App\Http\Controllers\Admin\AdminTimeSlotController;
use App\Http\Controllers\Admin\AdminClinicLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
//  >>>> >>config >> auth   دا معناه انك هتدخل ع , ..
// auth >> authentication معناها ان لازم اكون عامل
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


// الأدمن (الدكتور) - محمي بميدلوير إضافي للتأكد إنه admin
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/time-slots', [AdminTimeSlotController::class, 'index']);
    Route::post('/time-slots', [AdminTimeSlotController::class, 'store']);
    Route::delete('/time-slots/{timeSlot}', [AdminTimeSlotController::class, 'destroy']);
    Route::post('/clinic-locations', [AdminClinicLocationController::class, 'store']);

});
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // ... باقي الـ admin routes زي ما هي
});
