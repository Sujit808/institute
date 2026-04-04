<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_type', 30)->default('Quotation');
            $table->string('quotation_no', 50)->unique();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->string('currency', 10)->default('PKR');
            $table->string('prepared_by')->nullable();
            $table->string('subject')->nullable();
            $table->text('intro_text')->nullable();
            $table->text('notes')->nullable();
            $table->text('footer_text')->nullable();
            $table->json('client');
            $table->json('items');
            $table->json('terms')->nullable();
            $table->json('bank_details')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_rate', 7, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 7, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('last_action', 30)->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
