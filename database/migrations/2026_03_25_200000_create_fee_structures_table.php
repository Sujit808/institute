<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('academic_class_id')->constrained('academic_classes')->cascadeOnDelete();
            $table->string('fee_head');          // tuition, transport, lab, sports, exam, hostel, misc
            $table->string('fee_label');         // Display label e.g. "Tuition Fee"
            $table->decimal('amount', 10, 2);
            $table->tinyInteger('due_month')->nullable(); // 1-12, null = one-time
            $table->string('academic_year', 10)->default('2025-26'); // e.g. 2025-26
            $table->string('status')->default('active'); // active / inactive
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
