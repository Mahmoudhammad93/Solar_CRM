<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettingVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'setting_id',
        'value',
        'type',
        'unit',
        'effective_from',
        'status',
        'source_reference',
        'is_sample',
        'changed_by',
        'change_summary',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'is_sample' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
