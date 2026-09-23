<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('commercial_name_en');
            $table->string('commercial_name_ar')->nullable();
            $table->string('scientific_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('drug_class')->nullable();
            $table->string('route')->nullable();
            $table->decimal('price_egp', 10, 2)->nullable();
            $table->timestamps();

            $table->index('commercial_name_en');
            $table->index('commercial_name_ar');
            $table->index('scientific_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
