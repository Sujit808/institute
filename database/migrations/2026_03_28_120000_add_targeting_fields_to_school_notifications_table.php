<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_notifications', function (Blueprint $table) {
            $table->foreignId('academic_class_id')->nullable()->after('audience')->constrained('academic_classes')->nullOnDelete();
            $table->foreignId('section_id')->nullable()->after('academic_class_id')->constrained('sections')->nullOnDelete();
            $table->string('source_type', 50)->nullable()->after('status');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');

            $table->index(['source_type', 'source_id'], 'school_notifications_source_idx');
            $table->index(['academic_class_id', 'section_id'], 'school_notifications_target_idx');
        });
    }

    public function down(): void
    {
        Schema::table('school_notifications', function (Blueprint $table) {
            $table->dropIndex('school_notifications_source_idx');
            $table->dropIndex('school_notifications_target_idx');
            $table->dropConstrainedForeignId('section_id');
            $table->dropConstrainedForeignId('academic_class_id');
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
