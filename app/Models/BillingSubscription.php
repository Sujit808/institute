<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'license_config_id',
        'provider',
        'provider_subscription_id',
        'provider_customer_id',
        'plan_key',
        'status',
        'amount',
        'currency',
        'trial_ends_at',
        'renews_at',
        'canceled_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'canceled_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function licenseConfig(): BelongsTo
    {
        return $this->belongsTo(LicenseConfig::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(BillingInvoice::class, 'subscription_id');
    }
}
