<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Ticket extends Model
{
    protected $fillable = [
        'number',
        'customer_id',
        'project_id',
        'order_id',
        'product_id',
        'category',
        'issue_summary',
        'issue_detail',
        'priority',
        'source',
        'opened_at',
        'assignee_id',
        'team_id',
        'status',
        'target_resolution_at',
        'resolution_notes',
        'parts_costs',
        'feedback',
        'resolved_at',
        'closed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'target_resolution_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'parts_costs' => 'array',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class)->latest();
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(TicketStatusHistory::class)->orderBy('created_at');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isOverdue(): bool
    {
        return $this->target_resolution_at !== null
            && $this->target_resolution_at->isPast()
            && ! in_array($this->status, ['resolved', 'closed'], true);
    }
}
