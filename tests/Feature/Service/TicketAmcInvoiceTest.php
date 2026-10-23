<?php

namespace Tests\Feature\Service;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\AmcService;
use App\Services\InvoiceService;
use App\Services\NumberingService;
use App\Services\SettingsService;
use App\Services\TicketService;
use App\Support\TicketWorkflow;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SequenceSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TicketAmcInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        $this->seed(SequenceSeeder::class);
        $this->admin = User::factory()->withRole('super-admin')->create();
        $this->customer = Customer::query()->create([
            'customer_number' => app(NumberingService::class)->next('customer'),
            'name' => 'Service Customer',
            'mobile' => '9000000099',
            'state' => 'Maharashtra',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_ticket_sla_target_from_settings(): void
    {
        app(SettingsService::class)->set('tickets.default_sla_hours', 24, [
            'group' => 'tickets', 'type' => 'integer', 'change_summary' => 'test',
        ]);

        $ticket = app(TicketService::class)->create([
            'customer_id' => $this->customer->id,
            'issue_summary' => 'Inverter fault',
            'priority' => 'high',
        ], $this->admin);

        $this->assertSame('open', $ticket->status);
        $this->assertNotNull($ticket->target_resolution_at);
        $hours = $ticket->opened_at->diffInHours($ticket->target_resolution_at);
        $this->assertEquals(24, $hours);
    }

    public function test_ticket_transition_rules(): void
    {
        $this->assertFalse(TicketWorkflow::canTransition('closed', 'open'));
        $this->assertTrue(TicketWorkflow::canTransition('open', 'assigned'));

        $tickets = app(TicketService::class);
        $ticket = $tickets->create([
            'customer_id' => $this->customer->id,
            'issue_summary' => 'X',
        ], $this->admin);

        try {
            $tickets->transition($ticket, 'assigned', $this->admin);
            $this->fail('Assigned without assignee should fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('assignee_id', $e->errors());
        }

        try {
            $tickets->transition($ticket->fresh(), 'resolved', $this->admin, 'skip');
            $this->fail('open → resolved should fail');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }
    }

    public function test_ticket_assign_progress_resolve_close_and_scope(): void
    {
        $assignee = User::factory()->withRole('inventory-finance-service')->create();
        $tickets = app(TicketService::class);

        $order = $this->makeOrder(1000);
        $project = Project::query()->create([
            'number' => app(NumberingService::class)->next('project'),
            'order_id' => $order->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'percent_complete' => 0,
        ]);
        $order->update(['project_id' => $project->id]);

        $ticket = $tickets->create([
            'customer_id' => $this->customer->id,
            'project_id' => $project->id,
            'order_id' => $order->id,
            'issue_summary' => 'Panel hotspot',
        ], $this->admin);

        $tickets->assign($ticket, $assignee, $this->admin);
        $this->assertSame('assigned', $ticket->fresh()->status);

        $tickets->transition($ticket->fresh(), 'in_progress', $this->admin);
        $tickets->transition($ticket->fresh(), 'resolved', $this->admin, 'Replaced MC4 connector');
        $tickets->transition($ticket->fresh(), 'closed', $this->admin);

        $closed = $ticket->fresh();
        $this->assertSame('closed', $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertGreaterThanOrEqual(4, $closed->statusHistories()->count());

        $this->assertTrue($assignee->can('view', $closed));
        $other = User::factory()->withRole('sales-executive')->create();
        $this->assertFalse($other->can('view', $closed));
    }

    public function test_invoice_outstanding_and_overpayment_rejected(): void
    {
        $order = $this->makeOrder(5000.00);
        $invoices = app(InvoiceService::class);
        $invoice = $invoices->createFromOrder($order, $this->admin);

        $this->assertSame(1, Invoice::query()->where('order_id', $order->id)->count());
        $again = $invoices->createFromOrder($order, $this->admin);
        $this->assertSame($invoice->id, $again->id);

        $this->assertEquals(5000.0, (float) $invoice->grand_total);
        $this->assertEquals(5000.0, $invoice->outstanding());

        $invoices->recordPayment($invoice, [
            'amount' => 2000,
            'method' => 'upi',
            'paid_on' => now()->toDateString(),
            'reference' => 'UPI-1',
        ], $this->admin);

        $invoice->refresh();
        $this->assertEquals(3000.0, $invoice->outstanding());
        $this->assertSame('partial', $invoice->status);

        try {
            $invoices->recordPayment($invoice->fresh(), ['amount' => 4000, 'method' => 'cash'], $this->admin);
            $this->fail('Expected overpayment rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('amount', $e->errors());
        }

        $invoices->recordPayment($invoice->fresh(), ['amount' => 3000, 'method' => 'bank_transfer'], $this->admin);
        $invoice->refresh();
        $this->assertEquals(0.0, $invoice->outstanding());
        $this->assertSame('paid', $invoice->status);
    }

    public function test_amc_renewal_reminder_list(): void
    {
        $amc = app(AmcService::class);
        $contract = $amc->create([
            'customer_id' => $this->customer->id,
            'site_address' => 'Site AMC',
            'starts_on' => now()->subMonths(10)->toDateString(),
            'ends_on' => now()->addDays(20)->toDateString(),
            'coverage' => 'Panels + inverter',
            'visit_frequency' => 'quarterly',
            'amount' => 12000,
            'next_service_on' => now()->addDays(5)->toDateString(),
            'status' => 'active',
        ], $this->admin);

        $this->assertNotNull($contract->renewal_reminder_on);
        $list = $amc->renewalReminderList();
        $this->assertTrue($list->contains('id', $contract->id));

        $this->artisan('amc:renewal-reminders')->assertSuccessful();
    }

    private function makeOrder(float $value = 1000): Order
    {
        $quote = Quotation::query()->create([
            'number' => app(NumberingService::class)->next('quote'),
            'public_token' => bin2hex(random_bytes(16)),
            'proposal_type' => 'hybrid',
            'customer_id' => $this->customer->id,
            'owner_id' => $this->admin->id,
        ]);

        return Order::query()->create([
            'number' => app(NumberingService::class)->next('order'),
            'ordered_on' => now()->toDateString(),
            'quotation_id' => $quote->id,
            'customer_id' => $this->customer->id,
            'owner_id' => $this->admin->id,
            'contract_value' => $value,
            'totals_snapshot' => [
                'subtotal' => round($value / 1.18, 2),
                'taxable_value' => round($value / 1.18, 2),
                'cgst_amount' => 0,
                'sgst_amount' => 0,
                'igst_amount' => round($value - ($value / 1.18), 2),
                'grand_total' => $value,
            ],
            'payment_schedule' => [
                ['milestone' => 'Advance', 'percent' => 30],
                ['milestone' => 'Balance', 'percent' => 70],
            ],
            'lines_snapshot' => [],
            'status' => 'active',
        ]);
    }
}
