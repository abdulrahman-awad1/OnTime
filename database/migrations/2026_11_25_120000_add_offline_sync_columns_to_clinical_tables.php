<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offline-first (append-only) support:
 *  - client_uuid  : idempotency key generated on the device (prevents duplicates on retry)
 *  - recorded_at  : the real time the doctor wrote the record (may be earlier than created_at)
 * Non-destructive: nullable columns only, existing rows untouched.
 */
return new class extends Migration
{
    private array $tables = ['medical_records', 'prescriptions', 'medical_attachments'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('client_uuid')->nullable()->unique()->after('id');
                $t->timestamp('recorded_at')->nullable()->after('client_uuid');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique("{$table}_client_uuid_unique");
                $t->dropColumn(['client_uuid', 'recorded_at']);
            });
        }
    }
};
