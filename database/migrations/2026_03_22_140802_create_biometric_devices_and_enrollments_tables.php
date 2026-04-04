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
        // ── 1. Biometric Devices ────────────────────────────────────────────
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('device_code', 80)->unique();      // used as biometric_device_id in punch API
            $table->string('brand', 80)->nullable();
            $table->string('model_no', 80)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedSmallInteger('port')->default(4370);
            $table->string('location', 150)->nullable();
            $table->string('device_type', 40)->default('fingerprint');
            $table->string('communication', 40)->default('push_api');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        // ── 2. Biometric Enrollments (columns first, FKs after) ─────────────
        Schema::create('biometric_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biometric_device_id');
            $table->string('enrollment_for', 20)->default('student');
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('staff_id')->nullable();
            $table->string('punch_id', 80);
            $table->string('finger_index', 10)->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('enrolled_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['biometric_device_id', 'punch_id'], 'biometric_device_punch_unique');
        });

        // ── 3. Add FKs after both tables exist ──────────────────────────────
        Schema::table('biometric_enrollments', function (Blueprint $table) {
            $table->foreign('biometric_device_id')
                ->references('id')->on('biometric_devices')
                ->cascadeOnDelete();
            $table->foreign('student_id')
                ->references('id')->on('students')
                ->nullOnDelete();
            $table->foreign('staff_id')
                ->references('id')->on('staff')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('biometric_enrollments', function (Blueprint $table) {
            $table->dropForeign(['biometric_device_id']);
            $table->dropForeign(['student_id']);
            $table->dropForeign(['staff_id']);
        });
        Schema::dropIfExists('biometric_enrollments');
        Schema::dropIfExists('biometric_devices');
    }
};
