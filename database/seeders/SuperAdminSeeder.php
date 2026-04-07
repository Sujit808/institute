<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        $email = 'superadmin@school.com';
        $password = env('SUPERADMIN_PASSWORD', 'SuperAdmin@123');

        $fullPermissions = [
            "payroll","students","admission-leads","staff","classes","sections",
            "subjects","exams","exam-questions","exam-papers","results",
            "study-materials","attendance","biometric-devices","fees","timetable",
            "notifications","holidays","leaves","calendar","icards",
            "quotations","audit-logs"
        ];

        $superadmin = User::withTrashed()->firstWhere('email', $email);

        if ($superadmin) {
            $superadmin->password = $password;
            $superadmin->role = 'super_admin';
            $superadmin->active = true;
            $superadmin->permissions = $superadmin->permissions ?? $fullPermissions;
            $superadmin->save();
        } else {
            User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => $password,
                'role' => 'super_admin',
                'active' => true,
                'permissions' => $fullPermissions
            ]);
        }
    }
}


// php artisan make:seeder SuperAdminSeeder

