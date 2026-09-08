<?php

namespace App\Services;

use App\Models\Payment;

class PaymentSummaryService
{
    public function summary(): array
    {
        return [
            'total_collected' => (float) Payment::where('status', 'paid')->sum('amount'),

            'pending_cash' => (float) Payment::where('status', 'pending')
                ->where('method', 'cash')
                ->sum('amount'),

            'refunded' => (float) Payment::where('status', 'refunded')->sum('amount'),

            'today_revenue' => (float) Payment::where('status', 'paid')
                ->whereDate('updated_at', now()->toDateString())
                ->sum('amount'),
        ];
    }
}
