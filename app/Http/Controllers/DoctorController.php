<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicalAttachmentRequest;
use App\Http\Requests\StoreMedicalRecordRequest;
use App\Http\Requests\StorePrescriptionRequest;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use App\Services\DoctorService;
use App\trait\ApiResponse;
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
            $updated = $this->doctorService->completeConsultation($appointment);

            return $this->returnData('appointment', $updated, 'تم إكمال الكشف بنجاح');
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
        $record = $this->doctorService->addMedicalRecord($appointment, $request->validated());

        return $this->returnData('medical_record', $record, 'تم حفظ التشخيص');
    }

    public function storePrescription(StorePrescriptionRequest $request, MedicalRecord $medicalRecord)
    {
        $prescription = $this->doctorService->createPrescription(
            $medicalRecord,
            $request->validated('items'),
            $request->validated('notes')
        );

        return $this->returnData('prescription', $prescription, 'تم حفظ الروشتة');
    }

    public function storeAttachment(StoreMedicalAttachmentRequest $request, MedicalRecord $medicalRecord)
    {
        $attachment = $this->doctorService->uploadAttachment(
            $medicalRecord,
            $request->file('file'),
            $request->validated('type')
        );

        return $this->returnData('attachment', [
            'id' => $attachment->id,
            'type' => $attachment->type,
            'url' => $attachment->url(),
            'original_name' => $attachment->original_name,
        ], 'تم رفع الملف');
    }
}
