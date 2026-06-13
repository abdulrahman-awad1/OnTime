<?php
namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckLoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\userResource;
use App\Models\User;
use App\Models\EmailVerification;
use App\Services\AuthService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthUserController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $user = $this->authService->register($data);

        return $this->returnData('user', new UserResource($user), 'User registered successfully');
    }




    public function login(CheckLoginRequest $request)
    {
        $data = $request->validated();

        $result = $this->authService->login($data);

        if (!$result) {
            return $this->returnError('E001', 'Invalid credentials');
        }
        if (isset($result['error'])) {
            return $this->returnError('E002', $result['error']);
        }

        return $this->returnData('user', [
            'user'         => new UserResource($result['user']),
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
