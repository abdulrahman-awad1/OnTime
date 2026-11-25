<?php

namespace App\Http\Controllers;

use App\Exceptions\ClientUuidConflictException;
use App\Http\Requests\StoreMedicalAttachmentRequest;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\StorePrescriptionRequest;
use App\Http\Resources\OfflineAppointmentResource;
use App\Http\Resources\OfflinePatientResource;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Services\DoctorService;
use App\trait\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    use ApiResponse;

    public function __construct(private DoctorService $doctorService) {}

    public function currentAppointment(Request $request)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        $appointment = $this->doctorService->currentAppointment();

        if (! $appointment) {
            return $this->returnData('appointment', null, 'مفيش موعد مفعّل دلوقتي');
        }

        return $this->returnData('appointment', $appointment);
    }

    // POST /doctor/appointments/{appointment}/complete
    public function completeConsultation(Request $request, Appointment $appointment)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        try {
            $alreadyCompleted = $appointment->status === 'completed';
            $updated = $this->doctorService->completeConsultation($appointment);

            return $this->returnData(
                'appointment',
                $updated,
                $alreadyCompleted ? 'الكشف مكتمل بالفعل' : 'تم إكمال الكشف بنجاح',
                200,
                ['replayed' => $alreadyCompleted]
            );
        } catch (\RuntimeException $e) {
            return $this->returnError('E108', $e->getMessage(), 422);
        }
    }

    public function patientHistory(Request $request, int $patient)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        return $this->returnData('history', $this->doctorService->patientHistory($patient));
    }

    public function storeMedicalRecord(StoreMedicalRecordRequest $request, Appointment $appointment)
    {
        try {
            $record = $this->doctorService->addMedicalRecord($appointment, $request->validated());
        } catch (ClientUuidConflictException $e) {
            return $this->returnError('E409', $e->getMessage(), 409);
        }

        return $this->returnData('medical_record', $record, 'تم حفظ التشخيص', ...$this->syncMeta($record));
    }

    // Also serves POST /doctor/medical-records/uuid/{medicalRecord:client_uuid}/prescription
    public function storePrescription(StorePrescriptionRequest $request, MedicalRecord $medicalRecord)
    {
        try {
            $prescription = $this->doctorService->createPrescription(
                $medicalRecord,
                $request->validated('items'),
                $request->validated('notes'),
                $request->safe()->only(['client_uuid', 'recorded_at'])
            );
        } catch (ClientUuidConflictException $e) {
            return $this->returnError('E409', $e->getMessage(), 409);
        }

        return $this->returnData('prescription', $prescription, 'تم حفظ الروشتة', ...$this->syncMeta($prescription));
    }

    // Also serves POST /doctor/medical-records/uuid/{medicalRecord:client_uuid}/attachments
    public function storeAttachment(StoreMedicalAttachmentRequest $request, MedicalRecord $medicalRecord)
    {
        try {
            $attachment = $this->doctorService->uploadAttachment(
                $medicalRecord,
                $request->file('file'),
                $request->validated('type'),
                $request->safe()->only(['client_uuid', 'recorded_at'])
            );
        } catch (ClientUuidConflictException $e) {
            return $this->returnError('E409', $e->getMessage(), 409);
        }

        return $this->returnData('attachment', [
            'id' => $attachment->id,
            'client_uuid' => $attachment->client_uuid,
            'type' => $attachment->type,
            'url' => $attachment->url(),
            'original_name' => $attachment->original_name,
            'recorded_at' => $attachment->recorded_at?->toIso8601String(),
        ], 'تم رفع الملف', ...$this->syncMeta($attachment));
    }

    // GET /doctor/offline-pack
    public function offlinePack(Request $request)
    {
        if ($request->user()->role !== 'doctor') {
            abort(403, 'غير مصرح لك');
        }

        $pack = $this->doctorService->offlinePack();

        return $this->returnData('pack', [
            'generated_at' => $pack['generated_at'],
            'valid_until' => $pack['valid_until'],
            'dates' => $pack['dates'],
            'appointments' => OfflineAppointmentResource::collection($pack['appointments']),
            'patients' => OfflinePatientResource::collection($pack['patients']),
        ]);
    }

    /** 201 + replayed=false for a new row, 200 + replayed=true for an idempotent retry. */
    private function syncMeta(Model $model): array
    {
        return [
            'httpCode' => $model->wasRecentlyCreated ? 201 : 200,
            'extra' => ['replayed' => ! $model->wasRecentlyCreated],
        ];
    }
}
