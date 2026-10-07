<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicLocationRequest;
use App\Http\Resources\ClinicLocationResource;
use App\Http\Resources\GetAllClinicResource;
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
    public function index()
    {
        $clinicLocations = ClinicLocation::all();
        return $this->returnData('clinic_locations', GetAllClinicResource::collection($clinicLocations), 'تم جلب العيادات بنجاح');

    }
    public function getVisitTypes(ClinicLocation $clinic)
    {
        $visitTypes = [
            [
                'key'       => 'checkup',
                'label'     => 'كشف',
                'price'     => (float) $clinic->checkup_price, // أو اسم عمود سعر الكشف عندك
                'formatted' => 'كشف - ' . number_format($clinic->checkup_price) . ' ج.م',
            ],
            [
                'key'       => 'consultation',
                'label'     => 'استشارة',
                'price'     => (float) $clinic->consultation_price, // أو اسم عمود سعر الاستشارة عندك
                'formatted' => 'استشارة - ' . number_format($clinic->consultation_price) . ' ج.م',
            ],
        ];

        return $this->returnData('visit_types', $visitTypes);
    }
}
