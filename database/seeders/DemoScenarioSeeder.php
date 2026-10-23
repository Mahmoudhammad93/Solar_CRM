<?php

namespace Database\Seeders;

use App\Models\CalculationRun;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Project;
use App\Models\ProjectMaterial;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Visit;
use App\Models\Warehouse;
use App\Services\AmcService;
use App\Services\Calculators\EvCalculatorService;
use App\Services\Calculators\HybridCalculatorService;
use App\Services\Calculators\InvestmentCalculatorService;
use App\Services\ConvertQuotationToProject;
use App\Services\InvoiceService;
use App\Services\NumberingService;
use App\Services\ProjectMilestoneService;
use App\Services\QuotationService;
use App\Services\SettingsService;
use App\Services\TicketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DEMO ONLY — fictional Rootflash Solar walkthrough data.
 * Idempotent: skips if demo.scenario_version already matches (unless forced via DemoReset).
 */
class DemoScenarioSeeder extends Seeder
{
    public const VERSION = '12';

    public function run(): void
    {
        $settings = app(SettingsService::class);
        if ((string) $settings->get('demo.scenario_version') === self::VERSION
            && Lead::query()->where('campaign_details', 'DEMO-P12')->exists()) {
            $this->command?->info('DemoScenarioSeeder: already seeded (demo.scenario_version='.self::VERSION.'); skip.');

            return;
        }

        $admin = User::query()->where('email', 'admin@solar-crm.test')->firstOrFail();
        $manager = User::query()->where('email', 'manager@solar-crm.test')->firstOrFail();
        $exec = User::query()->where('email', 'executive@solar-crm.test')->firstOrFail();
        $surveyor = User::query()->where('email', 'surveyor@solar-crm.test')->firstOrFail();
        $pm = User::query()->where('email', 'pm@solar-crm.test')->firstOrFail();
        $ops = User::query()->where('email', 'ops@solar-crm.test')->firstOrFail();

        Auth::login($admin);

        DB::transaction(function () use ($admin, $manager, $exec, $surveyor, $pm, $ops, $settings) {
            $customers = $this->seedCustomers($admin);
            $leads = $this->seedLeads($admin, $manager, $exec, $customers);
            $visits = $this->seedVisits($admin, $surveyor, $leads, $customers);
            $calcs = $this->seedCalculations($admin, $exec, $leads, $customers, $visits);
            $quotes = $this->seedQuotations($admin, $manager, $exec, $customers, $leads, $calcs);
            $projects = $this->seedOrdersProjects($admin, $pm, $quotes);
            $this->seedShortage($projects[0] ?? null, $admin);
            $this->seedInvoices($admin, $projects);
            $this->seedTickets($admin, $ops, $customers, $projects);
            $this->seedAmc($admin, $ops, $customers, $projects);
            $this->seedTasks($admin, $exec, $manager, $leads);
            $this->seedNotifications($admin, $exec, $manager);

            $settings->set('demo.scenario_version', self::VERSION, [
                'group' => 'demo',
                'type' => 'string',
                'is_sample' => true,
                'change_summary' => 'DEMO ONLY scenario seed marker',
            ], $admin->id);
            $settings->set('demo.only', true, [
                'group' => 'demo',
                'type' => 'boolean',
                'is_sample' => true,
                'change_summary' => 'Guards demo data as non-production',
            ], $admin->id);
        });

        Auth::logout();
        $this->command?->info('DemoScenarioSeeder: fictional scenario ready (DEMO ONLY).');
    }

