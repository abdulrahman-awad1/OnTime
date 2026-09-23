<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\trait\ApiResponse;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    use ApiResponse;

    // GET /doctor/medicines/search?q=بنادول
    public function search(Request $request)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        $q = $request->string('q')->toString();

        if (strlen($q) < 2) {
            return $this->returnData('medicines', []);
        }

        $medicines = Medicine::where('commercial_name_ar', 'like', "%{$q}%")
            ->orWhere('commercial_name_en', 'like', "%{$q}%")
            ->orWhere('scientific_name', 'like', "%{$q}%")
            ->limit(15)
            ->get(['id', 'commercial_name_ar', 'commercial_name_en', 'scientific_name', 'price_egp']);

        return $this->returnData('medicines', $medicines);
    }
}
