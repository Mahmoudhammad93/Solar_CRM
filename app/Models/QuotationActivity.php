<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationActivity extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'quotation_id',
        'revision_id',
        'action',
        'from_status',
        'to_status',
        'notes',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(QuotationRevision::class, 'revision_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
