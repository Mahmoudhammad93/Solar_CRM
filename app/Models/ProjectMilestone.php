<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMilestone extends Model
{
    protected $fillable = [
        'project_id',
        'milestone_key',
        'label',
        'sort_order',
        'weight',
        'status',
        'hold_reason',
        'started_at',
        'completed_at',
        'due_at',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_at !== null
            && $this->due_at->isPast()
            && ! in_array($this->status, ['completed'], true);
    }
}
