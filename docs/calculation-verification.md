# Calculation Verification (Validation V3)

**Generated:** `2026-10-23 17:36+ EEST` (shell `date`)  
**Spec:** `docs/calculation-spec.md`  
**Services:** `HybridCalculatorService`, `InvestmentCalculatorService`, `EvCalculatorService`, `QuotationTotals`  
**Automated:** `php artisan test --compact tests/Feature/Calculators/CalculationVerificationTest.php` (+ existing Hybrid / InvestmentAndEv / Quotation totals tests)

**Rounding tolerance (documented):**

| Quantity | Tolerance |
|----------|-----------|
| Panel / integer counts | Exact |
| Battery / daily kWh (4–6 dp internals) | ±0.01 kWh |
| Annual kWh / money (2 dp) | ±0.05 |
| EMI | ±0.02 |
| Ratios | ±0.01 |

**Rule:** Hand math follows the spec formulas. If hand ≠ app beyond tolerance, root-cause which side is wrong — do not silently retune.

**Suite paste (V3):** Calculators folder **24 passed**; QuotationTotals filter **9 passed**; full suite after seed context **114 passed, 3 skipped, 0 failed** (`2026-10-23 17:40:07 EEST`).

---

## Reconciliation summary

| ID | Scenario | Hand key outputs | App output | Δ | Status |
|----|----------|------------------|------------|---|--------|
| H1 | Small home hybrid | daily 5.06; batt 0.9234; panels 3; gen 2258.44; savings 14679.84 | Same within tol | ~0 | Pass |
| H2 | Heavy load home | daily 25.06; batt 3.6934; panels 13; inv 3.7 kW | Same | ~0 | Pass |
| H3 | High DoD 0.9 vs low 0.5 | batt 0.9234 vs 1.662 (ratio 1.8) | Same | ~0 | Pass |
| H4 | High PSH 6.5 vs low 3.5 | panels 2 vs 4 | Same | ~0 | Pass |
| H5 | With generator 0.5 kW | effective backup 0; batt 0; panels still 3 | Same | ~0 | Pass |
| I1 | Investment no loan/subsidy | net 100k; Y1 save 80k; payback 2; ROI 1100% | Same | ~0 | Pass |
| I2 | Investment loan+subsidy | net 120k; EMI 2824.41; Y1 CF 28907; payback 3 | Same | ~0 | Pass |
| E1 | EV no capital | energy 1200 kWh/mo; profit 3500; payback null | Same | ~0 | Pass |
| E2 | EV with capital 100k | payback 28.57 mo | Same | ~0 | Pass |
| Q1 | Quote intra-state | taxable 1800; CGST+SGST 162+162; grand 2124 | Same | 0 | Pass |
| Q2 | Quote inter-state | IGST 324; grand 2124 | Same | 0 | Pass |
| SNAP | PDF = HTML = snapshot; immutable | panels/total/gen identical after settings change | Confirmed | 0 | Pass |

**Discrepancies found:** none (hand and app agree within tolerance). No formula bug fix required in V3.

---

## Common hybrid defaults (unless overridden)

| Symbol | Value | Unit |
|--------|-------|------|
| PSH | 5 | h/day |
| PR | 0.75 | — |
| L | 0 (losses folded in PR) | — |
| Wp | 550 | W |
| DoD | 0.90 | — |
| η_b, η_i | 0.95 | — |
| m | 1.25 | — |
| V | 48 | V |
| s | 0.70 | — |
| u | 1.0 | — |
| share | 0.70 | — |
| tariff / export | 8 / 3 | INR/kWh Sample/Demo |
| generator_credit_factor | 0.8 | — |

---

## H1 — Small home hybrid

### Inputs

| Load | qty | rated_w (W) | hours_day | critical |
|------|-----|-------------|-----------|----------|
| LED | 10 | 10 | 5 | no |
| Fan | 2 | 60 | 8 | no |
| Fridge | 1 | 150 | 24 | yes |

