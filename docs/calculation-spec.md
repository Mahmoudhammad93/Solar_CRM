# Calculation Specification (Phase 5)

**Status:** Authoritative formula document for Hybrid, Investment, and EV calculators.  
**Source of truth:** Server-side PHP services. Filament UI only collects inputs and displays stored snapshot outputs.  
**Disclaimer (always shown):** Results are an **indicative estimate, not engineering certification**. Detailed engineering validation is required before procurement/installation.

Tariff, export/net-metering, and subsidy figures are labeled **Sample/Demo** unless Settings include state/utility, source/reference, and effective date.

---

## 1. Units and symbols

| Symbol | Meaning | Unit |
|--------|---------|------|
| `qty` | Appliance quantity | count |
| `W` | Rated power | W |
| `h` | Hours per day | h/day |
| `u` | Usage-pattern factor | dimensionless (default 1.0) |
| `s` | Simultaneity factor (peak) | dimensionless (default 0.7 if not per-row) |
| `g` | Starting/surge factor | dimensionless (optional, default 1.0) |
| `DoD` | Usable depth of discharge | fraction 0–1 |
| `η_b` | Battery round-trip / discharge efficiency | fraction 0–1 |
| `η_i` | Inverter efficiency | fraction 0–1 |
| `m` | Design margin | fraction ≥ 1 (e.g. 1.25) |
| `V` | System DC voltage | V |
| `PSH` | Peak sun hours | h/day |
| `PR` | Performance ratio (includes losses if losses not separate) | fraction 0–1 |
| `L` | Separate system loss factor (optional) | fraction 0–1 of generation lost |
| `Wp` | Panel nameplate | W |
| `A` | Usable roof area | m² |
| `a_p` | Area per panel | m² |

---

## 2. Hybrid solar calculator

### 2.1 Load table (per row)

Inputs per row: `name`, `qty`, `rated_w`, `hours_day`, `usage_pattern` (maps to factor `u`), `is_critical` (bool), optional `surge_factor` (`g`), optional `simultaneity` (`s_row`).

**Formulas**

1. Connected load (W)  
   `connected_w = Σ (qty × rated_w)`

2. Daily energy (Wh)  
   `daily_wh = Σ (qty × rated_w × hours_day × u)`  
   `daily_kwh = daily_wh / 1000`

3. Peak simultaneous (W)  
   `peak_w = Σ (qty × rated_w × s_row)`  
   where `s_row` defaults to Settings `calc.hybrid.default_simultaneity` when blank.

4. Surge-adjusted peak (W)  
   `surge_peak_w = Σ (qty × rated_w × s_row × g)`

### 2.2 Critical backup energy

Let `backup_hours` = backup duration target (h).

`critical_daily_wh = Σ_critical (qty × rated_w × hours_day × u)`  
`critical_load_kw = (Σ_critical (qty × rated_w × s_row)) / 1000`  
`backup_energy_kwh = (Σ_critical (qty × rated_w × min(hours_day, backup_hours) × u)) / 1000`  

**Additional assumption (PDF silent):** If an appliance’s `hours_day` exceeds `backup_hours`, energy during backup uses `backup_hours` only (not full daily hours). Non-critical loads are excluded from battery sizing.

### 2.3 Optional generator reduction

If `generator_support_kw` > 0:

`effective_backup_kwh = max(0, backup_energy_kwh − generator_support_kw × backup_hours × generator_credit_factor)`  

**Additional assumption:** `generator_credit_factor` default **0.8** (Settings `calc.hybrid.generator_credit_factor`) — generator does not replace 100% of critical energy (availability/derate).

### 2.4 Battery sizing

Denominators must be > 0 (validated).

`battery_nominal_kwh = effective_backup_kwh / (DoD × η_b × η_i) × m`  
`battery_usable_kwh = battery_nominal_kwh × DoD × η_b`  
`battery_ah = battery_nominal_kwh × 1000 / V`

### 2.5 Inverter sizing

`inverter_continuous_kw = ceil_to_step(peak_w / 1000 × m, step=0.1)`  
`inverter_surge_kw = ceil_to_step(surge_peak_w / 1000 × m, step=0.1)`  
Recommended continuous rating = max(computed continuous, Settings minimum if any).

### 2.6 PV sizing

Daily energy target:

- Prefer load-derived `daily_kwh`  
- If `monthly_units` provided and `use_monthly_units_target` enabled:  
  `daily_kwh_target = monthly_units / 30 × offset_fraction`  
  (default `offset_fraction` = 1.0 from Settings)

Generation factor:

`gen_factor = PSH × PR × (1 − L)`  
where `L = 0` if losses are already folded into `PR` (Settings `calc.hybrid.losses_separate` = false → use `L=0` and PR as combined).

`pv_kwp_required = daily_kwh_target / gen_factor`  
`panel_count = ceil(pv_kwp_required × 1000 / Wp)`  
`pv_kwp_recommended = panel_count × Wp / 1000`

**Roof constraint** (when `constrain_to_roof` enabled and `A`, `a_p` > 0):

