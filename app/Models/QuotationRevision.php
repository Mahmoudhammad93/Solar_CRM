<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationRevision extends Model
{
    protected $fillable = [
        'quotation_id',
        'revision',
        'status',
        'is_locked',
        'valid_until',
        'system_type',
        'system_capacity_kw',
        'site_address',
        'customer_state',
        'internal_notes',
        'customer_terms',
        'payment_schedule',
        'advance_amount',
        'pump_details',
        'hybrid_assumptions',
        'lines',
        'totals',
        'tax_config',
        'branding_snapshot',
        'template_texts',
        'signature_path',
        'signed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
            'valid_until' => 'date',
            'system_capacity_kw' => 'decimal:3',
            'advance_amount' => 'decimal:2',
            'payment_schedule' => 'array',
            'pump_details' => 'array',
            'hybrid_assumptions' => 'array',
            'lines' => 'array',
            'totals' => 'array',
            'tax_config' => 'array',
            'branding_snapshot' => 'array',
            'template_texts' => 'array',
            'signed_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return ! $this->is_locked && in_array(strtolower((string) $this->status), ['draft', 'review'], true);
    }
}
