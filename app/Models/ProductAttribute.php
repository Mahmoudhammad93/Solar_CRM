<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = [
        'category',
        'attribute_key',
        'label',
        'data_type',
        'unit',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category)->where('is_active', true)->orderBy('sort_order');
    }
}
