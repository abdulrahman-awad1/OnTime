<?php

namespace App\Services;

use App\Models\User;
use Ichtrojan\Otp\Otp;
use Ichtrojan\Otp\Models\Otp as OtpModel;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    public function __construct(private WhatsappService $whatsappService) {}

    /**
     * 1. إرسال كود الـ OTP للمريض (مع فحص الـ Role وإلغاء test_otp تماماً)
     */
    public function sendPatientOtp(string $phone): array
    {
        $existingUser = User::where('phone', $phone)->first();
        if ($existingUser && $existingUser->role !== 'patient') {
            return [
                'error' => 'هذا الرقم خاص بحساب طبيب أو إدارة، يرجى تسجيل الدخول بالبريد الإلكتروني وكلمة المرور.'
            ];
        }

        $otp = (new Otp)->generate($phone, 'numeric', 6, 10);

        $sent = $this->whatsappService->sendOtp($phone, $otp->token);

        if (!$sent) {
            return ['error' => 'تعذر إرسال كود التحقق حالياً، يرجى المحاولة مرة أخرى.'];
        }

        return ['status' => true];
    }

    /**
     * 2. التحقق من الـ OTP مع فحص الـ Role قبل firstOrCreate
     */
    public function verifyPatientOtp(string $phone, string $otpCode): ?array
    {
        // فحص الـ Role قبل التحقق وقبل firstOrCreate لمنع إصدار توكن لطبيب أو أدمن أو أسيستنت
        $existingUser = User::where('phone', $phone)->first();
        if ($existingUser && $existingUser->role !== 'patient') {
            return [
                'error' => 'غير مصرح لهذا الحساب بتسجيل الدخول عبر كود التحقق.'
            ];
        }

        $validation = (new Otp)->validate($phone, $otpCode);

        if (!$validation->status) {
            return null;
        }

        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'role'              => 'patient',
                'profile_completed' => false,
            ]
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'              => $user->load('profile'),
            'token'             => $token,
            'profile_completed' => (bool) $user->profile_completed,
        ];
    }

    /**
     * حرق الكود من الداتابيز عند تخطي الحد الأقصى لمحاولات التخمين الخاطئة (Brute-force protection)
     */
    public function invalidateOtp(string $phone): void
    {
        OtpModel::where('identifier', $phone)->delete();
    }

    /**
     * 3. استكمال بيانات المريض
     */
    public function completePatientProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name'              => $data['name'],
                'email'             => $data['email'] ?? null,
                'profile_completed' => true,
            ]);

            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth' => $data['date_of_birth'],
                    'gender'        => $data['gender'],
                    'address'       => $data['address'],
                ]
            );

            return $user->load('profile');
        });
    }

    /**
     * 4. دخول الإدارة والأطباء والمساعدين
     */
    public function Admin_login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return null;
        }

        if ($user->role === 'patient') {
            return ['error' => 'حسابات المرضى تسجل الدخول عبر رقم الهاتف وكود التحقق فقط.'];
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    public function facebookLogin(string $token)
    {
        $providerUser = Socialite::driver('facebook')
            ->fields(['id', 'name', 'email', 'picture.type(large)'])
            ->userFromToken($token);

        $email = $providerUser->email ?? null;

        $user = User::updateOrCreate(
            [
                'provider_name' => 'facebook',
                'provider_id'   => $providerUser->id,
            ],
            [
                'name'   => $providerUser->name,
                'email'  => $email,
                'avatar' => $providerUser->avatar,
            ]
        );

        $user->tokens()->delete();
        $accessToken = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'accessToken');
    }

    public function sendReset(User $user)
    {
        $user->notify(new ResetPasswordNotification());
    }

    public function resetPassword(array $data)
    {
        return DB::transaction(function () use ($data) {
            $otp = OtpModel::where('token', $data['token'])
                ->where('identifier', $data['email'])
                ->where('validity', '>', now()->timestamp)
                ->first();

            if (!$otp) {
                return null;
            }

            $user = User::where('email', $data['email'])->first();
            $user->update(['password' => Hash::make($data['password'])]);

            $otp->delete();
            $user->tokens()->delete();

            return $user;
        });
    }

    public function changePassword(User $user, array $data): User
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match your current password.'],
            ]);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return $user;
    }
}