`max_panels = floor(A / a_p)`  
`pv_kwp_roof = max_panels × Wp / 1000`  
`panel_count_final = min(panel_count, max_panels)`  
`pv_kwp_final = panel_count_final × Wp / 1000`

### 2.7 Annual generation and self-consumption / export

`annual_gen_kwh = pv_kwp_final × PSH × 365 × PR × (1 − L) × (1 − first_year_degradation)`  

**Additional assumption:** First-year degradation default **0** unless Settings `calc.hybrid.first_year_degradation` set (fraction).

Self-consumption:

`self_consumed_kwh = min(annual_gen_kwh, self_consumption_share × annual_gen_kwh, annual_load_kwh)`  
where `annual_load_kwh = daily_kwh × 365`  
and `self_consumption_share` ∈ [0,1] from inputs/Settings.

**Additional assumption (avoid double counting):** `self_consumed = min(generation, share×generation, annual_load)`. Export only when enabled:

`exported_kwh = max(0, annual_gen_kwh − self_consumed_kwh)` if `export_enabled`, else `0`.

### 2.8 Backup time estimate

`usable_batt_kwh = battery_nominal_kwh × DoD × η_b`  
`est_backup_hours = usable_batt_kwh × η_i / max(critical_load_kw, ε)`  
(`ε` = 1e-6 guard; validation rejects zero critical load when backup target > 0)

### 2.9 Savings (indicative)

`annual_savings = self_consumed_kwh × tariff + exported_kwh × export_rate`  
Tariff/export marked Sample/Demo unless Settings provide source metadata.

### 2.10 Cost breakdown

Line items (currency from Settings `calc.currency`):

| Line | Formula |
|------|---------|
| Panels | `panel_count_final × panel_unit_price` |
| Inverter | `inverter_unit_price` (or × qty if multi) |
| Battery | `battery_nominal_kwh × battery_price_per_kwh` |
| BOS | `bos_flat` or `% of equipment` per Settings |
| Structure/civil | input or default |
| Wiring/protection | input or default |
| Installation | input or default |
| Transport | input or default |
| Engineering | input or default |
| Other | input |
| Subtotal ex-GST | sum of above |
| GST | `subtotal × gst_rate` |
| Subsidy | subtract (Sample/Demo unless sourced) |
| **Grand total** | `subtotal + GST − subsidy` |
| O&M (annual, shown separately) | input / Settings default — **not** in CAPEX total |

---

## 3. Solar investment / income calculator

### 3.1 Inputs

`upfront_cost`, `subsidy`, optional `loan_principal`, `annual_interest_rate` (fraction), `tenure_months`, `annual_generation_kwh`, `self_consumption_share`, `export_price`, `utility_tariff`, `degradation_per_year`, `tariff_escalation_per_year`, `annual_om`, `other_annual_cost`, `analysis_years`.

### 3.2 Formulas

`net_initial = upfront_cost − subsidy`

**EMI** (reducing-balance / standard annuity):

If `r_month = annual_interest_rate / 12` and `r_month = 0`:  
`emi = loan_principal / tenure_months`  

Else:  
`emi = P × r × (1+r)^n / ((1+r)^n − 1)`  
where `P=loan_principal`, `r=r_month`, `n=tenure_months`.

`annual_debt_service = emi × 12` (while loan active; for simplicity apply for `min(analysis_years, ceil(n/12))` years then 0).

Year `t` (1..N):

`gen_t = annual_generation_kwh × (1 − degradation)^(t−1)`  
`tariff_t = utility_tariff × (1 + tariff_escalation)^(t−1)`  
`export_t = export_price × (1 + tariff_escalation)^(t−1)`  
`self_t = gen_t × self_consumption_share`  
`export_kwh_t = gen_t − self_t`  
`savings_t = self_t × tariff_t + export_kwh_t × export_t`  
`opex_t = annual_om + other_annual_cost`  
`net_cf_t = savings_t − opex_t − annual_debt_service_t`

**Payback (simple):** smallest `Y` such that cumulative `Σ net_cf ≤ Y` ≥ `net_initial` (null if never).

**ROI over period:** `(Σ net_cf − net_initial) / net_initial × 100%` for selected years.

**NPV/IRR:** **Not implemented** (bonus) — marked Not implemented in matrix.

---

## 4. EV charging income calculator

### 4.1 Utilization without double counting

**Rule:** Energy is computed from sessions × kWh/session only.  
`utilization` (0–1) scales **sessions/day** (or effective operating capacity), **not** both sessions and kWh:

`effective_sessions_day = sessions_day × utilization`  
`energy_sold_day_kwh = points × effective_sessions_day × kwh_per_session`  
`energy_sold_month_kwh = energy_sold_day_kwh × operating_days_month`  
`energy_sold_year_kwh = energy_sold_month_kwh × 12`

Do **not** also multiply kWh/session by utilization.

### 4.2 P&L

