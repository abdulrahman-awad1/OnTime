<?php

use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a patient to book an available time slot', function () {

    // Arrange: مريض + موعد متاح
    $patient = User::factory()->create(); // role الافتراضي = patient
    $slot = TimeSlot::factory()->create(); // status الافتراضي = available

    // Act: نبعت طلب حجز، ومتظاهرين إن المريض ده مسجل دخول فعلاً
    $response = $this->actingAs($patient, 'sanctum')
        ->postJson('/api/appointments', [
            'time_slot_id' => $slot->id,
        ]);

    // Assert 1: الـ response نجح بـ 201 (Created)
    $response->assertStatus(201);

    // Assert 2: الموعد اتغيرت حالته لـ booked فعلاً جوه قاعدة البيانات
    $this->assertDatabaseHas('time_slots', [
        'id' => $slot->id,
        'status' => 'booked',
    ]);

    // Assert 3: اتعمل appointment جديد مربوط بالمريض والموعد الصح
    $this->assertDatabaseHas('appointments', [
        'user_id' => $patient->id,
        'time_slot_id' => $slot->id,
        'status' => 'confirmed',
    ]);
});

it('rejects booking a slot that is already booked', function () {

    // Arrange: موعد محجوز بالفعل من الأول
    $patient = User::factory()->create();
    $slot = TimeSlot::factory()->booked()->create();

    // Act
    $response = $this->actingAs($patient, 'sanctum')
        ->postJson('/api/appointments', [
            'time_slot_id' => $slot->id,
        ]);

    // Assert: المفروض يرجع 409 Conflict، مش 201
    $response->assertStatus(409);

    // Assert: اتأكد إن مفيش appointment اتعمل بالغلط
    $this->assertDatabaseMissing('appointments', [
        'user_id' => $patient->id,
        'time_slot_id' => $slot->id,
    ]);
});

it('rejects booking without authentication', function () {

    $slot = TimeSlot::factory()->create();

    // من غير actingAs() - يعني زي حد مش مسجل دخول أصلاً
    $response = $this->postJson('/api/appointments', [
        'time_slot_id' => $slot->id,
    ]);

    $response->assertStatus(401);
});