    /**
     * @return list<Customer>
     */
    private function seedCustomers(User $admin): array
    {
        $numbering = app(NumberingService::class);
        $defs = [
            ['Demo Customer Alpha', '9001001001', 'Maharashtra', 'Pune'],
            ['Demo Customer Beta', '9001001002', 'Karnataka', 'Bengaluru'],
            ['Demo Customer Gamma', '9001001003', 'Maharashtra', 'Nagpur'],
            ['Demo Farm Delta', '9001001004', 'Maharashtra', 'Nashik'],
            ['Demo Factory Epsilon', '9001001005', 'Gujarat', 'Ahmedabad'],
        ];
        $out = [];
        foreach ($defs as [$name, $mobile, $state, $city]) {
            $out[] = Customer::query()->updateOrCreate(
                ['mobile' => $mobile],
                [
                    'customer_number' => Customer::query()->where('mobile', $mobile)->value('customer_number')
                        ?? $numbering->next('customer'),
                    'name' => $name,
                    'customer_type' => 'residential',
                    'email' => strtolower(str_replace(' ', '.', $name)).'@demo.solar-crm.test',
                    'city' => $city,
                    'state' => $state,
                    'whatsapp_consent_status' => 'opted_in',
                    'whatsapp_consent_at' => now(),
                    'whatsapp_consent_source' => 'demo_seed',
                    'created_by' => $admin->id,
                ],
            );
        }

        return $out;
    }

    /**
     * @param  list<Customer>  $customers
     * @return list<Lead>
     */
    private function seedLeads(User $admin, User $manager, User $exec, array $customers): array
    {
        $numbering = app(NumberingService::class);
        $owners = [$exec, $exec, $manager, $admin, $exec];
        $stages = ['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost', 'converted', 'new', 'qualified', 'proposal', 'contacted', 'new', 'negotiation', 'qualified', 'proposal', 'new', 'contacted', 'qualified', 'lost'];
        $sources = ['website', 'referral', 'walk_in', 'phone', 'campaign', 'partner', 'website', 'referral', 'walk_in', 'phone', 'campaign', 'website', 'referral', 'phone', 'partner', 'website', 'walk_in', 'campaign', 'referral', 'phone'];
        $leads = [];
        for ($i = 0; $i < 20; $i++) {
            $mobile = '901200'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            $owner = $owners[$i % count($owners)];
            $customer = $customers[$i % count($customers)];
            $stage = $stages[$i];
            $lead = Lead::query()->updateOrCreate(
                ['mobile' => $mobile],
                [
                    'reference' => Lead::query()->where('mobile', $mobile)->value('reference')
                        ?? $numbering->next('lead'),
                    'name' => 'Demo Lead '.($i + 1),
                    'email' => 'lead'.($i + 1).'@demo.solar-crm.test',
                    'city' => $customer->city,
                    'state' => $customer->state,
                    'source' => $sources[$i],
                    'stage' => $stage,
                    'assigned_to' => $owner->id,
                    'priority' => ['low', 'medium', 'high', 'urgent'][$i % 4],
                    'expected_value' => 50000 + ($i * 7500),
                    'campaign_details' => 'DEMO-P12',
                    'customer_id' => in_array($stage, ['won', 'converted'], true) ? $customer->id : null,
                    'converted_at' => $stage === 'converted' ? now()->subDays(10 - $i % 5) : null,
                    'next_follow_up_at' => match (true) {
                        $i === 0 => now()->setTime(11, 0),
                        $i === 1 => now()->subDays(2),
                        $i % 5 === 0 => now()->addDays(2),
                        default => null,
                    },
                    'loss_reason' => $stage === 'lost' ? 'price' : null,
                    'created_by' => $admin->id,
                    'created_at' => now()->subDays(25 - $i),
                ],
            );
            $leads[] = $lead;
        }

        return $leads;
    }

    /**
     * @param  list<Lead>  $leads
     * @param  list<Customer>  $customers
     * @return list<Visit>
     */
    private function seedVisits(User $admin, User $surveyor, array $leads, array $customers): array
    {
        $numbering = app(NumberingService::class);
        $visits = [];
        for ($i = 0; $i < 6; $i++) {
            $lead = $leads[$i];
            $status = ['scheduled', 'scheduled', 'completed', 'completed', 'rescheduled', 'planned'][$i];
            $site = 'Demo site DEMO-VISIT-'.($i + 1).', Block '.($i + 1);
            $existing = Visit::query()->where('site_address', $site)->first();
            $visits[] = Visit::query()->updateOrCreate(
                ['site_address' => $site],
                [
                    'reference' => $existing?->reference ?? $numbering->next('visit'),
                    'lead_id' => $lead->id,
                    'customer_id' => $customers[$i % count($customers)]->id,
                    'scheduled_at' => now()->addDays($i - 1)->setTime(10 + $i, 0),
                    'assigned_surveyor_id' => $surveyor->id,
                    'status' => $status,
                    'city' => $lead->city,
                    'state' => $lead->state,
                    'property_type' => 'rcc_roof',
                    'usable_area_sqm' => 40 + $i * 5,
                    'feasibility' => $status === 'completed' ? 'feasible' : null,
                    'completed_at' => $status === 'completed' ? now()->subDays(3 - $i) : null,
                    'created_by' => $admin->id,
                ],
            );
        }

        return $visits;
    }

