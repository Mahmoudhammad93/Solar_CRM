<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Installation extends Model
{
    protected $fillable = [
        'project_id',
        'crew',
        'scheduled_at',
        'checklist',
        'serial_numbers',
        'safety_notes',
        'commissioning_readings',
        'customer_signoff_path',
        'signed_off_at',
        'completion_report_path',
        'handover_notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'checklist' => 'array',
            'serial_numbers' => 'array',
            'commissioning_readings' => 'array',
            'signed_off_at' => 'datetime',
        ];
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
