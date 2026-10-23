<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMaterial extends Model
{
    protected $fillable = [
        'project_id',
        'product_id',
        'sku',
        'title',
        'unit',
        'quantity_required',
        'quantity_reserved',
        'quantity_allocated',
        'quantity_issued',
        'warehouse_id',
        'is_service',
    ];

    protected function casts(): array
    {
        return [
            'quantity_required' => 'decimal:3',
            'quantity_reserved' => 'decimal:3',
            'quantity_allocated' => 'decimal:3',
            'quantity_issued' => 'decimal:3',
            'is_service' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function availableStock(): float
    {
        if (! $this->product_id) {
            return 0;
        }
        $q = StockBalance::query()->where('product_id', $this->product_id);
        if ($this->warehouse_id) {
            $q->where('warehouse_id', $this->warehouse_id);
        }

        return (float) $q->sum('available');
    }

    public function shortage(): float
    {
        if ($this->is_service) {
            return 0;
        }
        $need = (float) $this->quantity_required - (float) $this->quantity_allocated - (float) $this->quantity_reserved;

        return max(0, $need - $this->availableStock());
    }

    public function hasShortage(): bool
    {
        return $this->shortage() > 0.0001;
    }
}
