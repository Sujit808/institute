<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            if (! Schema::hasColumn('holidays', 'entry_type')) {
                $table->string('entry_type')->default('holiday')->after('holiday_type');
            }
            if (! Schema::hasColumn('holidays', 'class_id')) {
                $table->unsignedBigInteger('class_id')->nullable()->after('entry_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('holidays', function (Blueprint $table) {
            if (Schema::hasColumn('holidays', 'entry_type')) {
                $table->dropColumn('entry_type');
            }
            if (Schema::hasColumn('holidays', 'class_id')) {
                $table->dropColumn('class_id');
            }
        });
    }
};
