<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single invoice per order; manual payments only (gateway Simulated/not implemented).
 */
class InvoiceService
{
    public function __construct(
        private readonly NumberingService $numbering,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function createFromOrder(Order $order, ?User $user = null): Invoice
    {
        $existing = Invoice::query()->where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $totals = $order->totals_snapshot ?? [];
        $subtotal = (float) ($totals['taxable_value'] ?? $totals['subtotal'] ?? $order->contract_value);
        $tax = (float) (($totals['cgst_amount'] ?? 0) + ($totals['sgst_amount'] ?? 0) + ($totals['igst_amount'] ?? 0));
        if ($tax <= 0 && isset($totals['tax_total'])) {
            $tax = (float) $totals['tax_total'];
        }
        $grand = (float) ($totals['grand_total'] ?? $order->contract_value);

        $taxLines = [];
        if (($totals['cgst_amount'] ?? 0) > 0) {
            $taxLines[] = ['label' => 'CGST', 'amount' => (float) $totals['cgst_amount'], 'rate' => $totals['cgst_rate'] ?? null];
            $taxLines[] = ['label' => 'SGST', 'amount' => (float) $totals['sgst_amount'], 'rate' => $totals['sgst_rate'] ?? null];
        }
        if (($totals['igst_amount'] ?? 0) > 0) {
            $taxLines[] = ['label' => 'IGST', 'amount' => (float) $totals['igst_amount'], 'rate' => $totals['igst_rate'] ?? null];
        }

        $invoice = Invoice::query()->create([
            'number' => $this->numbering->next('invoice'),
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'invoiced_on' => now()->toDateString(),
            'subtotal' => $subtotal,
            'tax_total' => $tax,
            'grand_total' => $grand,
            'tax_lines' => $taxLines,
            'schedule_snapshot' => $order->payment_schedule,
            'lines_snapshot' => $order->lines_snapshot,
            'status' => 'issued',
            'payment_gateway_note' => 'Simulated/not implemented — manual payments only',
            'created_by' => $user?->id,
        ]);

        $this->auditLogger->log(
            action: 'invoice.created',
            entityType: Invoice::class,
            entityId: $invoice->id,
            entityReference: $invoice->number,
            summary: ['order' => $order->number, 'grand_total' => $grand],
        );

        return $invoice;
    }

    /**
     * @param  array{amount: float|int|string, method?: string, paid_on?: string, reference?: string, notes?: string}  $data
     */
    public function recordPayment(Invoice $invoice, array $data, ?User $user = null): Payment
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be > 0.']);
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $user) {
            /** @var Invoice $locked */
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === 'void') {
                throw ValidationException::withMessages(['invoice' => 'Cannot pay a void invoice.']);
            }

            $outstanding = $locked->outstanding();
            if ($amount - $outstanding > 0.009) {
                throw ValidationException::withMessages([
                    'amount' => "Overpayment rejected. Outstanding is {$outstanding}.",
                ]);
            }

            $payment = Payment::query()->create([
                'invoice_id' => $locked->id,
                'amount' => $amount,
                'method' => $data['method'] ?? 'bank_transfer',
                'paid_on' => $data['paid_on'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $user?->id,
            ]);

            $locked->refreshPaymentStatus();

            $this->auditLogger->log(
                action: 'payment.recorded',
                entityType: Invoice::class,
                entityId: $locked->id,
                entityReference: $locked->number,
                summary: [
                    'payment_id' => $payment->id,
                    'amount' => $amount,
                    'outstanding' => $locked->fresh()->outstanding(),
                    'status' => $locked->fresh()->status,
                ],
            );

            return $payment->fresh();
        });
    }
}