`revenue_month = energy_sold_month_kwh × selling_price`  
`electricity_cost_month = energy_sold_month_kwh × purchase_cost`  
`opex_month = rent + staff + maintenance + other`  
`net_profit_month = revenue_month − electricity_cost_month − opex_month`  
`net_profit_year = net_profit_month × 12`

If `capital_cost` > 0:  
`payback_months = capital_cost / max(net_profit_month, ε)` (null if profit ≤ 0)  
`break_even_months = payback_months` (same simple definition; document as capital recovery months)

---

## 5. Settings keys (group `calc`)

| Key | Default | Unit | Sample |
|-----|---------|------|--------|
| `calc.currency` | INR | code | no |
| `calc.hybrid.peak_sun_hours` | 5.0 | h/day | yes |
| `calc.hybrid.performance_ratio` | 0.75 | fraction | yes |
| `calc.hybrid.system_loss_factor` | 0.0 | fraction | yes |
| `calc.hybrid.losses_separate` | false | bool | no |
| `calc.hybrid.panel_wattage` | 550 | W | yes |
| `calc.hybrid.area_per_panel_m2` | 2.5 | m² | yes |
| `calc.hybrid.battery_chemistry` | LiFePO4 | text | yes |
| `calc.hybrid.usable_dod` | 0.90 | fraction | yes |
| `calc.hybrid.battery_efficiency` | 0.95 | fraction | yes |
| `calc.hybrid.inverter_efficiency` | 0.95 | fraction | yes |
| `calc.hybrid.design_margin` | 1.25 | fraction | yes |
| `calc.hybrid.system_voltage` | 48 | V | yes |
| `calc.hybrid.default_simultaneity` | 0.70 | fraction | yes |
| `calc.hybrid.default_usage_factor` | 1.0 | fraction | yes |
| `calc.hybrid.self_consumption_share` | 0.70 | fraction | yes |
| `calc.hybrid.export_enabled` | true | bool | yes |
| `calc.hybrid.export_rate` | 3.0 | currency/kWh | **Sample/Demo** |
| `calc.hybrid.tariff` | 8.0 | currency/kWh | **Sample/Demo** |
| `calc.hybrid.gst_rate` | 0.18 | fraction | yes |
| `calc.hybrid.panel_unit_price` | 120 | currency | yes |
| `calc.hybrid.inverter_unit_price` | 590 | currency | yes |
| `calc.hybrid.battery_price_per_kwh` | 250 | currency/kWh | yes |
| `calc.hybrid.bos_flat` | 150 | currency | yes |
| `calc.hybrid.installation_default` | 90 | currency | yes |
| `calc.hybrid.transport_default` | 120 | currency | yes |
| `calc.hybrid.structure_default` | 75 | currency | yes |
| `calc.hybrid.wiring_default` | 50 | currency | yes |
| `calc.hybrid.engineering_default` | 40 | currency | yes |
| `calc.hybrid.om_annual_default` | 500 | currency/year | yes |
| `calc.hybrid.generator_credit_factor` | 0.8 | fraction | yes |
| `calc.hybrid.first_year_degradation` | 0 | fraction | yes |
| `calc.hybrid.tariff_source` | Sample/Demo | text | yes |
| `calc.investment.default_analysis_years` | 10 | years | no |
| `calc.ev.default_utilization` | 0.6 | fraction | yes |

Each setting stores value, type, unit, effective_from, source_reference, is_sample, status via existing SettingsService.

---

## 6. Validation rules (server-side)

Reject with clear messages when:

- Any quantity, wattage, hours, price, or cost is negative  
- Efficiencies or DoD or PR outside (0, 1] (design margin must be ≥ 1)  
- PSH, voltage, panel W, area ≤ 0 when required  
- Division by zero risk (DoD×η_b×η_i, gen_factor, critical_load when backup>0)  
- Non-numeric where number expected  

---

## 7. Snapshots (`calculation_runs`)

Immutable JSON columns: `inputs`, `assumptions` (setting keys + values + versions/effective dates), `steps` (intermediates), `outputs`.  
Link optional `lead_id`, `customer_id`, `visit_id`.  
`re-run with current settings` → **new** row; old row unchanged.

PDF hybrid report reads **only** the snapshot (not live Settings).

---

## 8. Worked examples (hand arithmetic)

See Phase 5 report / tests for three scenarios with comments matching this spec.

### Example A — Small residential hybrid
Loads: 10× LED 10W × 5h; 2× fan 60W × 8h; 1× fridge 150W × 24h (critical).  
Backup 4h; PSH 5; PR 0.75; L 0; Wp 550; DoD 0.9; η_b=η_i=0.95; m=1.25; V=48; s=0.7; u=1.

### Example B — Higher backup / more critical load
Same as A but fridge + 1× router 20W×24h critical; backup 8h.

### Example C — Roof constrained
Large load daily energy needing > roof capacity; `constrain_to_roof=true`, area 20 m², 2.5 m²/panel.

---

## 9. Deviations from suggested master-prompt formulas

None material. Generator reduction uses documented `generator_credit_factor` (PDF silent). Self-consumption capped by annual load. EMI uses standard annuity; zero-interest handled. EV utilization scales sessions only.
