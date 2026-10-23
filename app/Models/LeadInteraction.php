<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadInteraction extends Model
{
    protected $fillable = [
        'lead_id',
        'type',
        'subject',
        'body',
        'outcome',
        'interacted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'interacted_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
