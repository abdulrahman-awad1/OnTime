<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfflineMedicalRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_uuid' => $this->client_uuid,
            'appointment_id' => $this->appointment_id,
            'record_type' => $this->record_type,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'notes' => $this->notes,
            // Rows created before this feature have no recorded_at: fall back to created_at.
            'recorded_at' => ($this->recorded_at ?? $this->created_at)?->toIso8601String(),
            'prescriptions' => $this->prescriptions->map(fn ($p) => [
                'id' => $p->id,
                'client_uuid' => $p->client_uuid,
                'notes' => $p->notes,
                'recorded_at' => ($p->recorded_at ?? $p->created_at)?->toIso8601String(),
                'items' => $p->items->map->only(['id', 'medicine_name', 'dosage', 'frequency', 'duration'])->values(),
            ])->values(),
            // Metadata + URL only; files are fetched online on demand.
            'attachments' => $this->attachments->map(fn ($a) => [
                'id' => $a->id,
                'client_uuid' => $a->client_uuid,
                'type' => $a->type,
                'original_name' => $a->original_name,
                'url' => $a->url(),
                'recorded_at' => ($a->recorded_at ?? $a->created_at)?->toIso8601String(),
            ])->values(),
        ];
    }
}
