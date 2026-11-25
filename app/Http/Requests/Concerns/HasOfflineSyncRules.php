<?php

namespace App\Http\Requests\Concerns;

trait HasOfflineSyncRules
{
    /**
     * client_uuid : optional idempotency key (UUID v4 generated on the device).
     * recorded_at : optional ISO-8601 time the record was written offline.
     *               Allowed window: last 7 days, up to 5 minutes in the future (clock skew).
     */
    protected function offlineSyncRules(): array
    {
        return [
            'client_uuid' => ['nullable', 'uuid'],
            'recorded_at' => [
                'nullable',
                'date',
                'after_or_equal:' . now()->subDays(7)->toIso8601String(),
                'before_or_equal:' . now()->addMinutes(5)->toIso8601String(),
            ],
        ];
    }
}
