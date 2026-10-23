<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AmcContract extends Model
{
    protected $fillable = [
        'number',
        'customer_id',
        'project_id',
        'site_address',
        'starts_on',
        'ends_on',
        'coverage',
        'visit_frequency',
        'sla_hours',
        'amount',
        'next_service_on',
        'status',
        'renewal_reminder_on',
        'notes',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'next_service_on' => 'date',
            'renewal_reminder_on' => 'date',
            'amount' => 'decimal:2',
            'sla_hours' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeDueForRenewalReminder(Builder $query, ?CarbonInterface $on = null): Builder
    {
        $on ??= now()->startOfDay();

        return $query
            ->whereIn('status', ['active', 'expiring'])
            ->where(function (Builder $q) use ($on) {
                $q->whereDate('renewal_reminder_on', '<=', $on)
                    ->orWhereDate('ends_on', '<=', $on->copy()->addDays(30));
            });
    }
}
