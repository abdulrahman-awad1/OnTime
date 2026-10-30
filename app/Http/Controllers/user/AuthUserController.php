<?php
namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\CheckLoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\StoreProfileRequest;
use App\Http\Resources\AdminResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\UserResource;
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


    public function register(StoreProfileRequest $request)
    {
        $user = $request->user();

        $data = $request->validated();

        $profile = $this->ProfileService->create(
            $user,
            $data
        );

        return $this->returnData(
            'profile',
            new ProfileResource($profile),
            'Patient profile created successfully'
        );
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
