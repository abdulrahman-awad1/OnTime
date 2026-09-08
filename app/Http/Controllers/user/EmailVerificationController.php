<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\EmailVerification;
use App\Models\User;
use App\trait\ApiResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    use apiResponse;
    public function verifyEmail(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string'
        ]);

        $record = EmailVerification::where('token',$data['token'])
            ->where('expires_at','>',now())
            ->first();

        if (!$record) {
            return $this->returnError('VER001','Invalid token');
        }

        $user = User::where('email',$record->email)->first();
      //  $user = auth()->user();


        $user->update([
            'email_verified_at' => now()
        ]);

        $record->delete();

        return $this->successMessage('Email verified successfully');
    }
}
