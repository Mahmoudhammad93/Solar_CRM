<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $fillable = [
        'moved_at',
        'warehouse_id',
        'product_id',
        'reference',
        'movement_type',
        'quantity',
        'available_delta',
        'reserved_delta',
        'allocated_delta',
        'issued_delta',
        'returned_delta',
        'damaged_delta',
        'user_id',
        'reason',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
            'quantity' => 'decimal:3',
            'available_delta' => 'decimal:3',
            'reserved_delta' => 'decimal:3',
            'allocated_delta' => 'decimal:3',
            'issued_delta' => 'decimal:3',
            'returned_delta' => 'decimal:3',
            'damaged_delta' => 'decimal:3',
            'meta' => 'array',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
