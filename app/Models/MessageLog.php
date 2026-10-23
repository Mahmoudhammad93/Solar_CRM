<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MessageLog extends Model
{
    public $timestamps = false;

    public const STATUS_DEMO_SIMULATED = 'demo_simulated';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'channel',
        'template_key',
        'recipient',
        'status',
        'subject_type',
        'subject_id',
        'subject_reference',
        'initiated_by',
        'payload',
        'provider_response',
        'error_message',
        'retry_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isSimulated(): bool
    {
        return $this->status === self::STATUS_DEMO_SIMULATED;
    }
}
