<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MedicineController extends Controller
{
    use ApiResponse;


    public function search(Request $request)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 2) {
            return $this->returnData('medicines', []);
        }

        $medicines = Medicine::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('commercial_name_en', 'like', "%{$q}%")
                    ->orWhere('scientific_name', 'like', "%{$q}%")
                    ->orWhere('commercial_name_ar', 'like', "%{$q}%");
            })
            // أولوية الترتيب: الدواء الذي يبدأ اسمه بالكلمة يظهر أولاً في القائمة المنسدلة للدكتور
            ->orderByRaw("CASE WHEN commercial_name_en LIKE ? THEN 0 ELSE 1 END", ["{$q}%"])
            ->orderBy('commercial_name_en', 'asc')
            ->limit(15)
            ->get(['id', 'commercial_name_ar', 'commercial_name_en', 'scientific_name', 'price_egp']);

        return $this->returnData('medicines', $medicines);
    }
}
