<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            'lead_source' => [
                ['website', 'Website'],
                ['referral', 'Referral'],
                ['walk_in', 'Walk-in'],
                ['phone', 'Phone inquiry'],
                ['campaign', 'Campaign'],
                ['partner', 'Partner'],
            ],
            'lead_stage' => [
                ['new', 'New'],
                ['contacted', 'Contacted'],
                ['qualified', 'Qualified'],
                ['proposal', 'Proposal'],
                ['negotiation', 'Negotiation'],
                ['won', 'Won'],
                ['converted', 'Converted'],
                ['lost', 'Lost'],
            ],
            'customer_type' => [
                ['residential', 'Residential'],
                ['commercial', 'Commercial'],
                ['industrial', 'Industrial'],
                ['agricultural', 'Agricultural'],
            ],
            'property_type' => [
                ['rcc_roof', 'RCC roof'],
                ['metal_roof', 'Metal roof'],
                ['ground_mount', 'Ground mount'],
                ['carport', 'Carport'],
            ],
            'property_segment' => [
                ['villa', 'Villa'],
                ['apartment', 'Apartment'],
                ['factory', 'Factory'],
                ['farm', 'Farm'],
                ['office', 'Office'],
            ],
            'system_type' => [
                ['hybrid', 'Hybrid'],
                ['solar_water_pump', 'Solar water pump'],
                ['ev_charging', 'EV charging'],
                ['other', 'Other'],
            ],
            'priority' => [
                ['low', 'Low'],
                ['medium', 'Medium'],
                ['high', 'High'],
                ['urgent', 'Urgent'],
            ],
            'loss_reason' => [
                ['price', 'Price'],
                ['competitor', 'Competitor'],
                ['no_budget', 'No budget'],
                ['timeline', 'Timeline'],
                ['not_feasible', 'Not feasible'],
                ['unresponsive', 'Unresponsive'],
            ],
            'task_type' => [
                ['call', 'Call'],
                ['meeting', 'Meeting'],
                ['follow_up', 'Follow-up'],
                ['site_prep', 'Site prep'],
                ['other', 'Other'],
            ],
            'task_status' => [
                ['open', 'Open'],
                ['in_progress', 'In progress'],
                ['completed', 'Completed'],
                ['cancelled', 'Cancelled'],
            ],
            'budget_range' => [
                ['under_50k', 'Under 50k'],
                ['50k_100k', '50k–100k'],
                ['100k_250k', '100k–250k'],
                ['250k_plus', '250k+'],
            ],
            'visit_status' => [
                ['scheduled', 'Scheduled'],
                ['in_progress', 'In progress'],
                ['completed', 'Completed'],
                ['rescheduled', 'Rescheduled'],
                ['cancelled', 'Cancelled'],
            ],
            'document_type' => [
                ['site_photo', 'Site photo'],
                ['electrical_room_photo', 'Electrical room / meter photo'],
                ['bill_evidence', 'Bill / consumption evidence'],
                ['visit_report', 'Visit report'],
                ['signed_proposal', 'Signed proposal'],
                ['warranty', 'Warranty'],
                ['general', 'General'],
                ['sensitive_id', 'Sensitive ID (restricted)'],
            ],
            'phase_type' => [
                ['single', 'Single-phase'],
                ['three', 'Three-phase'],
            ],
            'feasibility' => [
                ['feasible', 'Feasible'],
                ['conditional', 'Conditional'],
                ['not_feasible', 'Not feasible'],
            ],
            'product_category' => [
                ['solar_panels', 'Solar panels'],
                ['hybrid_inverters', 'Hybrid inverters'],
                ['batteries', 'Batteries'],
                ['pumps', 'Pumps'],
                ['ev_chargers', 'EV chargers'],
                ['structures', 'Structures'],
                ['wiring_protection', 'Wiring / protection'],
                ['accessories', 'Accessories'],
                ['services', 'Services'],
            ],
            'product_unit' => [
                ['pcs', 'Pieces'],
                ['set', 'Set'],
                ['m', 'Meter'],
                ['pair', 'Pair'],
                ['kW', 'kW'],
                ['job', 'Job'],
                ['contract', 'Contract'],
            ],
            'product_brand' => [
                ['HelioCraft', 'HelioCraft'],
                ['SunLattice', 'SunLattice'],
                ['VoltNest', 'VoltNest'],
                ['ArcForge', 'ArcForge'],
                ['AquaPulse', 'AquaPulse'],
                ['DeltaWell', 'DeltaWell'],
                ['ChargeBay', 'ChargeBay'],
                ['CableCraft', 'CableCraft'],
                ['SafeGrid', 'SafeGrid'],
                ['Rootflash Demo', 'Rootflash Demo'],
            ],
        ];

        foreach ($groups as $type => $rows) {
            foreach ($rows as $index => [$value, $label]) {
                Lookup::query()->updateOrCreate(
                    ['type' => $type, 'value' => $value],
                    [
                        'label' => $label,
                        'sort_order' => ($index + 1) * 10,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
