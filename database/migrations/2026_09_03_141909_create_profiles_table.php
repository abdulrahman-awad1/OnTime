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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            // Relation with users table
            $table->foreignId('user_id')
                ->unique()
                ->constrained()
                ->cascadeOnDelete();

            // Personal Information
            $table->date('date_of_birth');
            $table->enum('gender', ['male', 'female']);
            $table->text('address')->nullable();

            // Basic Medical Information
            $table->string('blood_type')->nullable();

            $table->boolean('has_hypertension')->nullable();
            $table->boolean('has_diabetes')->nullable();
            $table->boolean('has_heart_disease')->nullable();
            $table->boolean('has_previous_stroke')->nullable();

            $table->text('medical_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
