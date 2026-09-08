<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinic_location_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'booked', 'cancelled'])
                ->default('available');
            $table->timestamps();

            // composite index: أهم query هي فلترة بالعيادة + التاريخ + الحالة
            $table->index(['clinic_location_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
