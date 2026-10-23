<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Visit extends Model
{
    protected $fillable = [
        'reference',
        'lead_id',
        'customer_id',
        'scheduled_at',
        'assigned_surveyor_id',
        'status',
        'site_address',
        'city',
        'state',
        'postal_code',
        'gps_lat',
        'gps_lng',
        'reschedule_cancel_reason',
        'property_type',
        'roof_type_material',
        'usable_area_sqm',
        'orientation_tilt',
        'shading_obstructions',
        'structural_concerns',
        'site_access_notes',
        'safety_notes',
        'bill_consumption_evidence',
        'monthly_units',
        'sanctioned_load_kw',
        'phase_type',
        'utility_discom',
        'meter_connection_type',
        'proposed_hybrid_system',
        'tentative_capacity_kw',
        'measurements',
        'feasibility',
        'customer_needs',
        'objections',
        'feedback',
        'recommended_next_step',
        'preliminary_bom',
        'completed_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'gps_lat' => 'decimal:7',
            'gps_lng' => 'decimal:7',
            'usable_area_sqm' => 'decimal:2',
            'monthly_units' => 'decimal:2',
            'sanctioned_load_kw' => 'decimal:2',
            'tentative_capacity_kw' => 'decimal:2',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_surveyor_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(VisitHistory::class)->latest('created_at');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }
}
