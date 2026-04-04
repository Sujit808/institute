<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_leads', function (Blueprint $table) {
            $table->id();
            $table->string('student_name');
            $table->string('guardian_name')->nullable();
            $table->string('phone', 25);
            $table->string('email')->nullable();
            $table->foreignId('academic_class_id')->nullable()->constrained('academic_classes')->nullOnDelete();
            $table->string('source', 50)->default('walk_in');
            $table->string('stage', 50)->default('new');
            $table->unsignedTinyInteger('score')->nullable();
            $table->foreignId('assigned_to_staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->dateTime('last_contacted_at')->nullable();
            $table->dateTime('next_follow_up_at')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status', 20)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['stage', 'status']);
            $table->index('phone');
            $table->index('next_follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_leads');
    }
};
