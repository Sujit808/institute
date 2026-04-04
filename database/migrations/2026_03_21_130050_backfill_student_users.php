<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $students = DB::table('students')->select('id', 'first_name', 'last_name', 'email', 'phone', 'guardian_phone', 'roll_no', 'status')->get();

        foreach ($students as $student) {
            $existing = DB::table('users')->where('student_id', $student->id)->first();
            $email = $student->email ?: 'student'.$student->id.'@students.schoolsphere.local';

            if (DB::table('users')->where('email', $email)->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))->exists()) {
                $email = 'student'.$student->id.'-'.substr(md5((string) $student->id), 0, 6).'@students.schoolsphere.local';
            }

            $phone = $student->phone ?: $student->guardian_phone;

            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update([
                    'name' => trim($student->first_name.' '.$student->last_name),
                    'email' => $email,
                    'phone' => $phone,
                    'role' => 'student',
                    'active' => $student->status === 'active',
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('users')->insert([
                'name' => trim($student->first_name.' '.$student->last_name),
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make((string) $student->roll_no),
                'role' => 'student',
                'permissions' => json_encode([]),
                'student_id' => $student->id,
                'must_change_password' => false,
                'active' => $student->status === 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'student')->delete();
    }
};