    /**
     * @return list<CalculationRun>
     */
    private function seedCalculations(User $admin, User $exec, array $leads, array $customers, array $visits): array
    {
        $hybrid = app(HybridCalculatorService::class);
        $invest = app(InvestmentCalculatorService::class);
        $ev = app(EvCalculatorService::class);

        $baseLoads = [
            ['name' => 'LED', 'qty' => 10, 'rated_w' => 10, 'hours_day' => 5, 'is_critical' => false],
            ['name' => 'Fan', 'qty' => 2, 'rated_w' => 60, 'hours_day' => 8, 'is_critical' => false],
            ['name' => 'Fridge', 'qty' => 1, 'rated_w' => 150, 'hours_day' => 24, 'is_critical' => true],
        ];

        $runs = [];
        $runs[] = $hybrid->runAndStore([
            'backup_hours' => 4,
            'loads' => $baseLoads,
        ], $admin, ['lead_id' => $leads[2]->id, 'customer_id' => $customers[0]->id, 'visit_id' => $visits[2]->id ?? null]);

        $runs[] = $hybrid->runAndStore([
            'backup_hours' => 8,
            'loads' => array_merge($baseLoads, [['name' => 'Router', 'qty' => 1, 'rated_w' => 20, 'hours_day' => 24, 'is_critical' => true]]),
        ], $exec, ['lead_id' => $leads[3]->id, 'customer_id' => $customers[1]->id]);

        $runs[] = $hybrid->runAndStore([
            'backup_hours' => 4,
            'roof_area_m2' => 5,
            'loads' => $baseLoads,
        ], $admin, ['lead_id' => $leads[4]->id]);

        // Investment + EV to reach ≥4 calculation runs
        $runs[] = $invest->runAndStore([
            'upfront_cost' => 250000,
            'subsidy' => 20000,
            'annual_generation_kwh' => 4500,
            'self_consumption_share' => 0.7,
            'utility_tariff' => 8,
            'export_price' => 3,
            'annual_om' => 3000,
            'analysis_years' => 10,
        ], $admin, ['lead_id' => $leads[5]->id]);

        $runs[] = $ev->runAndStore([
            'charger_power_kw' => 7,
            'points' => 2,
            'sessions_per_day' => 4,
            'kwh_per_session' => 20,
            'operating_days_month' => 25,
            'selling_price' => 15,
            'purchase_cost' => 8,
            'utilization' => 0.6,
            'capital_cost' => 80000,
        ], $admin, ['lead_id' => $leads[6]->id]);

        return $runs;
    }

