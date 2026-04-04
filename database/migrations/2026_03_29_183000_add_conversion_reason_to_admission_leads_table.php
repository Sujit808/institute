<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_leads', function (Blueprint $table) {
            $table->text('conversion_reason')->nullable()->after('converted_at');
        });
    }

    public function down(): void
    {
        Schema::table('admission_leads', function (Blueprint $table) {
            $table->dropColumn('conversion_reason');
        });
    }
};
