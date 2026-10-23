<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Lead extends Model
{
    protected $fillable = [
        'reference',
        'title',
        'customer_type',
        'name',
        'company_name',
        'contact_person',
        'mobile',
        'alternate_phone',
        'email',
        'address',
        'city',
        'district',
        'state',
        'postal_code',
        'gps_lat',
        'gps_lng',
        'branch_id',
        'territory',
        'system_type',
        'property_segment',
        'roof_site_type',
        'monthly_bill',
        'monthly_units',
        'sanctioned_load_kw',
        'desired_capacity_kw',
        'priority',
        'budget_range',
        'source',
        'campaign_details',
        'assigned_to',
        'stage',
        'loss_reason',
        'probability',
        'expected_value',
        'notes',
        'tags',
        'next_follow_up_at',
        'reminder_at',
        'follow_up_outcome',
        'customer_id',
        'converted_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'monthly_bill' => 'decimal:2',
            'monthly_units' => 'decimal:2',
            'sanctioned_load_kw' => 'decimal:2',
            'desired_capacity_kw' => 'decimal:2',
            'expected_value' => 'decimal:2',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
            'probability' => 'integer',
            'next_follow_up_at' => 'datetime',
            'reminder_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(LeadStageHistory::class)->latest('created_at');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(LeadInteraction::class)->latest('interacted_at');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function isLost(): bool
    {
        return strtolower((string) $this->stage) === 'lost';
    }

    public function isConverted(): bool
    {
        return $this->customer_id !== null;
    }
}