    /**
     * @return list<Quotation>
     */
    private function seedQuotations(User $admin, User $manager, User $exec, array $customers, array $leads, array $calcs): array
    {
        $quotes = app(QuotationService::class);
        $panel = Product::query()->where('sku', 'PNL-HC-550')->first();
        $inv = Product::query()->where('sku', 'INV-VN-5K')->first();
        $bat = Product::query()->where('sku', 'BAT-VN-5KWH')->first();
        $pump = Product::query()->where('sku', 'PMP-AP-3HP')->first();

        $hybridLines = function (int $panels) use ($panel, $inv, $bat): array {
            return array_values(array_filter([
                $panel ? ['sku' => $panel->sku, 'title' => $panel->title, 'quantity' => $panels, 'unit_price' => (float) $panel->sale_price, 'discount_percent' => 0, 'gst_rate' => 0.18] : null,
                $inv ? ['sku' => $inv->sku, 'title' => $inv->title, 'quantity' => 1, 'unit_price' => (float) $inv->sale_price, 'discount_percent' => 0, 'gst_rate' => 0.18] : null,
                $bat ? ['sku' => $bat->sku, 'title' => $bat->title, 'quantity' => 1, 'unit_price' => (float) $bat->sale_price, 'discount_percent' => 0, 'gst_rate' => 0.18] : null,
            ]));
        };

        $pumpLines = $pump ? [[
            'sku' => $pump->sku, 'title' => $pump->title, 'quantity' => 1,
            'unit_price' => (float) $pump->sale_price, 'discount_percent' => 0, 'gst_rate' => 0.18,
        ]] : $hybridLines(4);

        $created = [];
        // 8 quotations across statuses
        $specs = [
            ['draft', 'hybrid', $exec, $customers[0], $leads[2], $hybridLines(8)],
            ['draft', 'hybrid', $manager, $customers[1], $leads[3], $hybridLines(6)],
            ['sent', 'hybrid', $exec, $customers[0], $leads[4], $hybridLines(10)],
            ['sent', 'pump', $manager, $customers[3], $leads[7], $pumpLines],
            // Golden walkthrough path: leads[2] already has completed visit + hybrid calc (calcs[0]).
            ['accepted', 'hybrid', $admin, $customers[0], $leads[2], $hybridLines(12)],
            ['accepted', 'hybrid', $exec, $customers[1], $leads[9], $hybridLines(16)],
            ['accepted', 'pump', $manager, $customers[3], $leads[10], $pumpLines],
            ['rejected', 'hybrid', $exec, $customers[4], $leads[11], $hybridLines(4)],
        ];

        foreach ($specs as $i => [$target, $type, $owner, $customer, $lead, $lines]) {
            if ($lines === []) {
                continue;
            }
            $header = [
                'proposal_type' => $type === 'pump' ? 'solar_water_pump' : 'hybrid',
                'owner_id' => $owner->id,
                'customer_id' => $customer->id,
                'lead_id' => $lead->id,
                'customer_state' => $customer->state,
                'site_address' => 'Demo quote site #'.($i + 1),
                'system_type' => $type === 'pump' ? 'solar_water_pump' : 'hybrid',
                'system_capacity_kw' => $type === 'pump' ? 3 : 4.4,
                'calculation_run_id' => $type === 'hybrid' && isset($calcs[0]) ? $calcs[0]->id : null,
                'payment_schedule' => [
                    ['milestone' => 'Advance', 'percent' => 30],
                    ['milestone' => 'Delivery', 'percent' => 40],
                    ['milestone' => 'Commissioning', 'percent' => 30],
                ],
            ];
            if ($type === 'pump') {
                $header['pump_details'] = [
                    'model' => 'AquaPulse AP-S3',
                    'capacity' => '3HP',
                    'head_m' => 50,
                    'discharge' => '180 LPM',
                ];
            }

            $q = $quotes->create($header, $lines, $owner);
            if ($target === 'sent') {
                $quotes->transition($q, 'sent', $owner);
            } elseif ($target === 'accepted') {
                $quotes->transition($q, 'sent', $owner);
                $quotes->transition($q->fresh(), 'accepted', $owner);
            } elseif ($target === 'rejected') {
                $quotes->transition($q, 'sent', $owner);
                $quotes->transition($q->fresh(), 'rejected', $owner);
            }
            $created[] = $q->fresh(['currentRevision']);
        }

        return $created;
    }

