<?php

use App\Http\Controllers\user\ClinicLocationController;
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


// عام - المريض يشوف العيادات والمواعيد المتاحة من غير تسجيل دخول
Route::get('/clinic-locations', [ClinicLocationController::class, 'index']);
Route::get('/clinic-locations/{clinicLocation}/time-slots', [ClinicLocationController::class, 'timeSlots']);
