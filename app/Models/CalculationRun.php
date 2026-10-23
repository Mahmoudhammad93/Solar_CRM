<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculationRun extends Model
{
    protected $fillable = [
        'reference',
        'calculator_type',
        'lead_id',
        'customer_id',
        'visit_id',
        'inputs',
        'assumptions',
        'steps',
        'outputs',
        'disclaimer',
        'created_by',
        'rerun_of_id',
    ];

    protected function casts(): array
    {
        return [
            'inputs' => 'array',
            'assumptions' => 'array',
            'steps' => 'array',
            'outputs' => 'array',
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

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function rerunOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rerun_of_id');
    }
}
