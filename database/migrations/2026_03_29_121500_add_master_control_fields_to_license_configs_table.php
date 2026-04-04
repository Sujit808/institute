<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('license_configs', function (Blueprint $table): void {
            $table->json('enabled_modules')->nullable()->after('is_active');
            $table->json('approval_settings')->nullable()->after('enabled_modules');
            $table->json('role_limits')->nullable()->after('approval_settings');
        });
    }

    public function down(): void
    {
        Schema::table('license_configs', function (Blueprint $table): void {
            $table->dropColumn(['enabled_modules', 'approval_settings', 'role_limits']);
        });
    }
};
