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
            'password' => 'hashed',
            'permissions' => 'array',
            'must_change_password' => 'boolean',
            'active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 AUTO SUPER ADMIN
    |--------------------------------------------------------------------------
    */
    public static function ensureSuperAdmin()
    {
        return self::firstOrCreate(
            ['email' => 'superadmin@school.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
                'role' => 'super_admin',
                'active' => true,
                'must_change_password' => false,
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Roles
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    public function canAccessModule(string $module): bool
    {
        $checkModule = SchoolModuleRegistry::normalizePermissionKey($module);
        $license = LicenseConfig::current();

        if ($module === 'audit-logs') {
            return $this->isSuperAdmin();
        }

        if ($license && ! $license->moduleEnabled($checkModule)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->permissions ?? [];

        if ($this->isAdmin() || $this->isHr()) {
            return $permissions === [] || in_array($checkModule, $permissions, true);
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
}
