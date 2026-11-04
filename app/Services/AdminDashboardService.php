<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminDashboardService
{
    /**
     * حساب إحصائيات اليوم
     */
    public function getTodayStats(): array
    {
        $today = Carbon::today()->toDateString();

        // استعلام أساسي واحد يفلتر المواعيد حسب تاريخ الـ timeSlot لليوم
        $todayAppointmentsQuery = Appointment::whereHas('timeSlot', function ($query) use ($today) {
            $query->whereDate('date', $today);
        });

        // 1. إجمالي مواعيد اليوم
        $todayAppointmentsCount = (clone $todayAppointmentsQuery)->count();

        // 2. مواعيد اليوم غير المدفوعة
        $unpaidAppointmentsCount = (clone $todayAppointmentsQuery)
            ->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhereDoesntHave('payment', function ($q) {
                        $q->where('status', 'paid');
                    });
            })->count();

        // 3. تقسيم مواعيد اليوم حسب الحالة في استعلام واحد فقط بدلاً من 4 استعلامات
        $statusCounts = (clone $todayAppointmentsQuery)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $appointmentsByStatus = [
            'confirmed'   => $statusCounts->get('confirmed', 0),
            'in_progress' => $statusCounts->get('in_progress', 0),
            'completed'   => $statusCounts->get('completed', 0),
            'cancelled'   => $statusCounts->get('cancelled', 0),
        ];

        // 4. إيرادات اليوم (المدفوعات التي تمت اليوم فعلياً في الخزنة/Paymob)
        $todayRevenue = Payment::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->sum('amount');

        return [
            'date'                      => $today,
            'today_revenue'             => (float) $todayRevenue,
            'today_appointments_count'  => $todayAppointmentsCount,
            'unpaid_appointments_count' => $unpaidAppointmentsCount,
            'appointments_by_status'    => $appointmentsByStatus,
        ];
    }

    /**
     * حساب الإحصائيات العامة مع الفلترة
     */
    public function getGeneralStats(Request $request): array
    {
        $appointmentsQuery = Appointment::query();
        $paymentsQuery     = Payment::where('status', 'paid');

        if ($request->filled('year')) {
            $year = $request->input('year');
            $appointmentsQuery->whereYear('created_at', $year);
            $paymentsQuery->whereYear('created_at', $year);

            if ($request->filled('month')) {
                $month = $request->input('month');
                $appointmentsQuery->whereMonth('created_at', $month);
                $paymentsQuery->whereMonth('created_at', $month);
            }
        }

        return [
            'filters_applied' => [
                'year'  => $request->input('year', 'All'),
                'month' => $request->input('month', 'All'),
            ],
            'total_patients'     => User::where('role', 'patient')->count(),
            'total_appointments' => (clone $appointmentsQuery)->count(),
            'total_revenue'      => (float) (clone $paymentsQuery)->sum('amount'),
            'appointments_by_status' => [
                'confirmed'   => (clone $appointmentsQuery)->where('status', 'confirmed')->count(),
                'in_progress' => (clone $appointmentsQuery)->where('status', 'in_progress')->count(),
                'completed'   => (clone $appointmentsQuery)->where('status', 'completed')->count(),
                'cancelled'   => (clone $appointmentsQuery)->where('status', 'cancelled')->count(),
            ],
            'revenue_by_payment_method' => [
                'card'   => (float) (clone $paymentsQuery)->where('method', 'card')->sum('amount'),
                'wallet' => (float) (clone $paymentsQuery)->where('method', 'wallet')->sum('amount'),
                'cash'   => (float) (clone $paymentsQuery)->where('method', 'cash')->sum('amount'),
            ],
        ];
    }
}
