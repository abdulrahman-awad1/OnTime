<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicLocationRequest;
use App\Http\Resources\ClinicLocationResource;
use App\Models\ClinicLocation;
use App\trait\ApiResponse;

class AdminClinicLocationController extends Controller
{
    use ApiResponse;

    public function store(StoreClinicLocationRequest $request)
    {
        $clinic = ClinicLocation::create($request->validated());

        return $this->returnData('clinic_location', new ClinicLocationResource($clinic), 'تم إضافة العيادة', 201);
    }
}
