<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendOtp(string $phone, string $otpCode, string $userName = 'عميلنا العزيز'): bool
    {
        $phoneId      = config('services.whatsapp.phone_id');
        $token        = config('services.whatsapp.token');
        $version      = config('services.whatsapp.version', 'v21.0');
        $templateName = config('services.whatsapp.template_name', 'ontime_request_verify');

        if (empty($phoneId) || empty($token)) {
            Log::error('WhatsApp credentials are missing in configuration.');
            return false;
        }

        // تنسيق الرقم المصري ليصبح 2010xxxxxxxx حتى لو أرسله المستخدم بصفر أو بدون صفر
        $internationalPhone = '20' . ltrim($phone, '0');
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $internationalPhone,
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => 'ar'],
                'components' => [
                    [
                        'type'       => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $userName],        // {{1}} الاسم
                            ['type' => 'text', 'text' => $phone],           // {{2}} رقم الهاتف
                            ['type' => 'text', 'text' => $otpCode], // {{3}} كود التحقق
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->post($url, $payload);

            if ($response->successful()) {
                return true;
            }

            Log::error('Meta WhatsApp API Error: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('WhatsApp Exception: ' . $e->getMessage());
            return false;
        }
    }
}
