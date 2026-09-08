<?php

use App\Http\Controllers\user\AppointmentController;
use App\Http\Controllers\user\AuthUserController;
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


// Auth - register للمريض بس، login موحّد للاتنين
Route::post('/register', [AuthUserController::class, 'register']);
Route::post('/login', [AuthUserController::class, 'login']);
// محمي - محتاج يكون المستخدم عامل تسجيل دخول (Sanctum) - أي role
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthUserController::class, 'logout']);
    Route::get('/me', [AuthUserController::class, 'me']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/my', [AppointmentController::class, 'myAppointments']);
    Route::delete('/appointments/{appointment}', [AppointmentController::class, 'cancel']);
});

