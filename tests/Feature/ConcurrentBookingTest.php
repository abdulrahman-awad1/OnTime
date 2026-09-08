<?php

use App\Models\TimeSlot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('blocks a second connection from locking the same slot while the first is still holding it', function () {

    // Arrange: موعد متاح في قاعدة بيانات الاختبار الحقيقية (MySQL مش SQLite)
    $slot = TimeSlot::factory()->create();

    // خلي الاتصال التاني يستنى ثانية واحدة بس قبل ما يستسلم
    // (بدل الإعداد الافتراضي اللي ممكن يستنى 50 ثانية ويعلّق التست)
    DB::connection('mysql_second')->statement('SET SESSION innodb_lock_wait_timeout = 1');

    // ------- الاتصال الأول: يقفل الصف ومبيعملش commit لسه -------
    DB::connection('mysql')->beginTransaction();

    DB::connection('mysql')
        ->table('time_slots')
        ->where('id', $slot->id)
        ->lockForUpdate()
        ->first();

    // دلوقتي الصف ده مقفول من اتصال 'mysql'، وأي اتصال تاني
    // يحاول يعمل lockForUpdate عليه المفروض "يستنى" أو يفشل

    // ------- الاتصال الثاني: يحاول يقفل نفس الصف وهو لسه مقفول -------
    $secondConnectionBlocked = false;

    try {
        DB::connection('mysql_second')->transaction(function () use ($slot) {
            DB::connection('mysql_second')
                ->table('time_slots')
                ->where('id', $slot->id)
                ->lockForUpdate()
                ->first();
        });
    } catch (QueryException $e) {
        // المفروض يوصل هنا بسبب "Lock wait timeout exceeded"
        $secondConnectionBlocked = true;
    }

    // نسيب القفل من الاتصال الأول عشان ننضف بعد التست
    DB::connection('mysql')->rollBack();

    // Assert: الاتصال التاني كان لازم يتمنع/يستنى - ده الدليل إن القفل حقيقي
    expect($secondConnectionBlocked)->toBeTrue();
});
