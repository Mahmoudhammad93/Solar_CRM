<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StockService
{
    public const STATES = ['available', 'reserved', 'allocated', 'issued', 'returned', 'damaged'];

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function allowNegativeStock(): bool
    {
        return (bool) $this->settings->get('inventory.allow_negative_stock', false);
    }

    public function reservationBehavior(): string
    {
        return (string) $this->settings->get('inventory.reservation_behavior', 'hard');
    }

    /**
     * Opening / goods receipt into available.
     */
    public function receive(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
        string $type = 'receive',
    ): StockMovement {
        $this->assertPositive($quantity);

        return $this->apply($product, $warehouse, $type, $quantity, [
            'available' => $quantity,
        ], $user, $reference, $reason, $meta);
    }

    public function reserve(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $this->assertPositive($quantity);

        return $this->apply($product, $warehouse, 'reserve', $quantity, [
            'available' => -$quantity,
            'reserved' => $quantity,
        ], $user, $reference, $reason, $meta);
    }

    public function unreserve(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $this->assertPositive($quantity);

        return $this->apply($product, $warehouse, 'unreserve', $quantity, [
            'available' => $quantity,
            'reserved' => -$quantity,
        ], $user, $reference, $reason, $meta);
    }

    public function allocate(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $this->assertPositive($quantity);

        return $this->apply($product, $warehouse, 'allocate', $quantity, [
            'reserved' => -$quantity,
            'allocated' => $quantity,
        ], $user, $reference, $reason, $meta);
    }

    public function deallocate(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $this->assertPositive($quantity);

        return $this->apply($product, $warehouse, 'deallocate', $quantity, [
            'reserved' => $quantity,
            'allocated' => -$quantity,
        ], $user, $reference, $reason, $meta);
    }

    public function issue(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
        bool $fromAvailable = true,
    ): StockMovement {
        $this->assertPositive($quantity);

        $deltas = $fromAvailable
            ? ['available' => -$quantity, 'issued' => $quantity]
            : ['allocated' => -$quantity, 'issued' => $quantity];

        return $this->apply($product, $warehouse, 'issue', $quantity, $deltas, $user, $reference, $reason, $meta);
    }

    public function returnStock(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $this->assertPositive($quantity);

        return $this->apply($product, $warehouse, 'return', $quantity, [
            'available' => $quantity,
            'returned' => $quantity,
            'issued' => -$quantity,
        ], $user, $reference, $reason, $meta);
    }

    public function markDamaged(
        Product|int $product,
        Warehouse|int $warehouse,
        float $quantity,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $this->assertPositive($quantity);

        return $this->apply($product, $warehouse, 'damage', $quantity, [
            'available' => -$quantity,
            'damaged' => $quantity,
        ], $user, $reference, $reason, $meta);
    }

    /**
     * @param  array<string, float>  $deltas  keyed by state
     */
    public function adjust(
        Product|int $product,
        Warehouse|int $warehouse,
        array $deltas,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $qty = 0.0;
        foreach ($deltas as $delta) {
            $qty += abs((float) $delta);
        }

        return $this->apply($product, $warehouse, 'adjust', $qty, $deltas, $user, $reference, $reason, $meta);
    }

    /**
     * @param  array<string, float>  $deltas
     */
    public function apply(
        Product|int $product,
        Warehouse|int $warehouse,
        string $movementType,
        float $quantity,
        array $deltas,
        ?User $user = null,
        ?string $reference = null,
        ?string $reason = null,
        array $meta = [],
    ): StockMovement {
        $productId = $product instanceof Product ? $product->id : (int) $product;
        $warehouseId = $warehouse instanceof Warehouse ? $warehouse->id : (int) $warehouse;

        return DB::transaction(function () use ($productId, $warehouseId, $movementType, $quantity, $deltas, $user, $reference, $reason, $meta) {
            $balance = StockBalance::query()
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = StockBalance::query()->create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'available' => 0,
                    'reserved' => 0,
                    'allocated' => 0,
                    'issued' => 0,
                    'returned' => 0,
                    'damaged' => 0,
                ]);
                $balance = StockBalance::query()->whereKey($balance->id)->lockForUpdate()->firstOrFail();
            }

            $projected = [];
            foreach (self::STATES as $state) {
                $delta = (float) ($deltas[$state] ?? 0);
                $projected[$state] = (float) $balance->{$state} + $delta;
            }

            $this->assertNonNegativeOrAuthorized($projected, $user, $productId, $warehouseId, $movementType);

            foreach (self::STATES as $state) {
                $balance->{$state} = $projected[$state];
            }
            $balance->save();

            $movement = StockMovement::query()->create([
                'moved_at' => now(),
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'reference' => $reference,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'available_delta' => (float) ($deltas['available'] ?? 0),
                'reserved_delta' => (float) ($deltas['reserved'] ?? 0),
                'allocated_delta' => (float) ($deltas['allocated'] ?? 0),
                'issued_delta' => (float) ($deltas['issued'] ?? 0),
                'returned_delta' => (float) ($deltas['returned'] ?? 0),
                'damaged_delta' => (float) ($deltas['damaged'] ?? 0),
                'user_id' => $user?->id,
                'reason' => $reason,
                'meta' => $meta ?: null,
            ]);

            $this->auditLogger->log(
                action: 'stock.'.$movementType,
                entityType: Product::class,
                entityId: $productId,
                entityReference: $reference,
                summary: [
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'deltas' => $deltas,
                    'balances' => $projected,
                ],
            );

            return $movement;
        });
    }

    /**
     * Recompute balances from movements and return mismatches.
     *
     * @return list<array{warehouse_id:int,product_id:int,field:string,ledger:float,balance:float}>
     */
    public function reconcile(?int $productId = null, ?int $warehouseId = null, bool $repair = false): array
    {
        $query = StockMovement::query()
            ->selectRaw('warehouse_id, product_id,
                COALESCE(SUM(available_delta),0) as available,
                COALESCE(SUM(reserved_delta),0) as reserved,
                COALESCE(SUM(allocated_delta),0) as allocated,
                COALESCE(SUM(issued_delta),0) as issued,
                COALESCE(SUM(returned_delta),0) as returned,
                COALESCE(SUM(damaged_delta),0) as damaged')
            ->groupBy('warehouse_id', 'product_id');

        if ($productId) {
            $query->where('product_id', $productId);
        }
        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        $ledgerRows = $query->get();
        $mismatches = [];

        foreach ($ledgerRows as $row) {
            $balance = StockBalance::query()
                ->where('warehouse_id', $row->warehouse_id)
                ->where('product_id', $row->product_id)
                ->first();

            foreach (self::STATES as $state) {
                $ledger = round((float) $row->{$state}, 3);
                $current = round((float) ($balance?->{$state} ?? 0), 3);
                if (abs($ledger - $current) > 0.0001) {
                    $mismatches[] = [
                        'warehouse_id' => (int) $row->warehouse_id,
                        'product_id' => (int) $row->product_id,
                        'field' => $state,
                        'ledger' => $ledger,
                        'balance' => $current,
                    ];
                    if ($repair && $balance) {
                        $balance->{$state} = $ledger;
                    }
                }
            }

            if ($repair && $balance) {
                $balance->save();
            } elseif ($repair && ! $balance) {
                StockBalance::query()->create([
                    'warehouse_id' => $row->warehouse_id,
                    'product_id' => $row->product_id,
                    'available' => $row->available,
                    'reserved' => $row->reserved,
                    'allocated' => $row->allocated,
                    'issued' => $row->issued,
                    'returned' => $row->returned,
                    'damaged' => $row->damaged,
                ]);
            }
        }

        return $mismatches;
    }

    /**
     * @param  array<string, float>  $projected
     */
    private function assertNonNegativeOrAuthorized(array $projected, ?User $user, int $productId, int $warehouseId, string $type): void
    {
        $negatives = [];
        foreach (['available', 'reserved', 'allocated'] as $state) {
            if ($projected[$state] < -0.0001) {
                $negatives[$state] = $projected[$state];
            }
        }

        if ($negatives === []) {
            return;
        }

        $allow = $this->allowNegativeStock();
        $canOverride = $user?->isSuperAdmin() || $user?->hasPermission('inventory.settings');

        if ($allow && $canOverride) {
            $this->auditLogger->log(
                action: 'stock.negative_allowed',
                entityType: Product::class,
                entityId: $productId,
                entityReference: null,
                summary: [
                    'warehouse_id' => $warehouseId,
                    'movement_type' => $type,
                    'negatives' => $negatives,
                    'user_id' => $user?->id,
                    'policy' => 'inventory.allow_negative_stock=true',
                ],
            );

            return;
        }

        throw ValidationException::withMessages([
            'quantity' => 'Insufficient stock: would make '.implode(', ', array_map(
                fn ($k, $v) => "{$k}={$v}",
                array_keys($negatives),
                $negatives,
            )).' (negative stock blocked by policy).',
        ]);
    }

    private function assertPositive(float $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be positive.');
        }
    }
}
