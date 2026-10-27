<?php

namespace App\Services;

use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class EgyptianMedicineService
{
    public function searchAndSync(string $query): Collection
    {
        $query = trim($query);

        // 1. البحث في قاعدة البيانات المحلية أولاً (سرعة فائقة)
        $medicines = $this->queryLocalDatabase($query);

        if ($medicines->isNotEmpty()) {
            return $medicines;
        }

        // 2. إذا لم يكن الدواء موجوداً محلياً، يتم جلبه من الـ API الخارجي وتخزينه
        $this->fetchAndSyncFromLiveSource($query);

        // 3. إعادة البحث محلياً بعد الحفظ لإرجاع البيانات بالـ ID الحقيقي
        return $this->queryLocalDatabase($query);
    }

    protected function queryLocalDatabase(string $query): Collection
    {
        return Medicine::where(function ($q) use ($query) {
            $q->where('commercial_name_en', 'like', "%{$query}%")
                ->orWhere('scientific_name', 'like', "%{$query}%");
        })
            ->limit(15)
            ->get(['id', 'commercial_name_ar', 'commercial_name_en', 'scientific_name', 'price_egp']);
    }

    protected function fetchAndSyncFromLiveSource(string $query): void
    {
        $recordsToInsert = [];
        $now = now();

        // المصدر الأول: NLM ClinicalTables API (ممتاز وسريع جداً للـ Autocomplete بالاسم الإنجليزي والتركيزات)
        try {
            $clinicalResponse = Http::timeout(5)->get('https://clinicaltables.nlm.nih.gov/api/rxterms/v3/search', [
                'terms'   => $query,
                'ef'      => 'STRENGTHS_AND_FORMS,SXDG_NAME',
                'maxList' => 15,
            ]);

            if ($clinicalResponse->successful()) {
                $data  = $clinicalResponse->json();
                $names = $data[1] ?? [];
                $extra = $data[2] ?? [];

                foreach ($names as $index => $drugName) {
                    $scientific = $extra['SXDG_NAME'][$index] ?? $drugName;
                    $forms = isset($extra['STRENGTHS_AND_FORMS'][$index])
                        ? implode(', ', (array) $extra['STRENGTHS_AND_FORMS'][$index])
                        : '';

                    $fullEnName = trim($drugName . ' ' . $forms);

                    $recordsToInsert[] = [
                        'commercial_name_ar' => $fullEnName,
                        'commercial_name_en' => $fullEnName,
                        'scientific_name'    => $scientific,
                        'price_egp'          => 0,
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('ClinicalTables API Error: ' . $e->getMessage());
        }

        // المصدر الثاني (Fallback): OpenFDA API (لجلب الأسماء التجارية والمواد الفعالة الإضافية)
        if (empty($recordsToInsert)) {
            try {
                $fdaResponse = Http::timeout(5)->get('https://api.fda.gov/drug/ndc.json', [
                    'search' => "brand_name:{$query}*+OR+generic_name:{$query}*",
                    'limit'  => 15,
                ]);

                if ($fdaResponse->successful()) {
                    $results = $fdaResponse->json('results') ?? [];

                    foreach ($results as $item) {
                        $brandName   = $item['brand_name'] ?? $item['generic_name'] ?? null;
                        $genericName = $item['generic_name'] ?? $brandName;
                        $dosageForm  = $item['dosage_form'] ?? '';

                        if ($brandName) {
                            $fullBrandName = trim($brandName . ($dosageForm ? " ($dosageForm)" : ''));

                            $recordsToInsert[] = [
                                'commercial_name_ar' => $fullBrandName,
                                'commercial_name_en' => $fullBrandName,
                                'scientific_name'    => $genericName,
                                'price_egp'          => 0,
                                'created_at'         => $now,
                                'updated_at'         => $now,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('OpenFDA API Error: ' . $e->getMessage());
            }
        }

        // حفظ النتائج الجديدة في جدول medicines
        if (!empty($recordsToInsert)) {
            try {
                // إزالة أي تكرار في الأسماء قبل الإدخال
                $uniqueRecords = collect($recordsToInsert)
                    ->unique('commercial_name_en')
                    ->values()
                    ->all();

                DB::table('medicines')->insertOrIgnore($uniqueRecords);
            } catch (\Throwable $e) {
                Log::error('Database Medicine Insert Error: ' . $e->getMessage());
            }
        }
    }
}
