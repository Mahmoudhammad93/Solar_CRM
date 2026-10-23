# Live demonstration script (12–20 min) — Validation V7

**Purpose:** One continuous screen recording (PDF §1 suggested structure).  
**DEMO ONLY** — fictional data. Base URL: `http://127.0.0.1:8000/admin/login`  
**Shared password:** `Password1!` (all active demo users)

**Target runtime:** **~15 minutes** (hard floor 12 / ceiling 20). Stick to budgets below.

| Segment | Budget | Cumulative |
|---------|-------:|----------:|
| A — Overview / stack / login | 1.5 min | 0:00–1:30 |
| B — Core sales → project path | 5.5 min | 1:30–7:00 |
| C — Ops / inventory / service / reports / RBAC | 4.0 min | 7:00–11:00 |
| D — Settings + honesty + status sheet | 4.0 min | 11:00–15:00 |
| Buffer / wrap | ≤5 min | → ≤20:00 |

---

## Pre-recording checklist

Do these **before** hitting Record:

1. **Fresh seed**
   ```bash
   php artisan demo:reset --force
   # or: php artisan migrate:fresh --seed
   php artisan serve
   ```
2. **Confirm anchors** (after seed — numbers are stable on fresh DB):

   | Use | Record |
   |-----|--------|
   | Golden lead | `LEAD-2026-0003` / Demo Lead 3 |
   | Completed visit | `VIS-2026-0003` |
   | Hybrid snapshot (OLD) | `CAL-2026-0001` |
   | Investment / EV runs | `CAL-2026-0004` / `CAL-2026-0005` |
   | Hybrid accepted (already converted) | `QUO-2026-0005` |
   | Pump sent (PDF) | `QUO-2026-0004` |
   | Draft hybrid (accept+convert live) | `QUO-2026-0001` |
   | Order / project | `ORD-2026-0001` / `PRJ-2026-0001` |
   | Shortage BOM line | Project 1 → Materials → SKU `PNL-HC-550`, Required **100**, Shortage icon |
   | Near-renewal AMC | `AMC-2026-0002` (ends ~+20 days) |
   | Ticket | `TKT-2026-0001` open |
   | Invoice | `INV-2026-0001` partial + Simulated gateway |

3. **Browser:** new profile / private window; zoom 100%; bookmarks bar hidden; OS notifications off; close Slack/mail popups.
4. **Logo file ready:** small PNG/SVG on Desktop (e.g. `demo-logo.png`) for Settings → Branding upload.
5. **Editor tabs ready (second monitor or pause):** `docs/feature-status.md`, `docs/known-limitations.md`, `docs/calculation-spec.md` (optional), this script.
6. **Claude evidence:** folder/screenshots of AI session exports ready to flash at end (or say “Composer/Auto session — disclosed in known-limitations”).
7. **Dry-run once** with this script; if a step fails, use Fallback (below) — do not improvise silently.

---

## Fallback plan (on camera)

If anything breaks:

1. Say aloud: **“This step failed in the live demo — I’ll show the status sheet entry.”**
2. Open `docs/feature-status.md` (or CSV) → find the ID → read Status + Gap note.
3. Continue to the next verified step. Do **not** claim delivery/sent for WhatsApp/email; do **not** invent success.

| Failure | Fallback |
|---------|----------|
| Create lead form error | Open existing `Demo Lead 1` / `LEAD-2026-0001` |
| Visit PDF missing | Show survey fields on `VIS-2026-0003` only |
| Calculator PDF fails | Show on-screen outputs + say Partial/known limitation |
| Convert fails | Open `QUO-2026-0005` already linked to `ORD/PRJ-2026-0001` |
| Settings logo not in nav | Hard refresh; if still missing → SET-01 Partial in status sheet |
| Password lockout confusion | `demo:reset --force` after recording — do password demo last |

---

## Demo users (speak once)

| Role | Email |
|------|-------|
| Super Admin | `admin@solar-crm.test` |
| Sales Executive | `executive@solar-crm.test` |
| (others) | manager / surveyor / pm / ops `@solar-crm.test` |
| Blocked | `inactive@solar-crm.test` |

