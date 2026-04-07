<?php

namespace App\Models;

use App\Support\SchoolModuleRegistry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    // Fillable attributes
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

    // Hidden attributes
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Cast attributes
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',  // Laravel 12+ auto-hashes
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

    // Role helpers
    public function isSuperAdmin(): bool { return $this->role === 'super_admin'; }
    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isHr(): bool { return $this->role === 'hr'; }
    public function isTeacher(): bool { return $this->role === 'teacher'; }
    public function isStudent(): bool { return $this->role === 'student'; }

    // Module access
    public function canAccessModule(string $module): bool
    {
        $checkModule = SchoolModuleRegistry::normalizePermissionKey($module);
        $license = LicenseConfig::current();

        if ($this->isSuperAdmin()) return true;
        if ($module === 'audit-logs') return $this->isSuperAdmin();
        if ($license && ! $license->moduleEnabled($checkModule)) return false;

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

        return false;
    }

    // Optional default permissions for Super Admin
    public static function booted()
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

        // ✅ IMPORTANT: Superadmin **auto-update at login removed** to avoid 500 error
        // Seeder will handle creation/updating
    }
}
