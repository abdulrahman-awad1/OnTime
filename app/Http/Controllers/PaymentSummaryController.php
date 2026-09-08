<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\PaymentSummaryService;
use App\trait\ApiResponse;

class PaymentSummaryController extends Controller
{
    use ApiResponse;

    public function __construct(private PaymentSummaryService $paymentSummaryService) {}

    // GET /api/admin/payments/summary
    public function summary()
    {
        return $this->returnData('summary', $this->paymentSummaryService->summary());
    }
}
