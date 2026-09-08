<?php

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
require __DIR__.'/auth.php';
require __DIR__.'/user.php';
require __DIR__.'/doctor.php';
require __DIR__.'/payment.php';
require __DIR__.'/profile.php';
require __DIR__.'/admin_appointments.php';
