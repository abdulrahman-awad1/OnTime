<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendOtp(string $phone, string $otpCode): bool
    {
        $phoneId = config('services.whatsapp.phone_id');
        $token   = config('services.whatsapp.token');
        $version = config('services.whatsapp.version', 'v21.0');

        if (empty($phoneId) || empty($token)) {
            Log::error('WhatsApp credentials are missing in configuration.');
            return false;
        }

        $internationalPhone = '2' . $phone;
        $url = "https://graph.facebook.com/{$version}/{$phoneId}/messages";

        // حالياً أثناء الاختبار يرسل نص مباشر (text)
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $internationalPhone,
            'type'              => 'text',
            'text'              => [
                'body' => "مرحباً بك في منصة *OnTime* 🏥\nكود الدخول الخاص بك هو: *{$otpCode}*\nصالح لمدة 10 دقائق."
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
