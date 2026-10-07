<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkinAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_id'   => 'required|integer|exists:users,id',
            'time_slot_id' => 'required|integer|exists:time_slots,id',
            'visit_type'   => 'required|string|in:checkup,consultation',
            'pay_method'   => 'nullable|string|in:cash',
        ];

    }
}
