<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('admin')->after('password');
            $table->json('permissions')->nullable()->after('role');
            $table->unsignedBigInteger('staff_id')->nullable()->after('permissions');
            $table->boolean('must_change_password')->default(false)->after('staff_id');
            $table->boolean('active')->default(true)->after('must_change_password');
            $table->unsignedBigInteger('deleted_by')->nullable()->after('active');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'role', 'permissions', 'staff_id', 'must_change_password', 'active', 'deleted_by']);
            $table->dropSoftDeletes();
        });
    }
};
