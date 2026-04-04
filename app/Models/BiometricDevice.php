<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BiometricDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_name',
        'device_code',
        'brand',
        'model_no',
        'ip_address',
        'port',
        'location',
        'device_type',
        'communication',
        'status',
        'last_sync_at',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_sync_at' => 'datetime',
        'port' => 'integer',
    ];

    public function enrollments(): HasMany
    {
        return $this->hasMany(BiometricEnrollment::class);
    }

    /** Unique device code used in the API punch header / payload. */
    public function getApiCodeAttribute(): string
    {
        return (string) $this->device_code;
    }
}
