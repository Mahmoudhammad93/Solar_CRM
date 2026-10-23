<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Lookup extends Model
{
    protected $fillable = [
        'type',
        'value',
        'label',
        'sort_order',
        'is_active',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'meta' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Options for forms/filters. Always includes $preserveValue even if deactivated.
     *
     * @return array<string, string>
     */
    public static function options(string $type, string|array|null $preserveValue = null): array
    {
        $preserve = collect(is_array($preserveValue) ? $preserveValue : [$preserveValue])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->values();

        return static::query()
            ->ofType($type)
            ->where(function (Builder $q) use ($preserve) {
                $q->where('is_active', true);
                if ($preserve->isNotEmpty()) {
                    $q->orWhereIn('value', $preserve->all());
                }
            })
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->mapWithKeys(fn (self $row) => [
                $row->value => $row->is_active ? $row->label : $row->label.' (inactive)',
            ])
            ->all();
    }

    /**
     * @return Collection<int, string>
     */
    public static function types(): Collection
    {
        return collect([
            'lead_source' => 'Lead sources',
            'lead_stage' => 'Lead stages',
            'customer_type' => 'Customer types',
            'property_type' => 'Property / roof-site types',
            'property_segment' => 'Property segments',
            'system_type' => 'System types',
            'priority' => 'Priorities',
            'loss_reason' => 'Loss reasons',
            'task_type' => 'Task types',
            'task_status' => 'Task statuses',
            'budget_range' => 'Budget ranges',
            'visit_status' => 'Visit statuses',
            'document_type' => 'Document types',
            'phase_type' => 'Electrical phase types',
            'feasibility' => 'Feasibility outcomes',
            'product_category' => 'Product categories',
            'product_unit' => 'Product units',
            'product_brand' => 'Product brands',
        ]);
    }
}
