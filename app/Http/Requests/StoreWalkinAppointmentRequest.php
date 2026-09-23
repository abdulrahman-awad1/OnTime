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
            // لو المريض موجود بالفعل، تبعت الـ id بس
            'patient_id' => ['nullable', 'integer', 'exists:users,id'],

            // لو مريض جديد، البيانات دي مطلوبة بدل الـ id
            'patient_name' => ['required_without:patient_id', 'string', 'max:255'],
            'patient_phone' => ['required_without:patient_id', 'string', 'max:20'],
            'patient_email' => ['nullable', 'email'],

            // بيانات البروفايل الأساسية - إجبارية بس لو المريض جديد
            // (date_of_birth و gender إجباريين في جدول profiles نفسه)
            'patient_date_of_birth' => ['required_without:patient_id', 'date', 'before:today'],
            'patient_gender' => ['required_without:patient_id', 'in:male,female'],
            'patient_address' => ['nullable', 'string', 'max:1000'],

            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'visit_type' => ['required', Rule::in(['checkup', 'consultation'])],
            'pay_method' => ['required', Rule::in(['card', 'wallet', 'fawry', 'cash'])],
        ];
    }
}
