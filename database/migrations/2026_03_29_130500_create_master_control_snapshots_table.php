<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_control_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('license_config_id')->nullable()->constrained('license_configs')->nullOnDelete();
            $table->json('snapshot');
            $table->string('change_summary')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_control_snapshots');
    }
};
