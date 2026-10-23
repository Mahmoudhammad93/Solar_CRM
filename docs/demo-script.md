# Live walkthrough demo script (AT-01 … AT-13)

**DEMO ONLY** — all records are fictional. Reset anytime with:

```bash
php artisan demo:reset --force
# equivalent: php artisan migrate:fresh --seed
```

Login base URL: `/admin/login`  
Shared demo password for all active users: `Password1!`

## Demo users

| Role | Email |
|------|-------|
| Super Admin | `admin@solar-crm.test` |
| Sales Manager | `manager@solar-crm.test` |
| Sales Executive | `executive@solar-crm.test` |
| Surveyor | `surveyor@solar-crm.test` |
| Project Manager | `pm@solar-crm.test` |
| Inventory / Finance / Service | `ops@solar-crm.test` |
| Deactivated (blocked) | `inactive@solar-crm.test` |

Seeded scenario marker: Settings `demo.scenario_version=12`, leads with `campaign_details=DEMO-P12`.

---

## Click path (≈12–20 minutes)

### 1) Auth & roles (AT-01 / SET-07)
1. Login as `executive@solar-crm.test` → Dashboard shows **only** scoped KPIs/leads.
2. Open Settings → expect **403 / no access**.
3. Logout → login `admin@solar-crm.test` → Settings opens; note branding Sample/Demo flags.
4. Attempt login as `inactive@solar-crm.test` → blocked.

### 2) Leads & follow-ups (AT-02)
1. Sales → Leads → filter source/stage; open **Demo Lead 1** (follow-up due today) and **Demo Lead 2** (overdue).
2. Create a note/interaction; show search by name `Demo Lead`.
3. Show Lookups (Administration → Lookups → lead_source) — values drive the form.

### 3) Site visit (AT-03)
1. Visits → open a **completed** visit (`Demo site DEMO-VISIT-3…`).
2. Show survey fields + status history; mention visit PDF if generated from Edit actions.

### 4) Calculators (AT-04 / AT-05 / SET-04)
1. Calculators → Hybrid → load a seeded CalculationRun from Filament list / prior run references.
2. Change Peak sun hours in Settings → Calculators, re-run; show new outputs vs immutable prior snapshot.
3. Briefly open Investment + EV pages (seeded runs exist).

### 5) Quotation (AT-06)
1. Quotations → open an **Accepted** hybrid quote and a **Sent** pump quote.
2. Show line totals / tax snapshot; Download PDF; Share WhatsApp → badge **SIMULATED – not delivered**.
3. Message logs → status `demo_simulated` only.

### 6) Order / project / materials (AT-07 / AT-08)
1. My Orders → Active tab; open linked Project.
2. Milestones relation — projects at different progress (1 / 3 / 5 steps completed).
3. Materials → highlight line with elevated `quantity_required` (deliberate **shortage** vs stock).
4. Inventory → Low stock page still shows low catalogue SKUs (e.g. PNL-HC-450).

### 7) Tickets / AMC / invoice (AT-09)
1. Tickets → show Open / Assigned / In progress / Waiting / Resolved / Closed demos.
2. AMC → **Demo AMC near renewal** (ends within ~20 days); run `php artisan amc:renewal-reminders`.
3. Invoices → two invoices with **partial** payments (outstanding > 0); gateway labelled Simulated.

### 8) Dashboard & reports (AT-10)
1. Dashboard → filters date range last 30 days; KPI cards non-zero (new leads, follow-ups, quotes, projects, tickets, revenue).
2. Click a KPI → lands on filtered list.
3. Reports → Lead report → Export CSV; note UTF-8 BOM / totals match table.

### 9) Communications (SET-05 / SET-06)
1. Administration → Communications → Demo mode; credential status **Not configured**; Preview template.
2. Test connection → SIMULATED message. No secret fields.

### 10) Settings polish (AT-11 / SET-01…09)
1. Settings → Branding change app name → appears in nav (refresh).
2. Regional & finance / Documents toggles / Integrations status cards / API enable.
3. API tokens → create token (shown once); `GET /api/v1/leads` with Bearer (see `docs/api.md`).
4. Change password page (SET-02).

### 11) Honesty checklist (AT-12 / limitations)
1. Open `docs/known-limitations.md` + README demo credentials.
2. Confirm WhatsApp/payment/maps labelled Demo/Simulated; webhook Not implemented; custom fields Not implemented.

### 12) Reset
```bash
php artisan demo:reset --force
```

---

## Seeded anchors (after fresh seed)

| Kind | How to find |
|------|-------------|
| Leads | name `Demo Lead N`, campaign `DEMO-P12` |
| Customers | mobile `900100100x` |
| Visits | site `Demo site DEMO-VISIT-N` |
| Quotes | proposal hybrid + pump; statuses draft/sent/accepted/rejected |
| Projects | 3 active from accepted quotes |
| Invoices | partial payments `DEMO-PAY-1/2` |
| Tickets | summaries starting with `Demo` |
| AMC | “near renewal” ends ~+20 days |
| Tasks | titles containing `demo` / Overdue / Due today |

Exact auto-numbers (`LEAD-2026-…`, `QUO-…`) increment with NumberingService — use names above in the recording.
