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
            $table->decimal('consultation_price', 8, 2)->after('longitude');
            $table->decimal('checkup_price', 8, 2)->after('consultation_price');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_locations', function (Blueprint $table) {
            $table->dropColumn(['consultation_price', 'checkup_price']);
        });
    }
};
