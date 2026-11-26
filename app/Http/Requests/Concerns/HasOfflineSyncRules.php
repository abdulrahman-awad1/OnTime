<?php

namespace App\Http\Requests\Concerns;

trait HasOfflineSyncRules
{
    /**
     * client_uuid : optional idempotency key (UUID v4 generated on the device).
     * recorded_at : optional ISO-8601 time the record was written offline.
     *               Allowed window: last 7 days, up to 1 day in the future.
     *               Future values (device clock ahead) are accepted and clamped to now()
     *               by DoctorService::recordedAt(), so a wrong clock never blocks a save.
     */
    protected function offlineSyncRules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'recorded_at' => [
                'nullable',
                'date',
                'after_or_equal:' . now()->subDays(7)->toIso8601String(),
                'before_or_equal:' . now()->addDay()->toIso8601String(),
            ],
        ];
    }
}
