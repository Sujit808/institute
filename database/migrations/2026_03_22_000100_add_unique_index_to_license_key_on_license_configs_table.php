<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_configs', function (Blueprint $table): void {
            $table->unique('license_key');
        });
    }

    public function down(): void
    {
        Schema::table('license_configs', function (Blueprint $table): void {
            $table->dropUnique(['license_key']);
        });
    }
};
