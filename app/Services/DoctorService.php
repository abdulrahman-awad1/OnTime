<?php

namespace App\Services;

use App\Events\AppointmentCompleted;
use App\Models\Appointment;
use App\Models\MedicalAttachment;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

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
        if ($appointment->status !== 'in_progress') {
            throw new \RuntimeException('الحجز ده مش هو الموعد الحالي المفعّل دلوقتي.');
        }

        $appointment->update(['status' => 'completed']);

        AppointmentCompleted::dispatch($appointment->id);

        return $appointment->fresh('timeSlot.clinicLocation');
    }

    public function addMedicalRecord(Appointment $appointment, array $data): MedicalRecord
    {
        return MedicalRecord::create([
            'appointment_id' => $appointment->id,
            'patient_id' => $appointment->user_id,
            'record_type' => $data['record_type'],
            'diagnosis' => $data['diagnosis'],
            'treatment' => $data['treatment'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function createPrescription(MedicalRecord $medicalRecord, array $items, ?string $notes): Prescription
    {
        return DB::transaction(function () use ($medicalRecord, $items, $notes) {
            $prescription = Prescription::create([
                'medical_record_id' => $medicalRecord->id,
                'patient_id' => $medicalRecord->patient_id,
                'notes' => $notes,
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
    }

    public function uploadAttachment(MedicalRecord $medicalRecord, UploadedFile $file, string $type): MedicalAttachment
    {
        $path = $file->store('medical-attachments', 'public');

        return MedicalAttachment::create([
            'medical_record_id' => $medicalRecord->id,
            'patient_id' => $medicalRecord->patient_id,
            'type' => $type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);
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
