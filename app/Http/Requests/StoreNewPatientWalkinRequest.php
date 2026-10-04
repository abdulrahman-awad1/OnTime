<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNewPatientWalkinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // بيانات المريض الجديد
            'name'          => 'required|string|max:255',
            'phone'         => 'required|string|max:20|unique:users,phone', // 👈 إجبار عدم التكرار
            'email'         => 'nullable|email|unique:users,email',
            'date_of_birth' => 'nullable|date',
            'gender'        => 'nullable|in:male,female',
            'address'       => 'nullable|string|max:500',

            // بيانات الحجز والدفع
            'time_slot_id'  => 'required|integer|exists:time_slots,id',
            'visit_type'    => 'required|string|in:checkup,consultation',
            'pay_method'    => 'required|string|in:cash',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'رقم الهاتف هذا مسجل بالفعل لمريض آخر، يرجى البحث عنه في قائمة المرضى الموجودين.',
        ];
    }
}
