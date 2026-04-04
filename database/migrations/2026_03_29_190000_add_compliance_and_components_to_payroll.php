<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payroll_salaries', function (Blueprint $table) {
            $table->decimal('pf', 10, 2)->default(0);
            $table->decimal('esi', 10, 2)->default(0);
            $table->decimal('tds', 10, 2)->default(0);
            $table->json('custom_components')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('payroll_salaries', function (Blueprint $table) {
            $table->dropColumn(['pf', 'esi', 'tds', 'custom_components']);
        });
    }
};