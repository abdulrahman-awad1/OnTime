<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LangController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\SocialAuthController;
use App\Http\Controllers\User\AuthUserController;
use App\Http\Controllers\User\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
// api >> دي الميدل وير الديفولت
Route::group(['middleware'=>'api','prefix'=>'post'],function (){
    Route::apiResource('post',PostController::class,);
// x عملنا ميدل وير جديد غير الديفولت
});
Route::group(['middleware'=>['api','ChekUser'],'prefix'=>'post'],function (){
    Route::apiResource('posts',PostController::class);
});
Route::group(['middleware'=>['api','ChekUser','lang'],'prefix'=>'lang'],function (){
    Route::apiResource('language',LangController::class);

});

// ================= Admin Routes =================


Route::prefix('admin')->group(function () {

    // Public routes
    Route::post('register', [AuthController::class,'register_admin']);
    Route::post('login', [AuthController::class,'login_admin'])->name('admin.login');

    // Protected routes (requires admin login)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class,'logout_admin']);
    });
});

// ================= user Routes =================

Route::prefix('user')->group(function () {

    // Public route
    Route::post('register', [AuthUserController::class, 'register']);
    Route::post('login', [AuthUserController::class, 'login'])->name('login');
    Route::post('facebook_login', [SocialAuthController::class, 'logFacebook']);
    Route::post('forgotPassword', [PasswordController::class, 'forgotPassword'])->name('forgotPassword');
    Route::post('resetPassword', [PasswordController::class, 'resetPassword'])->name('resetPassword');
    Route::post('verify-email', [EmailVerificationController::class,'verifyEmail']);
    Route::post('payments/pay', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::post('payments/callback', [PaymentController::class, 'callback'])->name('payments.callback');
    Route::get('payments/redirect', [PaymentController::class, 'redirect'])->name('redirect');



    // Protected routes (requires user login)
    Route::middleware(['auth:sanctum','verified.api'])->group(function () {
        Route::post('logout', [AuthUserController::class,'logout']);
        Route::post('changePassword', [PasswordController::class,'changePassword']);
        Route::get('profile', function(Request $request){
            return $request->user();
        });
    });
});
