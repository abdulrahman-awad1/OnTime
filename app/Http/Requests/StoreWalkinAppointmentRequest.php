<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalkinAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // الحماية شغالة عن طريق middleware الـ admin على مستوى الـ route
    }

    public function rules(): array
    {
        return [
            // لو المريض موجود بالفعل، تبعت الـ id بس
            'patient_id' => ['nullable', 'integer', 'exists:users,id'],

            // لو مريض جديد، البيانات دي مطلوبة بدل الـ id
            'patient_name' => ['required_without:patient_id', 'string', 'max:255'],
            'patient_phone' => ['required_without:patient_id', 'string', 'max:20'],
            'patient_email' => ['nullable', 'email'],

            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'visit_type' => ['required', Rule::in(['checkup', 'consultation'])],
            'pay_method' => ['required', Rule::in(['card', 'wallet', 'fawry', 'cash'])],
        ];
    }
}