    /**
     * @param  list<Quotation>  $quotes
     * @return list<Project>
     */
    private function seedOrdersProjects(User $admin, User $pm, array $quotes): array
    {
        $convert = app(ConvertQuotationToProject::class);
        $milestones = app(ProjectMilestoneService::class);
        $projects = [];

        $accepted = array_values(array_filter($quotes, fn ($q) => strtolower((string) $q->currentRevision?->status) === 'accepted'));
        foreach (array_slice($accepted, 0, 3) as $idx => $quote) {
            $result = $convert->convert($quote, $admin);
            $project = $result['project'];
            $project->update([
                'manager_id' => $pm->id,
                'sales_user_id' => $quote->owner_id,
                'status' => 'active',
            ]);
            $result['order']->update(['status' => 'active']);

            // Advance milestones differently per project
            $ms = $project->milestones()->orderBy('sort_order')->get();
            $completeCount = [1, 3, 5][$idx] ?? 1;
            foreach ($ms->take($completeCount) as $m) {
                if ($m->status === 'pending') {
                    // first may already be active after convert? ensure active then complete
                    if ($m->status !== 'active') {
                        try {
                            $milestones->transition($m, 'active', $pm);
                            $m = $m->fresh();
                        } catch (\Throwable) {
                        }
                    }
                    try {
                        $milestones->transition($m->fresh(), 'completed', $pm);
                    } catch (\Throwable) {
                    }
                } elseif ($m->status === 'active') {
                    try {
                        $milestones->transition($m, 'completed', $pm);
                    } catch (\Throwable) {
                    }
                }
            }
            $projects[] = $project->fresh(['materials', 'milestones', 'order']);
        }

        return $projects;
    }

    private function seedShortage(?Project $project, User $admin): void
    {
        if (! $project) {
            return;
        }
        $material = $project->materials()->whereHas('product', fn ($q) => $q->where('sku', 'PNL-HC-550'))->first()
            ?? $project->materials()->first();
        if (! $material) {
            $product = Product::query()->where('sku', 'PNL-HC-550')->first();
            $wh = Warehouse::query()->where('code', 'WH-CAI')->first();
            if (! $product || ! $wh) {
                return;
            }
            $material = ProjectMaterial::query()->create([
                'project_id' => $project->id,
                'product_id' => $product->id,
                'warehouse_id' => $wh->id,
                'sku' => $product->sku,
                'title' => $product->title,
                'quantity_required' => 100,
                'quantity_reserved' => 0,
                'quantity_allocated' => 0,
                'quantity_issued' => 0,
            ]);
        } else {
            $material->update(['quantity_required' => max(100, (float) $material->quantity_required + 80)]);
        }
        // shortage is computed vs warehouse available — ensure required exceeds available
        $this->command?->info('Demo shortage material id='.$material->id.' required='.$material->fresh()->quantity_required);
    }

    /**
     * @param  list<Project>  $projects
     */
    private function seedInvoices(User $admin, array $projects): void
    {
        $invoices = app(InvoiceService::class);
        foreach (array_slice($projects, 0, 2) as $i => $project) {
            $order = $project->order;
            if (! $order) {
                continue;
            }
            $invoice = $invoices->createFromOrder($order, $admin);
            $pay = min(5000 + ($i * 2500), (float) $invoice->grand_total * 0.35);
            $invoices->recordPayment($invoice, [
                'amount' => round($pay, 2),
                'method' => 'upi',
                'paid_on' => now()->subDays(2 - $i)->toDateString(),
                'reference' => 'DEMO-PAY-'.($i + 1),
            ], $admin);
        }
    }

    /**
     * @param  list<Customer>  $customers
     * @param  list<Project>  $projects
     */
    private function seedTickets(User $admin, User $ops, array $customers, array $projects): void
    {
        $tickets = app(TicketService::class);
        $project = $projects[0] ?? null;

        $t1 = $tickets->create([
            'customer_id' => $customers[0]->id,
            'project_id' => $project?->id,
            'order_id' => $project?->order_id,
            'issue_summary' => 'Demo open inverter alarm',
            'priority' => 'high',
            'category' => 'fault',
        ], $admin);

        $t2 = $tickets->create([
            'customer_id' => $customers[1]->id,
            'issue_summary' => 'Demo assigned panel cleaning',
            'priority' => 'medium',
            'category' => 'service',
            'assignee_id' => $ops->id,
        ], $admin);

        $t3 = $tickets->create([
            'customer_id' => $customers[2]->id,
            'issue_summary' => 'Demo in progress string fault',
            'priority' => 'urgent',
            'category' => 'fault',
            'assignee_id' => $ops->id,
        ], $admin);
        $tickets->transition($t3->fresh(), 'in_progress', $ops);

        $t4 = $tickets->create([
            'customer_id' => $customers[0]->id,
            'issue_summary' => 'Demo waiting for customer photos',
            'priority' => 'low',
            'assignee_id' => $ops->id,
        ], $admin);
        $tickets->transition($t4->fresh(), 'in_progress', $ops);
        $tickets->transition($t4->fresh(), 'waiting_customer', $ops);

        $t5 = $tickets->create([
            'customer_id' => $customers[3]->id,
            'issue_summary' => 'Demo resolved MC4 replacement',
            'priority' => 'medium',
            'assignee_id' => $ops->id,
        ], $admin);
        $tickets->transition($t5->fresh(), 'in_progress', $ops);
        $tickets->transition($t5->fresh(), 'resolved', $ops, 'Replaced MC4 (demo)');

        $t6 = $tickets->create([
            'customer_id' => $customers[4]->id,
            'issue_summary' => 'Demo closed complaint',
            'priority' => 'low',
            'assignee_id' => $ops->id,
        ], $admin);
        $tickets->transition($t6->fresh(), 'in_progress', $ops);
        $tickets->transition($t6->fresh(), 'resolved', $ops, 'Customer confirmed OK (demo)');
        $tickets->transition($t6->fresh(), 'closed', $ops);

        unset($t1, $t2); // created for status diversity
    }

