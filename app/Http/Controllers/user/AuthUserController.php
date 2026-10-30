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
        $normalizedPhone = preg_replace('/\s+/', '', (string) $request->phone);
        if (str_starts_with($normalizedPhone, '+20')) {
            $normalizedPhone = substr($normalizedPhone, 2);
        } elseif (str_starts_with($normalizedPhone, '201') && strlen($normalizedPhone) === 12) {
            $normalizedPhone = substr($normalizedPhone, 1);
        }
        $request->merge(['phone' => $normalizedPhone]);

        $request->validate([
            'phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
        ], [
            'phone.regex' => 'يرجى إدخال رقم هاتف مصري صحيح مكون من 11 رقم (يبدأ بـ 01).'
        ]);

        $phone = $request->phone;
        $ip    = $request->ip();

        // 1. حماية رصيد الواتساب بالـ IP: بحد أقصى 5 رسائل كل 15 دقيقة لنفس الـ IP مهما غير الأرقام
        $ipKey = 'otp-send-ip:' . $ip;
        if (RateLimiter::tooManyAttempts($ipKey, 5)) {
            $seconds = RateLimiter::availableIn($ipKey);
            return $this->returnError('429', "تم تجاوز الحد المسموح من الطلبات. يرجى المحاولة بعد {$seconds} ثانية.", 429);
        }

        // 2. حماية الرقم نفسه: طلب واحد كل 60 ثانية
        $phoneKey = 'otp-send-phone:' . $phone;
        if (RateLimiter::tooManyAttempts($phoneKey, 1)) {
            $seconds = RateLimiter::availableIn($phoneKey);
            return $this->returnError('429', "يرجى الانتظار {$seconds} ثانية قبل طلب كود جديد.", 429);
        }

        $result = $this->authService->sendPatientOtp($phone);

        if (isset($result['error'])) {
            return $this->returnError('E002', $result['error'], 403);
        }

        // تسجيل المحاولة في الـ RateLimiter بعد نجاح الإرسال
        RateLimiter::hit($ipKey, 900);   // 15 دقيقة للـ IP
        RateLimiter::hit($phoneKey, 60); // 60 ثانية للرقم

        // تصفير عداد محاولات التحقق الخاطئة السابقة عند إرسال كود جديد
        RateLimiter::clear('otp-verify-phone:' . $phone);

        return $this->successMessage('تم إرسال كود التحقق إلى رقم الواتساب الخاص بك بنجاح.');
    }

    public function verifyOtp(Request $request)
    {
        $normalizedPhone = preg_replace('/\s+/', '', (string) $request->phone);
        if (str_starts_with($normalizedPhone, '+20')) {
            $normalizedPhone = substr($normalizedPhone, 2);
        } elseif (str_starts_with($normalizedPhone, '201') && strlen($normalizedPhone) === 12) {
            $normalizedPhone = substr($normalizedPhone, 1);
        }
        $request->merge(['phone' => $normalizedPhone]);

        $request->validate([
            'phone' => ['required', 'string', 'regex:/^01[0125][0-9]{8}$/'],
            'otp'   => ['required', 'digits:6'],
        ]);

        $phone = $request->phone;
        $ip    = $request->ip();

        $verifyPhoneKey = 'otp-verify-phone:' . $phone;
        $verifyIpKey    = 'otp-verify-ip:' . $ip;

        // فحص عدد محاولات التخمين (بحد أقصى 5 محاولات للرقم أو 10 للـ IP خلال 10 دقائق)
        if (RateLimiter::tooManyAttempts($verifyPhoneKey, 5) || RateLimiter::tooManyAttempts($verifyIpKey, 10)) {
            // حرق الكود فوراً في الداتابيز لمنع أي محاولة أخرى حتى لو انتهى وقت الحظر
            $this->authService->invalidateOtp($phone);

            return $this->returnError('429', 'تم استنفاد محاولات التحقق المسموحة وإلغاء الكود. يرجى طلب كود جديد.', 429);
        }

        $result = $this->authService->verifyPatientOtp($phone, $request->otp);

        if (isset($result['error'])) {
            return $this->returnError('E003', $result['error'], 403);
        }

        if (!$result) {
            RateLimiter::hit($verifyPhoneKey, 600); // 10 دقائق
            RateLimiter::hit($verifyIpKey, 600);

            $remaining = RateLimiter::remaining($verifyPhoneKey, 5);
            if ($remaining === 0) {
                $this->authService->invalidateOtp($phone);
                return $this->returnError('429', 'تم إدخال الكود بشكل خاطئ 5 مرات. تم إلغاء الكود، يرجى طلب كود جديد.', 429);
            }

            return $this->returnError('E001', "كود التحقق غير صحيح أو منتهي الصلاحية. المحاولات المتبقية: {$remaining}", 401);
        }

        // نجاح التحقق -> مسح عدادات الأخطاء
        RateLimiter::clear($verifyPhoneKey);
        RateLimiter::clear($verifyIpKey);

        return $this->returnData('user', [
            'user'              => new UserResource($result['user']),
            'access_token'      => $result['token'],
            'profile_completed' => $result['profile_completed'],
        ], 'تم تسجيل الدخول بنجاح');
    }


    public function Admin_login(AdminLoginRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->Admin_login($data);

        if (!$result) {
            return $this->returnError('E001', 'Invalid credentials');
        }
        if (isset($result['error'])) {
            return $this->returnError('E002', $result['error']);
        }

        return $this->returnData('user', [
            'user'         => new AdminResource($result['user']),
            'access_token' => $result['token'],
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
            return $this->successMessage('Logout successful');
        }

        return $this->returnError('401', 'Unauthenticated', 401);
    }
}
