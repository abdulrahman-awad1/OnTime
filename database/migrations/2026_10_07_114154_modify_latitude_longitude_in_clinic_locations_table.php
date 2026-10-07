<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clinic_locations', function (Blueprint $table) {
            // جعل العمودين يقبلان null مع وضع قيمة افتراضية
            $table->decimal('latitude', 10, 8)->nullable()->default(30.0444)->change();
            $table->decimal('longitude', 11, 8)->nullable()->default(31.2357)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinic_locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable(false)->change();
            $table->decimal('longitude', 11, 8)->nullable(false)->change();
        });
    }
};
