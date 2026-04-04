<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterControlSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_config_id',
        'snapshot',
        'change_summary',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function licenseConfig(): BelongsTo
    {
        return $this->belongsTo(LicenseConfig::class);
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
