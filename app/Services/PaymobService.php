<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class PaymobService
{
    protected string $baseUrl = 'https://accept.paymob.com/api';

    public function resolveIntegration(string $method): int
    {
        $integrationId = match ($method) {
            'card' => config('services.paymob.integration_card'),
            'wallet' => config('services.paymob.integration_wallet'),
            'fawry' => config('services.paymob.integration_fawry'),
            default => throw new InvalidArgumentException('Invalid payment method: '.$method),
        };

        if (empty($integrationId)) {
            throw new RuntimeException("Paymob integration ID for [{$method}] is missing in .env or config.");
        }

        return (int) $integrationId;
    }

    public function authenticate(): string
    {
        $apiKey = config('services.paymob.api_key');
        if (empty($apiKey)) {
            throw new RuntimeException('Paymob API Key is missing in .env');
        }

        $response = Http::post($this->baseUrl.'/auth/tokens', [
            'api_key' => $apiKey,
        ])->throw();

        return $response->json('token');
    }

    public function createOrder(string $token, Appointment $appointment): string
    {
        $response = Http::post($this->baseUrl.'/ecommerce/orders', [
            'auth_token' => $token,
            'delivery_needed' => false,
            'amount_cents' => $this->toCents($appointment->price()),
            'currency' => 'EGP',
            'items' => [],
        ])->throw();

        return (string) $response->json('id');
    }

    public function generatePaymentKey(
        string $token,
        string $paymobOrderId,
        Appointment $appointment,
        array $billingData,
        int $integrationId
    ): string {
        $response = Http::post($this->baseUrl.'/acceptance/payment_keys', [
            'auth_token' => $token,
            'amount_cents' => $this->toCents($appointment->price()),
            'expiration' => 3600,
            'order_id' => $paymobOrderId,
            'billing_data' => $billingData,
            'currency' => 'EGP',
            'integration_id' => $integrationId,
            'redirect_url' => route('redirect'),
        ])->throw();

        return $response->json('token');
    }

    public function buildIframeUrl(string $paymentToken, string $method): string
    {
        $iframeId = match ($method) {
            'card' => config('services.paymob.iframe_id_card'),
            'wallet' => config('services.paymob.iframe_id_wallet'),
            default => config('services.paymob.iframe_id_card'),
        };

        if (empty($iframeId)) {
            throw new RuntimeException("Paymob iframe ID for [{$method}] is missing in .env or config.");
        }

        return "https://accept.paymob.com/api/acceptance/iframes/{$iframeId}?payment_token={$paymentToken}";
    }

    public function payWallet(string $paymentKey, string $phone): array
    {
        return Http::post($this->baseUrl.'/acceptance/payments/pay', [
            'source' => ['identifier' => $phone, 'subtype' => 'WALLET'],
            'payment_token' => $paymentKey,
        ])->throw()->json();
    }

    public function payFawry(string $paymentKey, string $phone): array
    {
        return Http::post($this->baseUrl.'/acceptance/payments/pay', [
            'source' => ['identifier' => $phone, 'subtype' => 'AGGREGATOR'],
            'payment_token' => $paymentKey,
        ])->throw()->json();
    }

    public function dispatchPayment(string $paymentKey, string $method, Appointment $appointment): array
    {
        $phone = $this->formatPhoneNumber($appointment->patient?->phone);

        return match ($method) {
            'wallet' => ['wallet' => $this->payWallet($paymentKey, $phone)],
            'fawry' => ['fawry' => $this->payFawry($paymentKey, $phone)],
            default => ['url' => $this->buildIframeUrl($paymentKey, $method)],
        };
    }

    public function buildBillingData(Appointment $appointment): array
    {
        $patient = $appointment->patient;
        $nameParts = explode(' ', trim($patient?->name ?? 'Patient Guest'));
        $firstName = $nameParts[0] ?? 'Patient';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : 'Guest';

        return [
            'apartment' => 'NA',
            'email' => $patient?->email ?? 'guest@mail.com',
            'floor' => 'NA',
            'street' => 'NA',
            'building' => 'NA',
            'city' => 'Cairo',
            'country' => 'EG',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => $this->formatPhoneNumber($patient?->phone),
            'shipping_method' => 'PKG',
            'postal_code' => 'NA',
            'state' => 'NA',
        ];
    }

    public function refund(string $token, string $transactionId, float $amount): array
    {
        return Http::post($this->baseUrl.'/acceptance/void_refund/refund', [
            'auth_token' => $token,
            'transaction_id' => $transactionId,
            'amount_cents' => $this->toCents($amount),
        ])->throw()->json();
    }

    public function verifyHmac(array $payload): bool
    {
        $objData = Arr::get($payload, 'obj');

        if (! $objData) {
            return false;
        }

        $fields = [
            'amount_cents', 'created_at', 'currency', 'error_occured',
            'has_parent_transaction', 'id', 'integration_id', 'is_3d_secure',
            'is_auth', 'is_capture', 'is_refunded', 'is_standalone_payment',
            'is_voided', 'order.id', 'owner', 'pending', 'source_data.pan',
            'source_data.sub_type', 'source_data.type', 'success',
        ];

        $message = collect($fields)
            ->map(function ($field) use ($objData) {
                $value = Arr::get($objData, $field, '');

                return is_bool($value) ? ($value ? 'true' : 'false') : $value;
            })
            ->implode('');

        $calculatedHmac = hash_hmac('sha512', $message, config('services.paymob.hmac_secret', ''));
        $receivedHmac = Arr::get($payload, 'hmac', '');

        return hash_equals($calculatedHmac, $receivedHmac);
    }

    private function toCents(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function formatPhoneNumber(?string $phone): string
    {
        if (! $phone) {
            return '01000000000';
        }

        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        return $cleaned ?: '01000000000';
    }
}
