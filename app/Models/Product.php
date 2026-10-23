<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    protected $fillable = [
        'reference',
        'sku',
        'category',
        'brand',
        'model',
        'title',
        'description',
        'specs',
        'unit',
        'purchase_cost',
        'sale_price',
        'gst_rate',
        'warranty_months',
        'vendor_id',
        'status',
        'image_path',
        'tracks_serial_batch',
        'reorder_threshold',
        'is_archived',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'specs' => 'array',
            'purchase_cost' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'gst_rate' => 'decimal:2',
            'reorder_threshold' => 'decimal:3',
            'tracks_serial_batch' => 'boolean',
            'is_archived' => 'boolean',
            'warranty_months' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false)->where('status', '!=', 'archived');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('is_archived', true)->orWhere('status', 'archived');
        });
    }

    public function availableStock(): float
    {
        return (float) $this->balances()->sum('available');
    }

    public function isLowStock(): bool
    {
        return $this->availableStock() <= (float) $this->reorder_threshold;
    }

    public function archive(): void
    {
        $this->update([
            'status' => 'archived',
            'is_archived' => true,
        ]);
    }
}
