<?php


use App\Http\Controllers\DoctorPatientProfileController;
use App\Http\Controllers\user\ProfileController;

Route::middleware('auth:sanctum')->group(function () {

    // Patient Profile
    Route::prefix('patient/profile')->group(function () {

        Route::get('/', [
            ProfileController::class,
            'show'
        ]);

        Route::post('/', [
            ProfileController::class,
            'store'
        ]);

        Route::put('/', [
            ProfileController::class,
            'update'
        ]);
    });


    // Doctor - Patient Medical Information
    Route::put(
        '/doctor/patients/{patientProfile}/medical-info',
        [
            DoctorPatientProfileController::class,
            'updateMedicalInfo'
        ]
    );
});
