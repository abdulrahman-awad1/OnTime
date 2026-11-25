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

    /**
     * GET /doctor/medicines/offline
     * Full active list for offline autocomplete. Supports If-None-Match => 304.
     * The ETag changes when a medicine is added, deleted, edited, (de)activated.
     */
    public function offline(Request $request)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        $meta = Medicine::query()
            ->selectRaw('COUNT(*) AS total, SUM(is_active = 1) AS active, MAX(updated_at) AS last_update')
            ->first();

        $version = md5("{$meta->total}|{$meta->active}|{$meta->last_update}");
        $etag = '"meds-' . $version . '"';

        // Accept weak validators too (nginx turns ETags into W/"..." when gzipping).
        $clientTags = array_map(fn ($t) => preg_replace('/^W\//', '', $t), $request->getETags());

        if (in_array($etag, $clientTags, true)) {
            return response()->noContent(304)
                ->header('ETag', $etag)
                ->header('Cache-Control', 'private, no-cache');
        }

        $medicines = Medicine::where('is_active', true)
            ->orderBy('commercial_name_en')
            ->get(['id', 'commercial_name_en', 'commercial_name_ar', 'scientific_name', 'price_egp']);

        return $this->returnData('medicines', $medicines, 'success', 200, ['version' => $version])
            ->header('ETag', $etag)
            ->header('Cache-Control', 'private, no-cache');
    }
}
