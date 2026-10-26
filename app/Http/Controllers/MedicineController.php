<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MedicineController extends Controller
{
    use ApiResponse;

    // GET /doctor/medicines/search?q=بنادول

    public function search(Request $request)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        $q = trim($request->string('q')->toString());

        if (mb_strlen($q) < 2) {
            return $this->returnData('medicines', []);
        }

        // استدعاء API حقيقي ومفتوح للبحث التقريبي عن الأدوية
        $response = Http::timeout(10)->get('https://rxnav.nlm.nih.gov/REST/approximateTerm.json', [
            'term' => $q,
            'maxEntries' => 15,
        ]);

        if (!$response->successful()) {
            return $this->returnData('medicines', []);
        }

        $candidates = $response->json('approximateGroup.candidate') ?? [];

        // تنسيق النتيجة لتطابق الحقول في تطبيقك
        $medicines = collect($candidates)
            ->unique('rxcui')
            ->whereNotNull('name')
            ->take(15)
            ->values()
            ->map(function ($item) {
                return [
                    'id'                 => (int) $item['rxcui'],
                    'commercial_name_ar' => $item['name'], // هذا الـ API يدعم الإنجليزية فقط
                    'commercial_name_en' => $item['name'],
                    'scientific_name'    => $item['name'],
                    'price_egp'          => 0, // لأنها قاعدة بيانات عالمية لا تحتوي على التسعير المصري
                ];
            });

        return $this->returnData('medicines', $medicines);
    }
}
