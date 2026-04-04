<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_leads', function (Blueprint $table) {
            $table->foreignId('converted_student_id')->nullable()->after('assigned_to_staff_id')->constrained('students')->nullOnDelete();
            $table->dateTime('converted_at')->nullable()->after('next_follow_up_at');
            $table->index(['converted_student_id', 'converted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('admission_leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_student_id');
            $table->dropColumn('converted_at');
        });
    }
};
