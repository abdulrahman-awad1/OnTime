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
        $today = Carbon::today();

        $todayAppointmentsCount = Appointment::whereDate('created_at', $today)->count();

        $unpaidAppointmentsCount = Appointment::whereDate('created_at', $today)
            ->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                    ->orWhereDoesntHave('payment', function ($q) {
                        $q->where('status', 'paid');
                    });
            })->count();

        $todayRevenue = Payment::whereDate('created_at', $today)
            ->where('status', 'paid')
            ->sum('amount');

        $appointmentsByStatus = [
            'confirmed'   => Appointment::whereDate('created_at', $today)->where('status', 'confirmed')->count(),
            'in_progress' => Appointment::whereDate('created_at', $today)->where('status', 'in_progress')->count(),
            'completed'   => Appointment::whereDate('created_at', $today)->where('status', 'completed')->count(),
            'cancelled'   => Appointment::whereDate('created_at', $today)->where('status', 'cancelled')->count(),
        ];

        return [
            'date'                       => $today->toDateString(),
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
