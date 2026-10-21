<?php


use App\Http\Controllers\Admin\AdminClinicLocationController;

Route::middleware(['auth:sanctum','admin'])->prefix('admin')->group(function () {
    Route::put('/update-clinic/{clinicLocation}', [AdminClinicLocationController::class, 'update']);
    Route::delete('/delete-clinic/{clinicLocation}', [AdminClinicLocationController::class, 'destroy']);
    Route::post('/clinic-locations', [AdminClinicLocationController::class, 'store']);
    Route::get('/get-clinic-locations', [AdminClinicLocationController::class, 'index']);
    Route::get('/clinics/{clinic}/visit-types', [AdminClinicLocationController::class, 'getVisitTypes']);


});
