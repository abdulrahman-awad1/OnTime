<?php

namespace App\Services;

use App\Events\AppointmentCompleted;
use App\Exceptions\ClientUuidConflictException;
use App\Models\Appointment;
use App\Models\MedicalAttachment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorService
{
    public function currentAppointment(): ?Appointment
    {
        return Appointment::with(['patient', 'timeSlot.clinicLocation'])
            ->where('status', 'in_progress')
            ->first();
    }

    /**
     * الدكتور بيدوس "إكمال الكشف" - بيتحول الحجز لـ completed،
     * وبيتبث حدث لحظي عشان الأسيستانت يعرف على طول ويفعّل اللي بعده.
     */
    public function completeConsultation(Appointment $appointment): Appointment
    {
        // Idempotent: a retried "complete" on an already-completed visit is a no-op (no second event).
        if ($appointment->status === 'completed') {
            return $appointment->fresh('timeSlot.clinicLocation');
        }

        if ($appointment->status !== 'in_progress') {
            throw new \RuntimeException('الحجز ده مش هو الموعد الحالي المفعّل دلوقتي.');
        }

        $appointment->update(['status' => 'completed']);

        AppointmentCompleted::dispatch($appointment->id);

        return $appointment->fresh('timeSlot.clinicLocation');
    }

    /**
     * Idempotent: same client_uuid => returns the existing record ($record->wasRecentlyCreated === false).
     *
     * @throws ClientUuidConflictException
     */
    public function addMedicalRecord(Appointment $appointment, array $data): MedicalRecord
    {
        $uuid = $data['client_uuid'] ?? null;

        if ($existing = $this->findByClientUuid(MedicalRecord::class, $uuid, 'appointment_id', $appointment->id)) {
            return $existing;
        }

        try {
            return MedicalRecord::create([
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->user_id,
                'record_type' => $data['record_type'],
                'diagnosis' => $data['diagnosis'],
                'treatment' => $data['treatment'] ?? null,
                'notes' => $data['notes'] ?? null,
                'client_uuid' => $uuid,
                'recorded_at' => $this->recordedAt($data['recorded_at'] ?? null),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Two retries raced: the other one won, return its row.
            return $this->findByClientUuid(MedicalRecord::class, $uuid, 'appointment_id', $appointment->id) ?? throw $e;
        }
    }

    /**
     * @param  array{client_uuid?: ?string, recorded_at?: ?string}  $sync
     *
     * @throws ClientUuidConflictException
     */
    public function createPrescription(MedicalRecord $medicalRecord, array $items, ?string $notes, array $sync = []): Prescription
    {
        $uuid = $sync['client_uuid'] ?? null;

        if ($existing = $this->findByClientUuid(Prescription::class, $uuid, 'medical_record_id', $medicalRecord->id)) {
            return $existing->load('items');
        }

        try {
            return DB::transaction(function () use ($medicalRecord, $items, $notes, $uuid, $sync) {
                $prescription = Prescription::create([
                    'medical_record_id' => $medicalRecord->id,
                    'patient_id' => $medicalRecord->patient_id,
                    'notes' => $notes,
                    'client_uuid' => $uuid,
                    'recorded_at' => $this->recordedAt($sync['recorded_at'] ?? null),
                ]);

                foreach ($items as $item) {
                    $prescription->items()->create([
                        'medicine_name' => $item['medicine_name'],
                        'dosage' => $item['dosage'] ?? null,
                        'frequency' => $item['frequency'] ?? null,
                        'duration' => $item['duration'] ?? null,
                    ]);
                }

                return $prescription->load('items');
            });
        } catch (UniqueConstraintViolationException $e) {
            $existing = $this->findByClientUuid(Prescription::class, $uuid, 'medical_record_id', $medicalRecord->id);

            return $existing?->load('items') ?? throw $e;
        }
    }

    /**
     * The UUID is checked BEFORE the file is stored, so retries never leave orphan files.
     *
     * @param  array{client_uuid?: ?string, recorded_at?: ?string}  $sync
     *
     * @throws ClientUuidConflictException
     */
    public function uploadAttachment(MedicalRecord $medicalRecord, UploadedFile $file, string $type, array $sync = []): MedicalAttachment
    {
        $uuid = $sync['client_uuid'] ?? null;

        if ($existing = $this->findByClientUuid(MedicalAttachment::class, $uuid, 'medical_record_id', $medicalRecord->id)) {
            return $existing;
        }

        $path = $file->store('medical-attachments', 'public');

        try {
            return MedicalAttachment::create([
                'medical_record_id' => $medicalRecord->id,
                'patient_id' => $medicalRecord->patient_id,
                'type' => $type,
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'client_uuid' => $uuid,
                'recorded_at' => $this->recordedAt($sync['recorded_at'] ?? null),
            ]);
        } catch (UniqueConstraintViolationException $e) {
            Storage::disk('public')->delete($path); // the parallel request already stored its copy

            return $this->findByClientUuid(MedicalAttachment::class, $uuid, 'medical_record_id', $medicalRecord->id) ?? throw $e;
        }
    }

    /**
     * Today's + tomorrow's visits with a normalized patient list (profile + last 5 records).
     * Normalized (appointments[] + patients[]) so it maps 1:1 to IndexedDB tables
     * and a patient with two visits is sent only once.
     */
    public function offlinePack(): array
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        $appointments = Appointment::query()
            ->select(['id', 'user_id', 'time_slot_id', 'visit_type', 'status', 'payment_status', 'notes'])
            ->whereIn('status', ['confirmed', 'in_progress', 'completed'])
            ->whereHas('timeSlot', fn ($q) => $q->whereIn('date', [$today, $tomorrow]))
            ->with([
                'timeSlot:id,clinic_location_id,date,start_time,end_time',
                'timeSlot.clinicLocation:id,name',
            ])
            ->get()
            ->sortBy(fn ($a) => $a->timeSlot->date . ' ' . $a->timeSlot->start_time)
            ->values();

        $patients = User::query()
            ->select(['id', 'name', 'phone'])
            ->whereIn('id', $appointments->pluck('user_id')->unique())
            ->with([
                'profile:id,user_id,date_of_birth,gender,blood_type,has_hypertension,has_diabetes,has_heart_disease,has_previous_stroke,medical_notes',
                // Laravel 11+ applies limit() per patient (window function) => last 5 records each.
                'medicalRecords' => fn ($q) => $q
                    ->select(['id', 'client_uuid', 'appointment_id', 'patient_id', 'record_type', 'diagnosis', 'treatment', 'notes', 'recorded_at', 'created_at'])
                    ->orderByRaw('COALESCE(recorded_at, created_at) DESC')
                    ->limit(5),
                'medicalRecords.prescriptions:id,client_uuid,medical_record_id,notes,recorded_at,created_at',
                'medicalRecords.prescriptions.items:id,prescription_id,medicine_name,dosage,frequency,duration',
                'medicalRecords.attachments:id,client_uuid,medical_record_id,type,file_path,original_name,recorded_at,created_at',
            ])
            ->get();

        return [
            'generated_at' => now()->toIso8601String(),
            'valid_until' => now()->addDay()->endOfDay()->toIso8601String(),
            'dates' => [$today, $tomorrow],
            'appointments' => $appointments,
            'patients' => $patients,
        ];
    }

    /**
     * Returns the row owned by this client_uuid, or null.
     * Throws if the UUID was already used under a different parent (=> HTTP 409).
     */
    private function findByClientUuid(string $modelClass, ?string $uuid, string $parentKey, int $parentId): ?Model
    {
        if (! $uuid) {
            return null;
        }

        $found = $modelClass::where('client_uuid', $uuid)->first();

        if ($found && (int) $found->{$parentKey} !== $parentId) {
            throw new ClientUuidConflictException('client_uuid مستخدم بالفعل مع سجل آخر.');
        }

        return $found;
    }

    /** Device time (any offset) normalized to UTC; defaults to now(). */
    private function recordedAt(?string $value): Carbon
    {
        return $value ? Carbon::parse($value)->utc() : now();
    }

    public function patientHistory(int $patientId): array
    {
        return [
            'medical_records' => MedicalRecord::where('patient_id', $patientId)
                ->with(['prescriptions.items', 'attachments'])
                ->latest()
                ->get(),
        ];
    }
}
