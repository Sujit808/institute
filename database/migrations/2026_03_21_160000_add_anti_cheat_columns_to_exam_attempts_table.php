<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->after('submitted_at');
            $table->unsignedTinyInteger('tab_switch_count')->default(0)->after('locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['locked_at', 'tab_switch_count']);
        });
    }
};