---

# Segment A — Overview (0:00–1:30) · Budget **1.5 min**

### Speaking notes
“This is Solar CRM — Laravel 13 + Filament 5 + MySQL. One admin app: leads → visits → hybrid calc snapshots → quotations → orders/projects → inventory → tickets/AMC. Settings are DB-backed. External WhatsApp/email/maps/payment are Demo/Simulated.”

### Click path
| # | Click / action | Verified |
|---|----------------|----------|
| A1 | Open browser → `http://127.0.0.1:8000/admin/login` | PASS |
| A2 | Login `admin@solar-crm.test` / `Password1!` → Dashboard | PASS |
| A3 | Point sidebar groups: Sales, Field, Calculators, Operations, Inventory, Service, Reports, Administration | PASS |
| A4 | Speak ER in one breath: Lead→Visit/Calc/Quote→Order→Project(+milestones/BOM)→Invoice; Customer→Ticket/AMC; Settings versioned | — |
| A5 | Mention seed: `demo:reset --force`; campaign `DEMO-P12` | — |

### Interviewer Q&A (short answers)
- **Why Filament?** Fast consistent admin UI + policies; business logic stays in service classes.
- **Where is truth stored?** MySQL Eloquent models; calcs/quotes use immutable JSON snapshots.
- **Demo vs prod messaging?** Demo driver logs `demo_simulated` only; live needs env secrets + tested client.

---

# Segment B — Core workflow (1:30–7:00) · Budget **5.5 min**

Pace: ~45–60s per subsection. Prefer seeded deep-links; create only where required.

## B1 Lead create / assign / follow-up (~50s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| B1.1 | Sales → **Leads** → **New lead** (`/admin/leads/create`) | “Creating a fictional lead.” | PASS |
| B1.2 | Name `Demo Live Lead`; Source `website`; Stage `new`; Assigned to **Eva Sales Executive**; Save | | PASS (form) |
| B1.3 | Open **Demo Lead 1** (`/admin/leads/1/edit`) — show follow-up / notes | “Seeded follow-ups due/overdue also on list.” | PASS |
| B1.4 | Search `Demo Lead` | | PASS |

## B2 Visit schedule / complete (~40s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| B2.1 | Field → **Visits** → open `VIS-2026-0003` (`/admin/visits/3/edit`) | “Completed survey on Lead 3.” | PASS |
| B2.2 | Scroll survey: roof, area, utility, GPS; mention Demo map (no live tiles) | | PASS |
| B2.3 | Optional: header/actions visit PDF if present; else skip (Fallback) | | FLAG if missing |

## B3 Hybrid calculator (~90s) — change load, backup, PSH, DoD, tariff

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| B3.1 | Calculators → **Hybrid solar** (`/admin/hybrid-calculator`) | “Server-side; indicative estimate.” | PASS |
| B3.2 | Link Lead = Demo Lead 3 / Visit VIS-2026-0003 if selectors present | | PASS |
| B3.3 | Load table: change qty/hours on a row; set backup hours ↑; note Peak sun hours / DoD / tariff fields | “Units kWh, kWp, W.” | PASS |
| B3.4 | **Calculate & save snapshot** → point assumptions / steps / outputs | | PASS |
| B3.5 | **Download PDF** | | PASS (action exists) |
| B3.6 | Say you’ll prove OLD snapshot immutability in Settings segment (`CAL-2026-0001`) | | — |

## B4 Investment + EV (~40s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| B4.1 | **Investment** (`/admin/investment-calculator`) — tweak tariff or opex → Calculate | “NPV/IRR not implemented — status sheet.” | PASS |
| B4.2 | **EV charging** (`/admin/ev-calculator`) — change sessions/day → Calculate | | PASS |

