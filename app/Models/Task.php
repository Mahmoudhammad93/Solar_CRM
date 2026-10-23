<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Task extends Model
{
    protected $fillable = [
        'reference',
        'title',
        'description',
        'owner_id',
        'due_at',
        'priority',
        'status',
        'task_type',
        'reminder_at',
        'outcome',
        'completed_at',
        'taskable_type',
        'taskable_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'reminder_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeDueToday(Builder $query): Builder
    {
        return $query->whereDate('due_at', now()->toDateString())
            ->where(function (Builder $q) {
                $q->whereNull('status')->orWhereNotIn('status', ['completed', 'cancelled']);
            });
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where(function (Builder $q) {
                $q->whereNull('status')->orWhereNotIn('status', ['completed', 'cancelled']);
            });
    }
}
