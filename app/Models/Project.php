<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    protected $fillable = [
        'number',
        'order_id',
        'quotation_id',
        'customer_id',
        'manager_id',
        'sales_user_id',
        'engineering_user_id',
        'installation_team_id',
        'planned_start',
        'planned_end',
        'actual_start',
        'actual_end',
        'priority',
        'current_milestone_key',
        'percent_complete',
        'risks_blockers',
        'customer_updates',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'planned_start' => 'date',
            'planned_end' => 'date',
            'actual_start' => 'date',
            'actual_end' => 'date',
            'percent_complete' => 'decimal:2',
            'customer_updates' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sort_order');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProjectMaterial::class);
    }

    public function installation(): HasOne
    {
        return $this->hasOne(Installation::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function recalculatePercentComplete(): float
    {
        $totalWeight = (float) $this->milestones()->sum('weight');
        if ($totalWeight <= 0) {
            return 0;
        }
        $done = (float) $this->milestones()->where('status', 'completed')->sum('weight');
        $pct = round(($done / $totalWeight) * 100, 2);
        $this->update(['percent_complete' => $pct]);

        return $pct;
    }
}
