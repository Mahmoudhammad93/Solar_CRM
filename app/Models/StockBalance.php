<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    protected $fillable = [
        'warehouse_id',
        'product_id',
        'available',
        'reserved',
        'allocated',
        'issued',
        'returned',
        'damaged',
    ];

    protected function casts(): array
    {
        return [
            'available' => 'decimal:3',
            'reserved' => 'decimal:3',
            'allocated' => 'decimal:3',
            'issued' => 'decimal:3',
            'returned' => 'decimal:3',
            'damaged' => 'decimal:3',
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
}
