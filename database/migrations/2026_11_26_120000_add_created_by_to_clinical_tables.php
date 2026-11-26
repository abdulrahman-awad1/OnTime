<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which user (doctor) wrote each clinical row.
 * Non-destructive: nullable, existing rows stay null, deleting a user keeps the records.
 */
return new class extends Migration
{
    private array $tables = ['medical_records', 'prescriptions', 'medical_attachments'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('created_by')->nullable()->after('recorded_at')
                    ->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('created_by');
            });
        }
    }
};
