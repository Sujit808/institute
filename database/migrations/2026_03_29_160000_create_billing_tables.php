<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('license_config_id')->nullable()->constrained('license_configs')->nullOnDelete();
            $table->string('provider')->default('generic');
            $table->string('provider_subscription_id')->unique();
            $table->string('provider_customer_id')->nullable();
            $table->string('plan_key')->default('starter');
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('renews_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained('billing_subscriptions')->nullOnDelete();
            $table->string('provider')->default('generic');
            $table->string('provider_invoice_id')->nullable()->unique();
            $table->string('invoice_number')->nullable();
            $table->decimal('amount_due', 10, 2)->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->string('status')->default('pending')->index();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->nullable()->constrained('billing_invoices')->nullOnDelete();
            $table->string('provider')->default('generic');
            $table->string('provider_transaction_id')->unique();
            $table->string('gateway')->nullable();
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('currency', 3)->default('INR');
            $table->timestamp('paid_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider')->default('generic');
            $table->string('provider_event_id')->unique();
            $table->string('event_type');
            $table->boolean('signature_valid')->default(false);
            $table->longText('payload');
            $table->string('processing_status')->default('pending')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_webhook_events');
        Schema::dropIfExists('billing_transactions');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_subscriptions');
    }
};
