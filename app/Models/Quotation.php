<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'created_by',
        'updated_by',
        'deleted_by',
        'document_type',
        'quotation_no',
        'quotation_date',
        'valid_until',
        'currency',
        'prepared_by',
        'subject',
        'intro_text',
        'notes',
        'footer_text',
        'client',
        'items',
        'terms',
        'bank_details',
        'subtotal',
        'discount_rate',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'grand_total',
        'last_action',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'generated_at' => 'datetime',
            'client' => 'array',
            'items' => 'array',
            'terms' => 'array',
            'bank_details' => 'array',
            'subtotal' => 'decimal:2',
            'discount_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
