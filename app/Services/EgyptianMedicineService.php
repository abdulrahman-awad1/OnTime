<?php

namespace App\Services;

use App\Models\Medicine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EgyptianMedicineService
{

    public function searchAndSync(string $query): Collection
    {
        $query = trim($query);
        $searchVariants = $this->getArabicSearchVariants($query);

        // 1. نبحث الأول في الداتابيز بتاعتنا (سريع جداً: 50ms - 100ms)
        $medicines = $this->queryLocalDatabase($searchVariants);

        // 2. لو الدواء موجود عندنا، نرجعه فوراً بدون أي تعطيل خارجي!
        if ($medicines->isNotEmpty()) {
            return $medicines;
        }

        // 3. لو مش موجود عندنا (دواء جديد لسه نازل السوق)، نطلع نجيبه من بره ونخزنه عندنا
        $this->fetchAndSyncFromLiveSource($query);

        // 4. نبحث تاني في جدولنا بعد ما اتخزن ونرجعه للدكتور
        return $this->queryLocalDatabase($searchVariants);
    }

    protected function queryLocalDatabase(array $searchVariants): Collection
    {
        return Medicine::where(function ($q) use ($searchVariants) {
            foreach ($searchVariants as $variant) {
                $q->orWhere('commercial_name_ar', 'like', "%{$variant}%")
                    ->orWhere('commercial_name_en', 'like', "%{$variant}%")
                    ->orWhere('scientific_name', 'like', "%{$variant}%");
            }
        })
            ->limit(15)
            ->get(['id', 'commercial_name_ar', 'commercial_name_en', 'scientific_name', 'price_egp']);
    }

    /**
     * الاتصال المباشر لجلب الأدوية الجديدة وتحديث الأسعار
     */
    protected function fetchAndSyncFromLiveSource(string $query): void
    {
        try {
            // الاتصال بمحرك بحث المنتجات الدوائية في السوق المصري (يدعم العربي والإنجليزي والأسعار المحدثة)
            $response = Http::timeout(4)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                ])
                ->get('https://chefaa.com/api/v1/products/search', [
                    'q' => $query,
                ]);

            if ($response->successful() && !empty($response->json('data'))) {
                foreach (collect($response->json('data'))->take(15) as $item) {
                    $nameAr = $item['name_ar'] ?? $item['title_ar'] ?? $item['name'] ?? null;
                    $nameEn = $item['name_en'] ?? $item['title_en'] ?? $item['slug'] ?? $nameAr;
                    $price  = $item['price'] ?? $item['final_price'] ?? 0;
                    $scientific = $item['active_ingredient'] ?? $item['scientific_name'] ?? '';

                    if ($nameAr || $nameEn) {
                        Medicine::updateOrCreate(
                            ['commercial_name_en' => $nameEn],
                            [
                                'commercial_name_ar' => $nameAr ?? $nameEn,
                                'scientific_name'    => $scientific,
                                'price_egp'          => (float) $price,
                            ]
                        );
                    }
                }
                return;
            }

            // Fallback: إذا كان البحث بالإنجليزية لمادة فعالة أو دواء عالمي غير موجود في الرد الأول
            // نتصل بـ API المعهد الوطني للصحة (RxNorm) المضمون 100%
            if (preg_match('/^[a-zA-Z0-9\s]+$/', $query)) {
                $rxResponse = Http::timeout(4)->get('https://rxnav.nlm.nih.gov/REST/approximateTerm.json', [
                    'term' => $query,
                    'maxEntries' => 10,
                ]);

                if ($rxResponse->successful()) {
                    $candidates = collect($rxResponse->json('approximateGroup.candidate') ?? [])
                        ->unique('rxcui')
                        ->whereNotNull('name')
                        ->take(10);

                    foreach ($candidates as $candidate) {
                        Medicine::firstOrCreate(
                            ['commercial_name_en' => $candidate['name']],
                            [
                                'commercial_name_ar' => $candidate['name'],
                                'scientific_name'    => $candidate['name'],
                                'price_egp'          => 0,
                            ]
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            // في حال حدوث Timeout أو بطء من المصدر الخارجي، نسجل الخطأ بهدوء
            // ولا نوقف الكود، بل يكمل البحث فوراً من قاعدة البيانات المحلية
            Log::warning('Live Medicine Sync Timeout/Notice: ' . $e->getMessage());
        }
    }

    /**
     * معالجة ذكية للكلمات العربية (مثل: بنادول <-> بانادول، والهمزات)
     */
    protected function getArabicSearchVariants(string $query): array
    {
        $variants = [$query];

        // توحيد الهمزات (أ، إ، آ -> ا)
        $normalized = str_replace(['أ', 'إ', 'آ'], 'ا', $query);
        $variants[] = $normalized;

        // معالجة حالة "بنادول" و "بانادول" الشهيرة في الأدوية
        if (str_contains($normalized, 'بنادول')) {
            $variants[] = str_replace('بنادول', 'بانادول', $normalized);
            $variants[] = 'Panadol';
        } elseif (str_contains($normalized, 'بانادول')) {
            $variants[] = str_replace('بانادول', 'بنادول', $normalized);
            $variants[] = 'Panadol';
        }

        return array_unique($variants);
    }
}
