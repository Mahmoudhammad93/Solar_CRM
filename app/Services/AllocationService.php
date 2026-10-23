<?php

namespace App\Services;

use App\Models\ProjectMaterial;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Project material stock actions — always via StockService.
 */
class AllocationService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function reserve(ProjectMaterial $material, float $quantity, ?User $user = null): ProjectMaterial
    {
        $this->assertStockLine($material, $quantity);

        return DB::transaction(function () use ($material, $quantity, $user) {
            $this->stock->reserve(
                $material->product_id,
                $this->warehouseId($material),
                $quantity,
                $user,
                $this->ref($material),
                'Project reserve',
                ['project_material_id' => $material->id],
            );
            $material->quantity_reserved = (float) $material->quantity_reserved + $quantity;
            $material->save();

            return $material->fresh();
        });
    }

    public function allocate(ProjectMaterial $material, float $quantity, ?User $user = null): ProjectMaterial
    {
        $this->assertStockLine($material, $quantity);
        if ((float) $material->quantity_reserved + 0.0001 < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Allocate requires reserved qty first (or reserve more).',
            ]);
        }

        return DB::transaction(function () use ($material, $quantity, $user) {
            $this->stock->allocate(
                $material->product_id,
                $this->warehouseId($material),
                $quantity,
                $user,
                $this->ref($material),
                'Project allocate',
                ['project_material_id' => $material->id],
            );
            $material->quantity_reserved = (float) $material->quantity_reserved - $quantity;
            $material->quantity_allocated = (float) $material->quantity_allocated + $quantity;
            $material->save();

            return $material->fresh();
        });
    }

    public function issue(ProjectMaterial $material, float $quantity, ?User $user = null): ProjectMaterial
    {
        $this->assertStockLine($material, $quantity);

        return DB::transaction(function () use ($material, $quantity, $user) {
            // Prefer from allocated; StockService issue from available if needed
            if ((float) $material->quantity_allocated + 0.0001 >= $quantity) {
                $this->stock->issue(
                    $material->product_id,
                    $this->warehouseId($material),
                    $quantity,
                    $user,
                    $this->ref($material),
                    'Project issue',
                    ['project_material_id' => $material->id],
                    fromAvailable: false,
                );
                $material->quantity_allocated = (float) $material->quantity_allocated - $quantity;
            } else {
                $this->stock->issue(
                    $material->product_id,
                    $this->warehouseId($material),
                    $quantity,
                    $user,
                    $this->ref($material),
                    'Project issue from available',
                    ['project_material_id' => $material->id],
                    fromAvailable: true,
                );
            }
            $material->quantity_issued = (float) $material->quantity_issued + $quantity;
            $material->save();

            return $material->fresh();
        });
    }

    public function returnStock(ProjectMaterial $material, float $quantity, ?User $user = null): ProjectMaterial
    {
        $this->assertStockLine($material, $quantity);
        if ((float) $material->quantity_issued + 0.0001 < $quantity) {
            throw ValidationException::withMessages(['quantity' => 'Cannot return more than issued.']);
        }

        return DB::transaction(function () use ($material, $quantity, $user) {
            $this->stock->returnStock(
                $material->product_id,
                $this->warehouseId($material),
                $quantity,
                $user,
                $this->ref($material),
                'Project return',
                ['project_material_id' => $material->id],
            );
            $material->quantity_issued = (float) $material->quantity_issued - $quantity;
            $material->save();

            return $material->fresh();
        });
    }

    public function damage(ProjectMaterial $material, float $quantity, ?User $user = null): ProjectMaterial
    {
        $this->assertStockLine($material, $quantity);

        return DB::transaction(function () use ($material, $quantity, $user) {
            $this->stock->markDamaged(
                $material->product_id,
                $this->warehouseId($material),
                $quantity,
                $user,
                $this->ref($material),
                'Project damage',
                ['project_material_id' => $material->id],
            );

            return $material->fresh();
        });
    }

    /**
     * @return array{required: float, available: float, reserved: float, allocated: float, issued: float, shortage: float, has_shortage: bool}
     */
    public function availability(ProjectMaterial $material): array
    {
        $available = $material->availableStock();

        return [
            'required' => (float) $material->quantity_required,
            'available' => $available,
            'reserved' => (float) $material->quantity_reserved,
            'allocated' => (float) $material->quantity_allocated,
            'issued' => (float) $material->quantity_issued,
            'shortage' => $material->shortage(),
            'has_shortage' => $material->hasShortage(),
        ];
    }

    private function assertStockLine(ProjectMaterial $material, float $quantity): void
    {
        if ($material->is_service || ! $material->product_id) {
            throw ValidationException::withMessages(['material' => 'Service/non-catalogue lines are not stock-allocated.']);
        }
        if ($quantity <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Quantity must be > 0.']);
        }
        if (! $this->warehouseId($material)) {
            throw ValidationException::withMessages(['warehouse_id' => 'Warehouse is required for stock actions.']);
        }
    }

    private function warehouseId(ProjectMaterial $material): int
    {
        if ($material->warehouse_id) {
            return (int) $material->warehouse_id;
        }

        $id = Warehouse::query()->where('is_active', true)->orderBy('id')->value('id');
        if (! $id) {
            throw ValidationException::withMessages(['warehouse_id' => 'No active warehouse.']);
        }

        return (int) $id;
    }

    private function ref(ProjectMaterial $material): string
    {
        return 'PRJ-MAT-'.$material->project_id.'-'.$material->id;
    }
}
