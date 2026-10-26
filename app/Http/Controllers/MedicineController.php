<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // جلب الداتا من المصدر الخارجي وتخزينها في الكاش لمدة 24 ساعة لسرعة الأداء
        $allMedicines = Cache::remember('egypt_medicines_dataset', 86400, function () {
            // رابط مباشر لقاعدة بيانات الأدوية المصرية المرفوعة بصيغة JSON على GitHub
            $url = 'https://raw.githubusercontent.com/anzawi/Egyptian-Drugs-Dataset/main/drugs.json';

            $response = Http::timeout(15)->get($url);

            if ($response->successful()) {
                return $response->json();
            }
            return [];
        });

        // فلترة النتائج بناءً على كلمة البحث (يدعم العربي والإنجليزي والمادة الفعالة)
        $results = collect($allMedicines)
            ->filter(function ($item) use ($q) {
                $nameAr = $item['name_ar'] ?? $item['arabic_name'] ?? '';
                $nameEn = $item['name_en'] ?? $item['english_name'] ?? $item['trade_name'] ?? '';
                $scientific = $item['scientific_name'] ?? $item['active_ingredient'] ?? '';

                return mb_stripos($nameAr, $q) !== false ||
                    mb_stripos($nameEn, $q) !== false ||
                    mb_stripos($scientific, $q) !== false;
            })
            ->take(15)
            ->map(function ($item, $index) {
                // توحيد شكل الـ Response ليكون مطابقاً لجدولك تماماً
                return [
                    'id'                  => $item['id'] ?? ($index + 1),
                    'commercial_name_ar'  => $item['name_ar'] ?? $item['arabic_name'] ?? null,
                    'commercial_name_en'  => $item['name_en'] ?? $item['trade_name'] ?? null,
                    'scientific_name'     => $item['scientific_name'] ?? $item['active_ingredient'] ?? null,
                    'price_egp'           => isset($item['price']) ? (float) $item['price'] : 0,
                ];
            })
            ->values();

        return $this->returnData('medicines', $results);
    }
}
