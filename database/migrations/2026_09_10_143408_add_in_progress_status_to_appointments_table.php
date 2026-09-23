<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE appointments MODIFY status ENUM('confirmed', 'in_progress', 'completed', 'no_show', 'cancelled') DEFAULT 'confirmed'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE appointments MODIFY status ENUM('confirmed', 'completed', 'no_show', 'cancelled') DEFAULT 'confirmed'");
    }
};