## B5 Quotations hybrid + pump, PDF/QR (~70s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| B5.1 | Sales → **Quotations** → open `QUO-2026-0005` (accepted hybrid) | | PASS |
| B5.2 | **Download PDF** — branding, QR, Demo e-signature area | “E-sign not legally verified.” | PASS |
| B5.3 | Open `QUO-2026-0004` (pump, sent) — show pump proposal type + PDF | | PASS |
| B5.4 | Optional New quotation if time; else say “create path exists at /admin/quotations/create” | | PASS create URL |

## B6 Accept + convert + idempotent second click (~60s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| B6.1 | Open draft `QUO-2026-0001` → header **changeStatus** → `accepted` → confirm | | PASS (page/action) |
| B6.2 | **Convert to order/project** → expect success / new ORD+PRJ | | PASS (action; tests) |
| B6.3 | Open already-converted `QUO-2026-0005` → **Convert to order/project** again | “Already converted — no duplicate.” | PASS (`ConvertQuotationToProjectTest`) |
| B6.4 | Optional: Share WhatsApp → **SIMULATED – not delivered** | | PASS (label) |

### Interviewer Q&A
- **How are quote totals computed?** `QuotationTotals` CGST/SGST or IGST from Settings; minor-unit rounding.
- **Why snapshots?** Changing Settings must not rewrite issued quotes/calcs (`calculation_runs` / revision JSON).
- **Idempotent convert?** Same accepted quote returns existing order/project (`created=false`).

---

# Segment C — Ops / reports / RBAC (7:00–11:00) · Budget **4 min**

## C1 Milestones + shortage + low stock (~70s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| C1.1 | Operations → **Projects** → `PRJ-2026-0001` | | PASS |
| C1.2 | Tab **Milestones** — progress / statuses | | PASS |
| C1.3 | Tab **Materials (BOM)** — row `PNL-HC-550` Required **100**, Shortage | “Demo shortage vs warehouse.” | PASS (DB+columns); UI scroll if needed |
| C1.4 | Row action **Allocate** small qty if stock allows — or explain reserve/allocate/issue | | PASS (actions exist) |
| C1.5 | Inventory → **Low stock** (`/admin/low-stock-products`) | | PASS |

## C2 Ticket + AMC (~40s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| C2.1 | Service → **Tickets** → `TKT-2026-0001` | | PASS |
| C2.2 | **AMC** → `AMC-2026-0002` near renewal | “Reminders need `schedule:run`.” | PASS |
| C2.3 | Finance → **Invoices** — Simulated gateway note; partial payment | | PASS |

## C3 Dashboard KPI + reports CSV (~50s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| C3.1 | **Dashboard** — click KPI **New leads** (deep link) | “KPIs from SQL, not hard-coded.” | PASS |
| C3.2 | Reports → **Reports** hub → Lead report → **Export CSV** | “Same query as table; UTF-8 BOM.” | PASS |

## C4 Role permissions — two roles + direct URL denied (~60s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| C4.1 | User menu → Log out | | PASS |
| C4.2 | Login `executive@solar-crm.test` / `Password1!` | “Scoped leads.” | PASS |
| C4.3 | Open Leads — fewer/assigned-only | | PASS (tests) |
| C4.4 | Paste URL `/admin/manage-settings` → **403 Forbidden** | “Server-side policy, not hidden menu only.” | PASS (HTTP 403) |
| C4.5 | Log out → login `admin@solar-crm.test` again | | PASS |

### Interviewer Q&A
- **How is RBAC enforced?** Permissions on roles + Laravel policies + `RecordScoping` for executives/surveyors.
- **Shortage meaning?** Required qty > available warehouse stock (`hasShortage()`).

---

# Segment D — Settings + honesty (11:00–15:00) · Budget **4 min**

## D1 Branding name/logo (~45s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| D1.1 | Administration → **Settings** → Branding tab | | PASS |
| D1.2 | Change **Application / website display name** → e.g. `Solar CRM Demo` → Save | | PASS |
| D1.3 | Upload logo / login logo from Desktop → Save → hard refresh | Show nav brand + login later | PASS (fields); SET-01 Partial if logo UI lag |
| D1.4 | Optional: open a quote PDF again for logo_on_pdf | | PASS toggle exists |

