<?php

namespace App\Services;

use App\Support\Money;
use Illuminate\Validation\ValidationException;

/**
 * Single source of truth for quotation monetary totals.
 *
 * Tax treatment (Sample/Demo defaults — configure in Settings, not hard-coded advice):
 * - auto: if company_state equals customer_state (case-insensitive trim) → CGST+SGST; else IGST
 * - force_cgst_sgst / force_igst: override
 *
 * Rounding: half-up to 2 decimal places via integer minor units (see App\Support\Money).
 */
class QuotationTotals
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array{
     *   lines: list<array<string, mixed>>,
     *   subtotal: float,
     *   discount_total: float,
     *   taxable_value: float,
     *   tax_mode: string,
     *   cgst_rate: float,
     *   sgst_rate: float,
     *   igst_rate: float,
     *   cgst_amount: float,
     *   sgst_amount: float,
     *   igst_amount: float,
     *   tax_total: float,
     *   round_off: float,
     *   grand_total: float,
     *   advance: float,
     *   balance_due: float,
     *   currency: string,
     *   company_state: ?string,
     *   customer_state: ?string,
     *   tax_label: string
     * }
     */
    public function calculate(array $lines, ?string $customerState = null, float $advance = 0.0): array
    {
        $maxDiscount = (float) $this->settings->get('quotations.max_discount_percent', 25);
        $companyState = $this->normalizeState((string) $this->settings->get('finance.company_state', 'Demo-State'));
        $customerState = $this->normalizeState($customerState ?? '');
        $rule = (string) $this->settings->get('finance.tax_split_rule', 'auto');
        $cgstRate = (float) $this->settings->get('finance.cgst_rate', 0.09);
        $sgstRate = (float) $this->settings->get('finance.sgst_rate', 0.09);
        $igstRate = (float) $this->settings->get('finance.igst_rate', 0.18);
        $roundOffMode = (string) $this->settings->get('finance.round_off', 'nearest');
        $currency = (string) $this->settings->get('calc.currency', 'INR');

        $useIntra = match ($rule) {
            'force_cgst_sgst' => true,
            'force_igst' => false,
            default => $companyState !== '' && $customerState !== '' && $companyState === $customerState,
        };

        $normalized = [];
        $subtotalMinor = 0;
        $discountMinor = 0;
        $taxableMinor = 0;

        foreach ($lines as $i => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $discountPct = (float) ($line['discount_percent'] ?? 0);
            $gstRate = (float) ($line['gst_rate'] ?? $igstRate);

            if ($qty < 0 || $unitPrice < 0 || $discountPct < 0 || $gstRate < 0) {
                throw ValidationException::withMessages(["lines.$i" => 'Quantity, price, discount, and GST must be ≥ 0.']);
            }
            if ($discountPct > $maxDiscount) {
                throw ValidationException::withMessages([
                    "lines.$i.discount_percent" => "Discount {$discountPct}% exceeds Settings limit of {$maxDiscount}%.",
                ]);
            }

            $lineGrossMinor = Money::toMinor($qty * $unitPrice);
            $lineDiscountMinor = Money::toMinor(Money::fromMinor($lineGrossMinor) * ($discountPct / 100));
            $lineTaxableMinor = $lineGrossMinor - $lineDiscountMinor;
            // Line GST amount is informational; header split applies for totals (Sample/Demo)
            $lineTaxMinor = Money::toMinor(Money::fromMinor($lineTaxableMinor) * $gstRate);
            $lineTotalMinor = $lineTaxableMinor + $lineTaxMinor;

            $subtotalMinor += $lineGrossMinor;
            $discountMinor += $lineDiscountMinor;
            $taxableMinor += $lineTaxableMinor;

            $normalized[] = array_merge($line, [
                'quantity' => $qty,
                'unit_price' => Money::roundMajor($unitPrice),
                'discount_percent' => $discountPct,
                'gst_rate' => $gstRate,
                'line_gross' => Money::fromMinor($lineGrossMinor),
                'discount_amount' => Money::fromMinor($lineDiscountMinor),
                'taxable_value' => Money::fromMinor($lineTaxableMinor),
                'tax_amount' => Money::fromMinor($lineTaxMinor),
                'line_total' => Money::fromMinor($lineTotalMinor),
            ]);
        }

        $taxable = Money::fromMinor($taxableMinor);

        if ($useIntra) {
            $cgstMinor = Money::toMinor($taxable * $cgstRate);
            $sgstMinor = Money::toMinor($taxable * $sgstRate);
            $igstMinor = 0;
            $taxLabel = 'CGST+SGST (intra-state rule)';
        } else {
            $cgstMinor = 0;
            $sgstMinor = 0;
            $igstMinor = Money::toMinor($taxable * $igstRate);
            $taxLabel = 'IGST (inter-state rule)';
        }

        $taxTotalMinor = $cgstMinor + $sgstMinor + $igstMinor;
        $preRoundMinor = $taxableMinor + $taxTotalMinor;

        $roundOffMinor = 0;
        if ($roundOffMode === 'nearest') {
            $roundedMajor = round(Money::fromMinor($preRoundMinor), 0, PHP_ROUND_HALF_UP);
            $roundOffMinor = Money::toMinor($roundedMajor) - $preRoundMinor;
        }

        $grandMinor = $preRoundMinor + $roundOffMinor;
        $advanceMinor = Money::toMinor($advance);
        $balanceMinor = max(0, $grandMinor - $advanceMinor);

        return [
            'lines' => $normalized,
            'subtotal' => Money::fromMinor($subtotalMinor),
            'discount_total' => Money::fromMinor($discountMinor),
            'taxable_value' => $taxable,
            'tax_mode' => $useIntra ? 'cgst_sgst' : 'igst',
            'cgst_rate' => $cgstRate,
            'sgst_rate' => $sgstRate,
            'igst_rate' => $igstRate,
            'cgst_amount' => Money::fromMinor($cgstMinor),
            'sgst_amount' => Money::fromMinor($sgstMinor),
            'igst_amount' => Money::fromMinor($igstMinor),
            'tax_total' => Money::fromMinor($taxTotalMinor),
            'round_off' => Money::fromMinor($roundOffMinor),
            'grand_total' => Money::fromMinor($grandMinor),
            'advance' => Money::fromMinor($advanceMinor),
            'balance_due' => Money::fromMinor($balanceMinor),
            'currency' => $currency,
            'company_state' => $companyState ?: null,
            'customer_state' => $customerState ?: null,
            'tax_label' => $taxLabel.' — Sample/Demo configurable rates',
            'rounding_policy' => 'half-up to 2dp via minor units; optional nearest-rupee round_off',
        ];
    }

    private function normalizeState(?string $state): string
    {
        return strtolower(trim((string) $state));
    }
}