`backup_hours = 4`.

### Hand steps

1. `connected_w = 10×10 + 2×60 + 150 = 370 W`
2. `daily_wh = 10×10×5 + 2×60×8 + 150×24 = 500+960+3600 = 5060 Wh` → `daily_kwh = 5.06`
3. `peak_w = 370 × 0.7 = 259 W`
4. Critical backup: `150 × min(24,4) × 1 = 600 Wh` → `backup_energy_kwh = 0.6`
5. `denom = 0.9×0.95×0.95 = 0.81225`
6. `battery_nominal = 0.6 / 0.81225 × 1.25 = 0.92336 ≈ 0.9234 kWh`
7. `battery_ah = 0.92336×1000/48 ≈ 19.24 Ah`
8. `gen_factor = 5×0.75 = 3.75`
9. `pv_kwp_required = 5.06/3.75 = 1.349333` → `ceil(1349.33/550) = 3` panels → `1.65 kWp`
10. `annual_gen = 1.65×5×365×0.75 = 2258.4375 → 2258.44`
11. `annual_load = 5.06×365 = 1846.9`; `self = min(2258.44, 0.7×2258.44, 1846.9) = 1580.908`
12. `export = 2258.44−1580.91 = 677.53`
13. `savings = 1580.91×8 + 677.53×3 = 14679.84`

### App vs hand

| Metric | Hand | App | Δ |
|--------|------|-----|---|
| daily_kwh | 5.06 | 5.06 | 0 |
| battery_nominal_kwh | 0.9234 | 0.9234 | 0 |
| panel_count | 3 | 3 | 0 |
| annual_generation_kwh | 2258.44 | 2258.44 | 0 |
| annual_savings | 14679.84 | 14679.84 | 0 |

**Status:** Pass

---

## H2 — Home with heavy load

### Inputs

H1 loads plus:

| Load | qty | W | h/day | critical |
|------|-----|---|-------|----------|
| AC | 2 | 1500 | 6 | no |
| Pump | 1 | 750 | 2 | yes |
| TV | 1 | 100 | 5 | no |

`backup_hours = 6`.

### Hand steps

1. `connected = 370 + 3000 + 750 + 100 = 4220 W`
2. `daily_wh = 5060 + 2×1500×6 + 750×2 + 100×5 = 5060+18000+1500+500 = 25060` → `25.06 kWh`
3. Backup: fridge `150×6=900`; pump `750×2=1500` → `2.4 kWh`
4. `battery = 2.4/0.81225×1.25 = 3.6934 kWh`
5. `peak = 4220×0.7 = 2954 W` → `inv_cont = ceil(2.954×1.25 / 0.1)×0.1 = 3.7 kW`
6. `pv_req = 25.06/3.75 = 6.682667` → `ceil(6682.67/550)=13` → `7.15 kWp`

### App vs hand

| Metric | Hand | App | Δ |
|--------|------|-----|---|
| daily_kwh | 25.06 | 25.06 | 0 |
| battery_nominal_kwh | 3.6934 | 3.6934 | 0 |
| panel_count | 13 | 13 | 0 |
| inverter_continuous_kw | 3.7 | 3.7 | 0 |

**Status:** Pass

---

## H3 — High DoD vs low DoD

Same loads as H1; vary `usable_dod` only.

| Case | DoD | Hand battery_nominal | App | Δ |
|------|-----|----------------------|-----|---|
| High | 0.90 | `0.6/(0.9×0.95×0.95)×1.25 = 0.9234` | 0.9234 | 0 |
| Low | 0.50 | `0.6/(0.5×0.95×0.95)×1.25 = 1.662` | 1.662 | 0 |

**Expected direction:** lower DoD → **higher** nominal battery (inversely proportional to DoD).  
**Magnitude:** ratio `0.9/0.5 = 1.8` (confirmed app).

**Status:** Pass