## D2 Password change (~40s) — do near end

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| D2.1 | Account → **Change password** | | PASS |
| D2.2 | Current = wrong → expect “Current password is incorrect.” | | PASS (`PasswordChangeTest`) |
| D2.3 | Current `Password1!` → New `TempPass1!` → Save → logout → login with new → works | | PASS |
| D2.4 | Try old `Password1!` → fails; set back to `Password1!` **or** plan `demo:reset` after recording | | PASS |

## D3 Lookups + workflow (~30s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| D3.1 | **Lookups / dropdowns** → group `lead_source` → create value `demo_expo` | | PASS |
| D3.2 | Optionally edit a `lead_stage` label; open Lead form → new source appears | | PASS |

## D4 Hybrid defaults vs OLD snapshot (~50s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| D4.1 | Settings → **Calculators** — change Peak sun hours, DoD, loss/PR, panel W → Save | | PASS |
| D4.2 | Open Hybrid calculator → Calculate & save **NEW** run — outputs differ | | PASS |
| D4.3 | Speak: seeded `CAL-2026-0001` JSON unchanged (immutable); optional open via DB/tinker or prior PDF | “Old snapshot not rewritten.” | PASS (tests/V3) |

## D5 WhatsApp Demo + audit + persistence (~45s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| D5.1 | Administration → **Communications** — mode Demo; credentials **Not configured**; template Preview | “SIMULATED.” | PASS |
| D5.2 | Test connection if present → simulated; no secret fields | | PASS |
| D5.3 | **Message logs** — statuses `demo_simulated` / `failed` only | | PASS |
| D5.4 | **Activity center** — recent audit entries | | PASS |
| D5.5 | Logout → login → Settings name still new (DB persistence). Optional mention restart: `php artisan serve` again | | PASS (SET-09) |

## D6 Limitations + feature status + Claude evidence (~30s)

| # | Click path | Speak | Verified |
|---|------------|-------|----------|
| D6.1 | Open `docs/known-limitations.md` — WhatsApp/email Demo; payment Simulated; NPV/IRR NI; PO/GRN NI; 2FA NI; custom fields NI | | — |
| D6.2 | Open `docs/feature-status.md` — counts Fully working / Simulated / Partial / Not implemented | | — |
| D6.3 | Flash Claude/Composer evidence folder; disclose assistant used | | — |

### Interviewer Q&A
- **Where do secrets live?** `.env` only; UI shows Configured / Not configured.
- **What happens if Settings change after a quote?** New work uses new defaults; issued snapshots stay frozen.
- **Backup?** Manual plan in `docs/backup-plan.md`; automated restore Unavailable.

---

## Post-recording

```bash
php artisan demo:reset --force   # restore Password1! and golden path
```

Upload recording + point evaluator to README, `docs/feature-status.md`, `docs/acceptance-evidence.md`.

---

## Dry-run verification log (Validation V7)

**Method:** Fresh `migrate:fresh --seed`; HTTP GET as `admin@solar-crm.test` / executive; browser login smoke; PHPUnit subset (convert/password/messaging/settings/dashboard).

| Step group | Result |
|------------|--------|
| A login + dashboard | **PASS** |
| B leads/visits/calcs/quotes URLs | **PASS** (25/25 admin paths 200) |
| B convert idempotency | **PASS** (feature tests) |
| C project/milestones/materials/low-stock/tickets/AMC/invoices/reports | **PASS** |
| C executive → `/admin/manage-settings` | **PASS** HTTP **403** |
| D settings/comms/lookups/password/activity/message-logs | **PASS** |
| Materials shortage row `PNL-HC-550` qty 100 | **PASS** (DB + table columns); scroll BOM tab on camera |
| Visit PDF header action | **FLAG** — if absent on camera, skip and cite Partial/known limitation |
| Live logo visible on login without refresh quirks | **FLAG** — SET-01 Partial until human confirms; hard-refresh |

**No product code changes in V7** (script-only). Totals budgets: 1.5+5.5+4+4 = **15 min** (within 12–20).
