<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Resources\GeneralStatsResource;
use App\Http\Resources\TodayStatsResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AdminDashboardService;
use App\trait\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SummaryController extends Controller
{
    use ApiResponse;

    public function __construct(private AdminDashboardService $dashboardService) {}

    public function todayStats()
    {
        $stats = $this->dashboardService->getTodayStats();

        return $this->returnData('today_stats', new TodayStatsResource($stats), 'تم جلب إحصائيات اليوم بنجاح');
    }

    public function generalStats(Request $request)
    {
        $stats = $this->dashboardService->getGeneralStats($request);

        return $this->returnData('general_stats', new GeneralStatsResource($stats), 'تم جلب الإحصائيات العامة بنجاح');
    }
}
