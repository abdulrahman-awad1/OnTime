<?php

namespace App\Services;


use App\Models\User;
use Ichtrojan\Otp\Models\Otp;
use App\Notifications\LoginNotification;
use App\Notifications\verificationNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\DB;
use App\Models\EmailVerification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Str;

class AuthService
{
    public function register(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'phone' => $data['phone'],
        ]);

        $token = Str::random(60);

     //   EmailVerification::create([
      //      'email' => $user->email,
      //      'token' => $token,
      //      'expires_at' => now()->addMinutes(30),
      //  ]);

      //  $user->notify(new VerifyEmailNotification($token));


        return $user;
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return null;
        }

        if (!$user->email_verified_at) {
            return [
                'error' => 'Email not verified'
            ];
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }


    public function facebookLogin(string $token)
    {
        $providerUser = Socialite::driver('facebook')
            ->fields(['id','name','email','picture.type(large)'])
            ->userFromToken($token);

        $email = $providerUser->email
            ?? $providerUser->id . '@facebook.local';

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

            $otp = Otp::where('token', $data['token'])
                ->where('identifier', $data['email'])
                ->where('validity', '>',  now()->timestamp)
                ->first();

            if (!$otp) {
                return null;
            }

            $user = User::where('email', $data['email'])->first();

            $user->update([
                'password' => Hash::make($data['password']),
            ]);

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
