<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Resources\AdminResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class AuthUserController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    public function login(Request $request)
    {
        // توحيد وتنظيف الرقم قبل الفاليديشن (إزالة المسافات أو +2)
        $normalizedPhone = preg_replace('/\s+/', '', (string)$request->phone);
        if (str_starts_with($normalizedPhone, '+20')) {
            $normalizedPhone = substr($normalizedPhone, 2);
        } elseif (str_starts_with($normalizedPhone, '201') && strlen($normalizedPhone) === 12) {
            $normalizedPhone = substr($normalizedPhone, 1);
        }
        $request->merge(['phone' =>$normalizedPhone]);

        $request->validate([
            'phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
        ], [
            'phone.regex' => 'يرجى إدخال رقم هاتف مصري صحيح مكون من 11 رقم (يبدأ بـ 01).'
        ]);

        $phone =$request->phone;
        $ip    =$request->ip();

        // 1. حماية رصيد الواتساب بالـ IP: بحد أقصى 5 رسائل كل 15 دقيقة لنفس الـ IP مهما غير الأرقام
        $ipKey = 'otp-send-ip:' .$ip;
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return $this->returnError('429', "تم تجاوز الحد المسموح من الطلبات. يرجى المحاولة بعد {$seconds} ثانية.", 429);
        }

        // 2. حماية الرقم نفسه: طلب واحد كل 60 ثانية
        $phoneKey = 'otp-send-phone:' .$phone;
        if (RateLimiter::tooManyAttempts($phoneKey, 1)) {
            $seconds = RateLimiter::availableIn($phoneKey);
            return $this->returnError('429', "يرجى الانتظار {$seconds} ثانية قبل طلب كود جديد.", 429);
        }

        $result = $this->authService->sendPatientOtp($phone);

        if (isset($result['error'])) {
            return $this->returnError('E002', $result['error'], 403); }          // تسجيل المحاولة في الـ RateLimiter بعد نجاح الإرسال RateLimiter::hit($
