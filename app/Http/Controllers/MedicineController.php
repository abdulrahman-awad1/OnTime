<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Services\EgyptianMedicineService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MedicineController extends Controller
{
    use ApiResponse;


    public function search(Request $request, EgyptianMedicineService $medicineService)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        $q = $request->string('q')->toString();

        // استخدام mb_strlen بدلاً من strlen لدعم الحروف العربية بشكل صحيح
        if (mb_strlen(trim($q)) < 2) {
            return $this->returnData('medicines', []);
        }

        $medicines = $medicineService->searchAndSync($q);

        return $this->returnData('medicines', $medicines);
    }
}
