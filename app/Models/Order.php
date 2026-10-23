<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    protected $fillable = [
        'number',
        'ordered_on',
        'quotation_id',
        'quotation_revision_id',
        'customer_id',
        'owner_id',
        'site_address',
        'project_type',
        'capacity_kw',
        'contract_value',
        'confirmed_scope',
        'payment_schedule',
        'lines_snapshot',
        'totals_snapshot',
        'delivery_target',
        'status',
        'cancellation_reason',
        'project_id',
    ];

    protected function casts(): array
    {
        return [
            'ordered_on' => 'date',
            'delivery_target' => 'date',
            'capacity_kw' => 'decimal:3',
            'contract_value' => 'decimal:2',
            'payment_schedule' => 'array',
            'lines_snapshot' => 'array',
            'totals_snapshot' => 'array',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function quotationRevision(): BelongsTo
    {
        return $this->belongsTo(QuotationRevision::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