---

## H4 — High PSH vs low PSH

Same loads as H1; vary `peak_sun_hours`.

| Case | PSH | gen_factor | pv_kwp_req | panels (hand) | App panels | App kWp |
|------|-----|------------|------------|---------------|------------|---------|
| High | 6.5 | 4.875 | 1.0379 | ceil(1037.9/550)=**2** | 2 | 1.1 |
| Low | 3.5 | 2.625 | 1.9276 | ceil(1927.6/550)=**4** | 4 | 2.2 |

**Direction:** higher PSH → fewer panels / lower required kWp.  
**Status:** Pass

---

## H5 — With generator

H1 inputs + `generator_support_kw = 0.5`.

### Hand

`credit = 0.5 × 4 × 0.8 = 1.6 kWh`  
`effective_backup = max(0, 0.6 − 1.6) = 0`  
`battery_nominal = 0`  
PV path unchanged → still **3** panels / 1.65 kWp.

### App

| Metric | Hand | App | Δ |
|--------|------|-----|---|
| effective_backup_kwh | 0 | 0 | 0 |
| battery_nominal_kwh | 0 | 0 | 0 |
| panel_count | 3 | 3 | 0 |

**Status:** Pass  
**Note:** When generator credit exceeds backup energy, battery sizes to zero by design (spec §2.3).

---

## I1 — Investment without loan / subsidy

### Inputs

`upfront=100000`, `subsidy=0`, `gen=8000 kWh/y`, `share=1`, `tariff=10`, `export=0`, degrade=0, escalate=0, opex=0, years=15.

### Hand

`net_initial = 100000`  
`Y1 savings = 8000×10 = 80000`  
Cumulative: Y1 80k < 100k; Y2 160k ≥ 100k → **payback = 2**  
`Σ net_cf (15y) = 1_200_000`; `ROI = (1_200_000 − 100_000)/100_000 × 100% = 1100%`

### App

| Metric | Hand | App | Δ |
|--------|------|-----|---|
| net_initial | 100000 | 100000 | 0 |
| annual_savings_year1 | 80000 | 80000 | 0 |
| payback_years | 2 | 2 | 0 |
| roi_percent | 1100 | 1100 | 0 |

**Status:** Pass

---

## I2 — Investment with loan and subsidy

### Inputs

`upfront=150000`, `subsidy=30000` → `net_initial=120000`  
`loan=60000`, `r_annual=12%`, `n=24` months  
`gen=9000`, `share=0.7`, `tariff=9`, `export=3`, `om=2000`, years=10, degrade=0, escalate=0.

### Hand — EMI

`r = 0.01`, `n = 24`  
`(1.01)^24 ≈ 1.26973`  
`EMI = 60000×0.01×1.26973/(1.26973−1) ≈ 2824.41`

### Hand — Year 1

`self=6300`, `export_kwh=2700`  
`savings = 6300×9 + 2700×3 = 64800`  
`debt = 2824.41×12 = 33892.90` (loan active years 1–2 only)  
`net_cf = 64800 − 2000 − 33892.90 = 28907.10`  
After Y3 (no debt): cum ≈ 120614 ≥ 120000 → **payback = 3**

### App

| Metric | Hand | App | Δ |
|--------|------|-----|---|
| EMI | 2824.41 | 2824.41 | ~0 |
| Y1 savings | 64800 | 64800 | 0 |
| Y1 net_cf | 28907.10 | 28907.10 | ~0 |
| payback_years | 3 | 3 | 0 |

**Status:** Pass

---

## E1 — EV without capital cost

### Inputs

`points=2`, `sessions/day=4`, `kWh/session=10`, `days=30`, `util=0.5`,  
`sell=20`, `buy=5`, `rent=5000`, `staff=8000`, `maint=1000`, `other=500`, `capital=0`.

### Hand

