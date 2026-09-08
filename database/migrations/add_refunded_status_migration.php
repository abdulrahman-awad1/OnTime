<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL محتاج إعادة تعريف الـ enum كامل لإضافة قيمة جديدة
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending'");
        DB::statement("ALTER TABLE appointments MODIFY payment_status ENUM('unpaid', 'paid', 'failed', 'refunded') DEFAULT 'unpaid'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE payments MODIFY status ENUM('pending', 'paid', 'failed') DEFAULT 'pending'");
        DB::statement("ALTER TABLE appointments MODIFY payment_status ENUM('unpaid', 'paid', 'failed') DEFAULT 'unpaid'");
    }
};
