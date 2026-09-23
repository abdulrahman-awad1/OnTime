<?php

use App\Http\Controllers\DoctorController;
use App\Http\Controllers\MedicineController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('doctor')->group(function () {
    Route::get('/current-appointment', [DoctorController::class, 'currentAppointment']);

    Route::post('/appointments/{appointment}/complete', [DoctorController::class, 'completeConsultation']);

    Route::get('/patients/{patient}/history', [DoctorController::class, 'patientHistory']);
    Route::post('/appointments/{appointment}/medical-record', [DoctorController::class, 'storeMedicalRecord']);
    Route::post('/medical-records/{medicalRecord}/prescription', [DoctorController::class, 'storePrescription']);
    Route::post('/medical-records/{medicalRecord}/attachments', [DoctorController::class, 'storeAttachment']);

    Route::get('/medicines/search', [MedicineController::class, 'search']);
});
