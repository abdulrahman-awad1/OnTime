<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\PaymobService;
use App\trait\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(protected PaymobService $paymob) {}

    public function pay(PaymentRequest $request)
    {
        $appointment = Appointment::with(['timeSlot.clinicLocation', 'patient'])
            ->findOrFail($request->appointment_id);

        if ($appointment->user_id !== $request->user()->id) {
            return $this->returnError('E403', 'غير مصرح لك', 403);
        }

        // ⚠️ الإصلاح: لازم الحجز يكون لسه confirmed - مش ملغي ولا أي حالة تانية
        if ($appointment->status !== 'confirmed') {
            return $this->returnError('E104', 'الحجز ده ملغي أو منتهي، مينفعش تدفع عليه.', 400);
        }

        if ($appointment->payment_status === 'paid') {
            return $this->returnError('E102', 'الحجز ده مدفوع بالفعل', 400);
        }

        if ($request->pay_method === 'cash') {
            return $this->payCash($appointment);
        }

        return $this->payOnline($appointment, $request);
    }

    private function payCash(Appointment $appointment)
    {
        $payment = DB::transaction(function () use ($appointment) {
            return Payment::create([
                'appointment_id' => $appointment->id,
                'user_id' => $appointment->user_id,
                'provider' => 'cash',
                'amount' => $appointment->price(),
                'method' => 'cash',
                'status' => 'pending',
            ]);
        });

        return $this->returnData('payment', [
            'method' => 'cash',
            'amount' => $payment->amount,
            'message' => 'تم تأكيد حجزك، وهتدفع المبلغ نقدًا وقت وصولك للعيادة.',
        ], 'تم تأكيد الحجز', 201);
    }

    private function payOnline(Appointment $appointment, PaymentRequest $request)
    {
        try {
            $integrationId = $this->paymob->resolveIntegration($request->pay_method);
            $token = $this->paymob->authenticate();
            $paymobOrderId = $this->paymob->createOrder($token, $appointment);

            $paymentKey = $this->paymob->generatePaymentKey(
                $token,
                $paymobOrderId,
                $appointment,
                $this->paymob->buildBillingData($appointment),
                $integrationId
            );

            $payment = DB::transaction(function () use ($appointment, $request, $paymobOrderId) {
                return Payment::create([
                    'appointment_id' => $appointment->id,
                    'user_id' => $appointment->user_id,
                    'provider' => 'paymob',
                    'amount' => $appointment->price(),
                    'method' => $request->pay_method,
                    'status' => 'pending',
                    'paymob_order_id' => $paymobOrderId,
                ]);
            });

            $result = $this->paymob->dispatchPayment($paymentKey, $payment->method, $appointment);

            return $this->returnData('payment', $result, 'كمّل الدفع من الرابط ده');

        } catch (Exception $e) {
            Log::error('Paymob Payment Initialization Failed: '.$e->getMessage(), [
                'appointment_id' => $appointment->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->returnError('E502', 'حصلت مشكلة مع بوابة الدفع، حاول تاني كمان شوية.', 502);
        }
    }

    public function callback(Request $request)
    {
        Log::info('Paymob Webhook Received', $request->all());

        if (! $this->paymob->verifyHmac($request->all())) {
            Log::warning('Paymob Webhook HMAC Verification Failed', ['payload' => $request->all()]);

            return response()->json(['message' => 'Invalid HMAC signature'], 401);
        }

        $paymobOrderId = $request->input('obj.order.id');
        $transactionId = $request->input('obj.id');
        $isSuccess = filter_var($request->input('obj.success'), FILTER_VALIDATE_BOOLEAN);

        $payment = Payment::where('paymob_order_id', $paymobOrderId)->first();

        if (! $payment) {
            Log::error('Paymob Webhook: Payment Record Not Found', ['paymob_order_id' => $paymobOrderId]);

            return response()->json(['message' => 'Payment record not found'], 404);
        }

        if ($payment->status === 'paid') {
            return response()->json(['message' => 'Webhook already processed'], 200);
        }

        try {
            DB::transaction(function () use ($payment, $isSuccess, $transactionId, $request) {
                if ($isSuccess) {
                    $payment->update([
                        'status' => 'paid',
                        'transaction_id' => $transactionId,
                        'raw_response' => $request->all(),
                    ]);
                    $payment->appointment->update(['payment_status' => 'paid']);
                } else {
                    $payment->update([
                        'status' => 'failed',
                        'transaction_id' => $transactionId,
                        'raw_response' => $request->all(),
                    ]);
                    $payment->appointment->update(['payment_status' => 'failed']);
                }
            });

            return response()->json(['message' => 'Webhook processed successfully'], 200);

        } catch (Exception $e) {
            Log::error('Paymob Webhook Processing Error: '.$e->getMessage());

            return response()->json(['message' => 'Internal Server Error'], 500);
        }
    }

    public function redirect(Request $request)
    {
        $isSuccess = filter_var($request->get('success'), FILTER_VALIDATE_BOOLEAN);

        return response()->json([
            'success' => $isSuccess,
            'message' => $isSuccess ? 'تم الدفع بنجاح!' : 'فشلت عملية الدفع، حاول تاني.',
        ]);
    }
}
