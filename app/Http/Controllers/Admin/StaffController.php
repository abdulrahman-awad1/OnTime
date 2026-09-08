<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\trait\ApiResponse;
use Illuminate\Support\Facades\Hash;

class StaffController extends Controller
{
    use ApiResponse;

    // POST /api/admin/staff
    public function store(StoreStaffRequest $request)
    {
        $staff = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'phone' => $request->validated('phone'),
            'password' => Hash::make($request->validated('password')),
            'role' => 'admin', // نفس صلاحيات الدكتور بالظبط - قرار متعمد
        ]);

        return $this->returnData('staff', new UserResource($staff), 'تم إضافة الحساب بنجاح', 201);
    }

    // GET /api/admin/staff
    public function index()
    {
        $staff = User::where('role', 'admin')->get();

        return $this->returnData('staff', UserResource::collection($staff));
    }
}