    /**
     * @param  list<Customer>  $customers
     * @param  list<Project>  $projects
     */
    private function seedAmc(User $admin, User $ops, array $customers, array $projects): void
    {
        $amc = app(AmcService::class);
        $amc->create([
            'customer_id' => $customers[0]->id,
            'project_id' => $projects[0]->id ?? null,
            'site_address' => 'Demo AMC long-term site',
            'starts_on' => now()->subMonths(2)->toDateString(),
            'ends_on' => now()->addYear()->toDateString(),
            'coverage' => 'Panels + inverter (demo)',
            'visit_frequency' => 'quarterly',
            'amount' => 18000,
            'next_service_on' => now()->addMonths(1)->toDateString(),
            'status' => 'active',
            'owner_id' => $ops->id,
        ], $admin);

        $amc->create([
            'customer_id' => $customers[1]->id,
            'site_address' => 'Demo AMC near renewal',
            'starts_on' => now()->subMonths(11)->toDateString(),
            'ends_on' => now()->addDays(20)->toDateString(),
            'coverage' => 'Full hybrid stack (demo)',
            'visit_frequency' => 'semi_annual',
            'amount' => 24000,
            'next_service_on' => now()->addDays(5)->toDateString(),
            'status' => 'active',
            'owner_id' => $ops->id,
        ], $admin);
    }

    /**
     * @param  list<Lead>  $leads
     */
    private function seedTasks(User $admin, User $exec, User $manager, array $leads): void
    {
        $numbering = app(NumberingService::class);
        $defs = [
            ['Overdue demo follow-up call', $exec, now()->subDay(), 'open'],
            ['Due today site prep checklist', $exec, now()->setTime(16, 0), 'open'],
            ['Upcoming manager review', $manager, now()->addDays(3), 'open'],
            ['Completed demo task', $admin, now()->subDays(5), 'completed'],
        ];
        foreach ($defs as $i => [$title, $owner, $due, $status]) {
            Task::query()->updateOrCreate(
                ['title' => $title],
                [
                    'reference' => Task::query()->where('title', $title)->value('reference')
                        ?? $numbering->next('task'),
                    'description' => 'DEMO-P12 task',
                    'owner_id' => $owner->id,
                    'due_at' => $due,
                    'priority' => 'medium',
                    'status' => $status,
                    'task_type' => 'follow_up',
                    'taskable_type' => Lead::class,
                    'taskable_id' => $leads[$i]->id,
                    'created_by' => $admin->id,
                ],
            );
        }
    }

    private function seedNotifications(User $admin, User $exec, User $manager): void
    {
        foreach ([[$exec, 'follow_up'], [$manager, 'task'], [$admin, 'system']] as [$user, $cat]) {
            UserNotification::query()->firstOrCreate(
                [
                    'user_id' => $user->id,
                    'title' => 'Demo '.$cat.' notification',
                    'category' => $cat,
                ],
                [
                    'body' => 'Seeded DEMO-P12 in-app notification',
                    'action_url' => '/admin',
                    'created_at' => now(),
                ],
            );
        }
    }
}
