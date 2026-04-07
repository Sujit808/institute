<?php

namespace App\Models;

use App\Support\SchoolModuleRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'permissions',
        'staff_id',
        'student_id',
        'photo',
        'must_change_password',
        'active',
        'deleted_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Laravel 12+ auto-bcrypt
            'permissions' => 'array',
            'must_change_password' => 'boolean',
            'active' => 'boolean',
        ];
    }

    // Relationships
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class)
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    // Role check helpers
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isHr(): bool
    {
        return $this->role === 'hr';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    // Module access
    public function canAccessModule(string $module): bool
    {
        $checkModule = SchoolModuleRegistry::normalizePermissionKey($module);
        $license = LicenseConfig::current();

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($module === 'audit-logs') {
            return $this->isSuperAdmin();
        }

        if ($license && ! $license->moduleEnabled($checkModule)) {
            return false;
        }

        $permissions = $this->permissions ?? [];

        if ($this->isAdmin() || $this->isHr()) {
            return empty($permissions) || in_array($checkModule, $permissions, true);
        }

        if ($this->isTeacher()) {
            return in_array($checkModule, array_unique(array_merge(
                $permissions,
                SchoolModuleRegistry::defaultPermissionsForRole('teacher')
            )), true) || $module === 'dashboard';
        }

        if ($this->isStudent()) {
            return false;
        }

        return false;
    }

    // Booted method for auto-create or password update for Super Admin
    protected static function booted()
    {
        static::creating(function ($user) {
            if ($user->role === 'super_admin' && empty($user->permissions)) {
                $user->permissions = [
                    "payroll","students","admission-leads","staff","classes","sections",
                    "subjects","exams","exam-questions","exam-papers","results",
                    "study-materials","attendance","biometric-devices","fees","timetable",
                    "notifications","holidays","leaves","calendar","icards",
                    "quotations","audit-logs"
                ];
            }
        });

        // Ensure superadmin password is updated or created
        static::booted(function () {
            $superadminEmail = 'superadmin@school.com';
            $defaultPassword = env('SUPERADMIN_PASSWORD', 'SuperAdmin@123');

            $superadmin = User::withTrashed()->where('email', $superadminEmail)->first();

            if ($superadmin) {
                // Update password if already exists
                $superadmin->password = $defaultPassword; // auto-hashed by Laravel 12+
                $superadmin->role = 'super_admin';
                $superadmin->active = true;
                $superadmin->permissions = $superadmin->permissions ?? [
                    "payroll","students","admission-leads","staff","classes","sections",
                    "subjects","exams","exam-questions","exam-papers","results",
                    "study-materials","attendance","biometric-devices","fees","timetable",
                    "notifications","holidays","leaves","calendar","icards",
                    "quotations","audit-logs"
                ];
                $superadmin->save();
            } else {
                // Create superadmin if not exists
                User::create([
                    'name' => 'Super Admin',
                    'email' => $superadminEmail,
                    'password' => $defaultPassword, // auto-hashed
                    'role' => 'super_admin',
                    'active' => true,
                ]);
            }
        });
    }
}