`effective_sessions = 4×0.5 = 2`  
`energy_day = 2×2×10 = 40 kWh`  
`energy_month = 40×30 = 1200 kWh`  
`revenue = 1200×20 = 24000`  
`elec = 1200×5 = 6000`  
`opex = 14500`  
`profit = 24000−6000−14500 = 3500`  
`payback = null` (no capital)

### App

Matches exactly (1200 / 3500 / null). **Pass**

---

## E2 — EV with capital cost

Same as E1 + `capital_cost = 100000`.

`payback_months = 100000 / 3500 ≈ 28.571 → 28.57`

App: **28.57**. **Pass**

---

## Q1 — Quotation intra-state (CGST+SGST)

Company state = Maharashtra; customer = Maharashtra; rates 9%+9%; round_off = none.

Line: qty 2 × 1000, discount 10%.

1. Gross = 2000  
2. Discount = 200  
3. Taxable = 1800  
4. CGST = 1800×0.09 = 162; SGST = 162  
5. Grand = 1800+324 = **2124**

App: identical. **Pass**

---

## Q2 — Quotation inter-state (IGST)

Customer = Karnataka; IGST 18%.

Taxable 1800 → IGST 324 → grand **2124** (same total, different split).

App: identical. **Pass**

---

## What-if tables (hybrid)

Base = H1. Changing **one** variable; direction from formula; magnitude from app Δ.

| Variable change | Formula expectation | Direction | App magnitude (approx) |
|-----------------|---------------------|-----------|------------------------|
| LED hours 5→10 | daily_wh ↑ by 500 Wh | daily_kwh ↑ | +0.50 kWh/day; panels may ↑ |
| backup_hours 4→8 | backup energy ↑ (fridge uses 8h) | battery ↑ | 0.9234 → ~1.847 kWh |
| panel_W 550→600 | fewer panels for same kWp | panels ↓ or = | 3 → ≤3 |
| PSH 5→6 | gen_factor ↑ → kWp_req ↓ | panels ↓ or = | 3 → ≤3 |
| DoD 0.9→0.5 | denom ↓ → battery ↑ | battery ↑ | ×1.8 |
| η_b 0.95→0.8 | denom ↓ → battery ↑ | battery ↑ | +~0.19 kWh |
| tariff 8→20 | savings ↑ linearly in self×tariff | savings ↑ | large ↑ |
| L=0.2 (losses separate) | gen_factor ↓ → more panels | panels ↑ | 3 → >3 |
| installation +5000 | CAPEX ↑ | grand_total ↑ | +5000×(1+GST) |

Investment / EV quick checks:

| Change | Expectation | App |
|--------|-------------|-----|
| I1 tariff 10→20 | payback 2→1 | Confirmed in `InvestmentAndEvCalculatorTest` |
| E1 util 1.0→0.5 | energy halves (sessions only) | 2400→1200 |

---

## Snapshot / PDF / on-screen equality

1. `runAndStore` writes `inputs`, `assumptions`, `steps`, `outputs`.  
2. Blade `pdf.hybrid-calculation` reads **only** the run snapshot (not live Settings).  
3. After changing `calc.hybrid.peak_sun_hours` to 2.0, reloaded run still has original `panel_count`, `grand_total`, `annual_generation_kwh`.  
4. PDF binary generated from same snapshot; HTML contains the same panel/total/gen strings.

**Automated:** `CalculationVerificationTest::test_pdf_html_and_snapshot_match_and_remain_immutable` + `HybridCalculatorTest::test_pdf_matches_stored_snapshot_values` / `test_snapshot_immutable_when_settings_change`.

**Status:** Pass

---

## Commands

```bash
php artisan test --compact tests/Feature/Calculators/CalculationVerificationTest.php
php artisan test --compact tests/Feature/Calculators/
php artisan test --compact --filter=QuotationTotals
php artisan test --compact
```

Interview tip: walk H1 → change DoD or PSH → predict direction from the formula column above before revealing the app number.
