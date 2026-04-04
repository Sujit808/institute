<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'provider',
        'provider_invoice_id',
        'invoice_number',
        'amount_due',
        'amount_paid',
        'currency',
        'status',
        'period_start',
        'period_end',
        'due_date',
        'paid_at',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(BillingSubscription::class, 'subscription_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BillingTransaction::class, 'invoice_id');
    }
}
