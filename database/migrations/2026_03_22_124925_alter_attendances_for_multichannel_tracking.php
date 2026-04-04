<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('attendances', function (Blueprint $table): void {
                if (! Schema::hasColumn('attendances', 'staff_attendance_id')) {
                    $table->unsignedBigInteger('staff_attendance_id')->nullable()->after('student_id');
                }
                if (! Schema::hasColumn('attendances', 'attendance_for')) {
                    $table->string('attendance_for', 30)->default('student')->after('attendance_date');
                }
                if (! Schema::hasColumn('attendances', 'attendance_method')) {
                    $table->string('attendance_method', 40)->default('manual')->after('attendance_for');
                }
                if (! Schema::hasColumn('attendances', 'biometric_device_id')) {
                    $table->string('biometric_device_id', 120)->nullable()->after('attendance_method');
                }
                if (! Schema::hasColumn('attendances', 'biometric_log_id')) {
                    $table->string('biometric_log_id', 120)->nullable()->after('biometric_device_id');
                }
                if (! Schema::hasColumn('attendances', 'capture_payload')) {
                    $table->json('capture_payload')->nullable()->after('biometric_log_id');
                }
                if (! Schema::hasColumn('attendances', 'captured_at')) {
                    $table->timestamp('captured_at')->nullable()->after('capture_payload');
                }
                if (! Schema::hasColumn('attendances', 'marked_by_staff_id')) {
                    $table->unsignedBigInteger('marked_by_staff_id')->nullable()->after('staff_id');
                }
                if (! Schema::hasColumn('attendances', 'sync_status')) {
                    $table->string('sync_status', 20)->default('synced')->after('status');
                }
            });

            return;
        }

        // MySQL requires FKs to be dropped before the unique index they rely on.
        // Use raw DDL so we can control order precisely without Schema builder quirks.
        DB::statement('ALTER TABLE attendances DROP FOREIGN KEY attendances_student_id_foreign');
        DB::statement('ALTER TABLE attendances DROP FOREIGN KEY attendances_academic_class_id_foreign');
        DB::statement('ALTER TABLE attendances DROP FOREIGN KEY attendances_section_id_foreign');
        DB::statement('ALTER TABLE attendances DROP INDEX attendances_student_id_attendance_date_unique');

        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('staff_attendance_id')->nullable()->after('student_id')->constrained('staff')->nullOnDelete();
            $table->string('attendance_for', 30)->default('student')->after('attendance_date');
            $table->string('attendance_method', 40)->default('manual')->after('attendance_for');
            $table->string('biometric_device_id', 120)->nullable()->after('attendance_method');
            $table->string('biometric_log_id', 120)->nullable()->after('biometric_device_id');
            $table->json('capture_payload')->nullable()->after('biometric_log_id');
            $table->timestamp('captured_at')->nullable()->after('capture_payload');
            $table->foreignId('marked_by_staff_id')->nullable()->after('staff_id')->constrained('staff')->nullOnDelete();
            $table->string('sync_status', 20)->default('synced')->after('status');

            $table->foreignId('student_id')->nullable()->change();
            $table->foreignId('academic_class_id')->nullable()->change();
            $table->foreignId('section_id')->nullable()->change();

            $table->foreign('student_id')->references('id')->on('students')->nullOnDelete();
            $table->foreign('academic_class_id')->references('id')->on('academic_classes')->nullOnDelete();
            $table->foreign('section_id')->references('id')->on('sections')->nullOnDelete();

            $table->unique(['attendance_for', 'attendance_date', 'student_id'], 'attendances_student_day_unique');
            $table->unique(['attendance_for', 'attendance_date', 'staff_attendance_id'], 'attendances_staff_day_unique');
            $table->index(['attendance_method', 'attendance_date'], 'attendances_method_date_idx');
            $table->index('biometric_log_id', 'attendances_biometric_log_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('attendances_method_date_idx');
            $table->dropIndex('attendances_biometric_log_idx');
            $table->dropUnique('attendances_student_day_unique');
            $table->dropUnique('attendances_staff_day_unique');

            $table->dropForeign(['student_id']);
            $table->dropForeign(['academic_class_id']);
            $table->dropForeign(['section_id']);
            $table->dropForeign(['staff_attendance_id']);
            $table->dropForeign(['marked_by_staff_id']);

            $table->dropColumn([
                'staff_attendance_id',
                'attendance_for',
                'attendance_method',
                'biometric_device_id',
                'biometric_log_id',
                'capture_payload',
                'captured_at',
                'marked_by_staff_id',
                'sync_status',
            ]);

            $table->foreignId('student_id')->nullable(false)->change();
            $table->foreignId('academic_class_id')->nullable(false)->change();
            $table->foreignId('section_id')->nullable(false)->change();

            $table->foreign('student_id')->references('id')->on('students');
            $table->foreign('academic_class_id')->references('id')->on('academic_classes');
            $table->foreign('section_id')->references('id')->on('sections');
            $table->unique(['student_id', 'attendance_date']);
        });
    }
};
