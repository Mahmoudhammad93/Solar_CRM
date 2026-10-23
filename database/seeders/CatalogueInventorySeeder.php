<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\NumberingService;
use App\Services\StockService;
use Illuminate\Database\Seeder;

class CatalogueInventorySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAttributes();

        $vendors = [
            ['name' => 'HelioCraft Supplies', 'contact_name' => 'Amina Farid', 'email' => 'sales@heliocraft.test', 'phone' => '+20 100 111 0001', 'address' => 'Cairo Demo Zone A', 'tax_identifier' => 'TAX-HC-1001', 'status' => 'active', 'notes' => 'Panels & structures'],
            ['name' => 'VoltNest Trading', 'contact_name' => 'Omar Said', 'email' => 'orders@voltnest.test', 'phone' => '+20 100 111 0002', 'address' => 'Alexandria Demo Port', 'tax_identifier' => 'TAX-VN-2002', 'status' => 'active', 'notes' => 'Inverters & batteries'],
            ['name' => 'AquaPulse Agro', 'contact_name' => 'Layla Hassan', 'email' => 'pump@aquapulse.test', 'phone' => '+20 100 111 0003', 'address' => 'Delta Farm Hub', 'tax_identifier' => 'TAX-AP-3003', 'status' => 'active', 'notes' => 'Pumps & EV chargers'],
        ];

        $vendorModels = [];
        foreach ($vendors as $row) {
            $vendorModels[] = Vendor::query()->updateOrCreate(['name' => $row['name']], $row);
        }

        $branch = Branch::query()->firstOrCreate(
            ['code' => 'HQ'],
            ['name' => 'Headquarters', 'is_active' => true],
        );

        $whMain = Warehouse::query()->updateOrCreate(
            ['code' => 'WH-CAI'],
            ['name' => 'Cairo Central Warehouse', 'branch_id' => $branch->id, 'address' => '12 Demo Industrial Estate', 'is_active' => true],
        );
        $whNorth = Warehouse::query()->updateOrCreate(
            ['code' => 'WH-ALX'],
            ['name' => 'Alexandria Branch Store', 'branch_id' => $branch->id, 'address' => 'North Coast Demo Yard', 'is_active' => true],
        );

        $numbering = app(NumberingService::class);
        $stock = app(StockService::class);

        $catalog = $this->catalog($vendorModels);

        foreach ($catalog as $item) {
            $opening = (float) ($item['opening'] ?? 20);
            $openingNorth = (float) ($item['opening_north'] ?? 0);
            unset($item['opening'], $item['opening_north']);

            $product = Product::query()->updateOrCreate(
                ['sku' => $item['sku']],
                array_merge($item, [
                    'reference' => Product::query()->where('sku', $item['sku'])->value('reference')
                        ?? $numbering->next('product'),
                ]),
            );

            $existing = (float) $product->balances()->where('warehouse_id', $whMain->id)->value('available');
            if ($existing <= 0 && $opening > 0) {
                $stock->receive($product, $whMain, $opening, null, 'OPEN-'.$product->sku, 'Seed opening stock', [], 'opening');
            }

            if ($openingNorth > 0) {
                $north = (float) $product->balances()->where('warehouse_id', $whNorth->id)->value('available');
                if ($north <= 0) {
                    $stock->receive($product, $whNorth, $openingNorth, null, 'OPEN-N-'.$product->sku, 'Seed north stock', [], 'opening');
                }
            }
        }
    }

    private function seedAttributes(): void
    {
        $defs = [
            'solar_panels' => [
                ['wattage_w', 'Wattage', 'number', 'W'],
                ['efficiency_pct', 'Efficiency', 'number', '%'],
                ['cell_type', 'Cell type', 'string', null],
            ],
            'hybrid_inverters' => [
                ['capacity_kw', 'Capacity', 'number', 'kW'],
                ['phase', 'Phase', 'string', null],
                ['mppt_count', 'MPPT count', 'number', null],
            ],
            'batteries' => [
                ['capacity_kwh', 'Capacity', 'number', 'kWh'],
                ['voltage_v', 'Voltage', 'number', 'V'],
                ['chemistry', 'Chemistry', 'string', null],
            ],
            'pumps' => [
                ['horsepower_hp', 'Horsepower', 'number', 'HP'],
                ['head_m', 'Head', 'number', 'm'],
                ['flow_lpm', 'Flow', 'number', 'L/min'],
            ],
            'ev_chargers' => [
                ['power_kw', 'Power', 'number', 'kW'],
                ['connector', 'Connector', 'string', null],
            ],
            'structures' => [
                ['mount_type', 'Mount type', 'string', null],
                ['material', 'Material', 'string', null],
            ],
            'wiring_protection' => [
                ['rating_a', 'Current rating', 'number', 'A'],
                ['cable_mm2', 'Cable size', 'number', 'mm²'],
            ],
            'accessories' => [
                ['accessory_type', 'Type', 'string', null],
            ],
            'services' => [
                ['service_scope', 'Scope', 'string', null],
            ],
        ];

        foreach ($defs as $category => $attrs) {
            foreach ($attrs as $i => [$key, $label, $type, $unit]) {
                ProductAttribute::query()->updateOrCreate(
                    ['category' => $category, 'attribute_key' => $key],
                    ['label' => $label, 'data_type' => $type, 'unit' => $unit, 'sort_order' => $i, 'is_active' => true],
                );
            }
        }
    }

    /**
     * @param  list<Vendor>  $vendors
     * @return list<array<string, mixed>>
     */
    private function catalog(array $vendors): array
    {
        [$v1, $v2, $v3] = $vendors;

        return [
            // Panels (4)
            ['sku' => 'PNL-HC-550', 'category' => 'solar_panels', 'brand' => 'HelioCraft', 'model' => 'HC-550M', 'title' => 'HelioCraft 550W Mono Panel', 'unit' => 'pcs', 'purchase_cost' => 85, 'sale_price' => 120, 'gst_rate' => 18, 'warranty_months' => 300, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 10, 'tracks_serial_batch' => true, 'specs' => ['wattage_w' => 550, 'efficiency_pct' => 21.2, 'cell_type' => 'Mono PERC'], 'opening' => 80, 'opening_north' => 20],
            ['sku' => 'PNL-HC-450', 'category' => 'solar_panels', 'brand' => 'HelioCraft', 'model' => 'HC-450M', 'title' => 'HelioCraft 450W Mono Panel', 'unit' => 'pcs', 'purchase_cost' => 70, 'sale_price' => 99, 'gst_rate' => 18, 'warranty_months' => 300, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 15, 'tracks_serial_batch' => true, 'specs' => ['wattage_w' => 450, 'efficiency_pct' => 20.5, 'cell_type' => 'Mono'], 'opening' => 8], // low
            ['sku' => 'PNL-SUN-400', 'category' => 'solar_panels', 'brand' => 'SunLattice', 'model' => 'SL-400', 'title' => 'SunLattice 400W Poly Panel', 'unit' => 'pcs', 'purchase_cost' => 55, 'sale_price' => 78, 'gst_rate' => 18, 'warranty_months' => 240, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 12, 'specs' => ['wattage_w' => 400, 'efficiency_pct' => 19.1, 'cell_type' => 'Poly'], 'opening' => 40],
            ['sku' => 'PNL-SUN-600', 'category' => 'solar_panels', 'brand' => 'SunLattice', 'model' => 'SL-600B', 'title' => 'SunLattice 600W Bifacial', 'unit' => 'pcs', 'purchase_cost' => 110, 'sale_price' => 155, 'gst_rate' => 18, 'warranty_months' => 300, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 6, 'specs' => ['wattage_w' => 600, 'efficiency_pct' => 22.0, 'cell_type' => 'Bifacial'], 'opening' => 25],

            // Hybrid inverters (3)
            ['sku' => 'INV-VN-5K', 'category' => 'hybrid_inverters', 'brand' => 'VoltNest', 'model' => 'VN-HY5', 'title' => 'VoltNest Hybrid 5kW', 'unit' => 'pcs', 'purchase_cost' => 420, 'sale_price' => 590, 'gst_rate' => 18, 'warranty_months' => 60, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 3, 'tracks_serial_batch' => true, 'specs' => ['capacity_kw' => 5, 'phase' => 'single', 'mppt_count' => 2], 'opening' => 12],
            ['sku' => 'INV-VN-8K', 'category' => 'hybrid_inverters', 'brand' => 'VoltNest', 'model' => 'VN-HY8', 'title' => 'VoltNest Hybrid 8kW', 'unit' => 'pcs', 'purchase_cost' => 610, 'sale_price' => 850, 'gst_rate' => 18, 'warranty_months' => 60, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 2, 'tracks_serial_batch' => true, 'specs' => ['capacity_kw' => 8, 'phase' => 'three', 'mppt_count' => 3], 'opening' => 2], // low
            ['sku' => 'INV-ARC-10K', 'category' => 'hybrid_inverters', 'brand' => 'ArcForge', 'model' => 'AF-H10', 'title' => 'ArcForge Hybrid 10kW', 'unit' => 'pcs', 'purchase_cost' => 780, 'sale_price' => 1090, 'gst_rate' => 18, 'warranty_months' => 72, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 2, 'specs' => ['capacity_kw' => 10, 'phase' => 'three', 'mppt_count' => 4], 'opening' => 6, 'opening_north' => 2],

            // Batteries (3)
            ['sku' => 'BAT-VN-5KWH', 'category' => 'batteries', 'brand' => 'VoltNest', 'model' => 'VN-Li5', 'title' => 'VoltNest LiFePO4 5kWh', 'unit' => 'pcs', 'purchase_cost' => 900, 'sale_price' => 1250, 'gst_rate' => 18, 'warranty_months' => 120, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 4, 'tracks_serial_batch' => true, 'specs' => ['capacity_kwh' => 5, 'voltage_v' => 48, 'chemistry' => 'LiFePO4'], 'opening' => 15],
            ['sku' => 'BAT-VN-10KWH', 'category' => 'batteries', 'brand' => 'VoltNest', 'model' => 'VN-Li10', 'title' => 'VoltNest LiFePO4 10kWh', 'unit' => 'pcs', 'purchase_cost' => 1600, 'sale_price' => 2200, 'gst_rate' => 18, 'warranty_months' => 120, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 3, 'specs' => ['capacity_kwh' => 10, 'voltage_v' => 48, 'chemistry' => 'LiFePO4'], 'opening' => 3], // at threshold
            ['sku' => 'BAT-ARC-15KWH', 'category' => 'batteries', 'brand' => 'ArcForge', 'model' => 'AF-B15', 'title' => 'ArcForge Stack 15kWh', 'unit' => 'pcs', 'purchase_cost' => 2300, 'sale_price' => 3100, 'gst_rate' => 18, 'warranty_months' => 120, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 2, 'specs' => ['capacity_kwh' => 15, 'voltage_v' => 51.2, 'chemistry' => 'LiFePO4'], 'opening' => 5],

            // Pumps (3)
            ['sku' => 'PMP-AP-3HP', 'category' => 'pumps', 'brand' => 'AquaPulse', 'model' => 'AP-S3', 'title' => 'AquaPulse Solar Pump 3HP', 'unit' => 'pcs', 'purchase_cost' => 480, 'sale_price' => 680, 'gst_rate' => 18, 'warranty_months' => 24, 'vendor_id' => $v3->id, 'status' => 'active', 'reorder_threshold' => 2, 'specs' => ['horsepower_hp' => 3, 'head_m' => 50, 'flow_lpm' => 180], 'opening' => 7],
            ['sku' => 'PMP-AP-5HP', 'category' => 'pumps', 'brand' => 'AquaPulse', 'model' => 'AP-S5', 'title' => 'AquaPulse Solar Pump 5HP', 'unit' => 'pcs', 'purchase_cost' => 720, 'sale_price' => 990, 'gst_rate' => 18, 'warranty_months' => 24, 'vendor_id' => $v3->id, 'status' => 'active', 'reorder_threshold' => 2, 'specs' => ['horsepower_hp' => 5, 'head_m' => 80, 'flow_lpm' => 250], 'opening' => 1], // low
            ['sku' => 'PMP-DELTA-2HP', 'category' => 'pumps', 'brand' => 'DeltaWell', 'model' => 'DW-2', 'title' => 'DeltaWell Surface Pump 2HP', 'unit' => 'pcs', 'purchase_cost' => 310, 'sale_price' => 450, 'gst_rate' => 18, 'warranty_months' => 18, 'vendor_id' => $v3->id, 'status' => 'active', 'reorder_threshold' => 3, 'specs' => ['horsepower_hp' => 2, 'head_m' => 30, 'flow_lpm' => 120], 'opening' => 9],

            // EV chargers (2)
            ['sku' => 'EVC-AP-7K', 'category' => 'ev_chargers', 'brand' => 'AquaPulse', 'model' => 'AP-EV7', 'title' => 'ChargeBay AC 7kW Wallbox', 'unit' => 'pcs', 'purchase_cost' => 350, 'sale_price' => 520, 'gst_rate' => 18, 'warranty_months' => 36, 'vendor_id' => $v3->id, 'status' => 'active', 'reorder_threshold' => 2, 'specs' => ['power_kw' => 7, 'connector' => 'Type 2'], 'opening' => 6],
            ['sku' => 'EVC-AP-22K', 'category' => 'ev_chargers', 'brand' => 'ChargeBay', 'model' => 'CB-22', 'title' => 'ChargeBay AC 22kW', 'unit' => 'pcs', 'purchase_cost' => 780, 'sale_price' => 1100, 'gst_rate' => 18, 'warranty_months' => 36, 'vendor_id' => $v3->id, 'status' => 'active', 'reorder_threshold' => 1, 'specs' => ['power_kw' => 22, 'connector' => 'Type 2'], 'opening' => 4],

            // Structures (2)
            ['sku' => 'STR-HC-ROOF', 'category' => 'structures', 'brand' => 'HelioCraft', 'model' => 'HC-RM', 'title' => 'Roof Mount Kit (per kW)', 'unit' => 'set', 'purchase_cost' => 45, 'sale_price' => 75, 'gst_rate' => 18, 'warranty_months' => 60, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 10, 'specs' => ['mount_type' => 'roof', 'material' => 'GI'], 'opening' => 50],
            ['sku' => 'STR-HC-GROUND', 'category' => 'structures', 'brand' => 'HelioCraft', 'model' => 'HC-GM', 'title' => 'Ground Mount Kit (per kW)', 'unit' => 'set', 'purchase_cost' => 65, 'sale_price' => 105, 'gst_rate' => 18, 'warranty_months' => 60, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 8, 'specs' => ['mount_type' => 'ground', 'material' => 'HDG steel'], 'opening' => 30],

            // Wiring / protection (3)
            ['sku' => 'WR-DC-6MM', 'category' => 'wiring_protection', 'brand' => 'CableCraft', 'model' => 'CC-DC6', 'title' => 'DC Solar Cable 6mm²', 'unit' => 'm', 'purchase_cost' => 0.8, 'sale_price' => 1.4, 'gst_rate' => 18, 'warranty_months' => 12, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 200, 'specs' => ['rating_a' => 40, 'cable_mm2' => 6], 'opening' => 150], // low vs threshold
            ['sku' => 'WR-MCB-32', 'category' => 'wiring_protection', 'brand' => 'SafeGrid', 'model' => 'SG-32', 'title' => 'DC MCB 32A', 'unit' => 'pcs', 'purchase_cost' => 8, 'sale_price' => 14, 'gst_rate' => 18, 'warranty_months' => 12, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 20, 'specs' => ['rating_a' => 32, 'cable_mm2' => null], 'opening' => 60],
            ['sku' => 'WR-SPD-T2', 'category' => 'wiring_protection', 'brand' => 'SafeGrid', 'model' => 'SG-SPD2', 'title' => 'SPD Type 2 DC', 'unit' => 'pcs', 'purchase_cost' => 22, 'sale_price' => 38, 'gst_rate' => 18, 'warranty_months' => 24, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 10, 'specs' => ['rating_a' => null, 'cable_mm2' => null], 'opening' => 25],

            // Accessories (2)
            ['sku' => 'ACC-MC4', 'category' => 'accessories', 'brand' => 'CableCraft', 'model' => 'MC4-PAIR', 'title' => 'MC4 Connector Pair', 'unit' => 'pair', 'purchase_cost' => 1.2, 'sale_price' => 2.5, 'gst_rate' => 18, 'warranty_months' => 12, 'vendor_id' => $v1->id, 'status' => 'active', 'reorder_threshold' => 50, 'specs' => ['accessory_type' => 'connector'], 'opening' => 200],
            ['sku' => 'ACC-EARTH', 'category' => 'accessories', 'brand' => 'SafeGrid', 'model' => 'SG-EARTH', 'title' => 'Earthing Kit', 'unit' => 'set', 'purchase_cost' => 18, 'sale_price' => 32, 'gst_rate' => 18, 'warranty_months' => 12, 'vendor_id' => $v2->id, 'status' => 'active', 'reorder_threshold' => 15, 'specs' => ['accessory_type' => 'earthing'], 'opening' => 40],

            // Services (3)
            ['sku' => 'SRV-INSTALL', 'category' => 'services', 'brand' => 'Rootflash Demo', 'model' => 'INST', 'title' => 'Installation Labour (per kW)', 'unit' => 'kW', 'purchase_cost' => 40, 'sale_price' => 90, 'gst_rate' => 18, 'warranty_months' => 12, 'vendor_id' => null, 'status' => 'active', 'reorder_threshold' => 0, 'specs' => ['service_scope' => 'installation'], 'opening' => 999],
            ['sku' => 'SRV-TRANSPORT', 'category' => 'services', 'brand' => 'Rootflash Demo', 'model' => 'TRN', 'title' => 'Transport / Logistics', 'unit' => 'job', 'purchase_cost' => 50, 'sale_price' => 120, 'gst_rate' => 18, 'warranty_months' => 0, 'vendor_id' => null, 'status' => 'active', 'reorder_threshold' => 0, 'specs' => ['service_scope' => 'transport'], 'opening' => 999],
            ['sku' => 'SRV-AMC-1Y', 'category' => 'services', 'brand' => 'Rootflash Demo', 'model' => 'AMC1', 'title' => 'AMC Care Plan 1 Year', 'unit' => 'contract', 'purchase_cost' => 80, 'sale_price' => 180, 'gst_rate' => 18, 'warranty_months' => 12, 'vendor_id' => null, 'status' => 'active', 'reorder_threshold' => 0, 'specs' => ['service_scope' => 'amc'], 'opening' => 999],
        ];
    }
}
