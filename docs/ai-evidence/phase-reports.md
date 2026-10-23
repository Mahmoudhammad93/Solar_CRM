# AI Evidence - Phase Reports

Append-only. Never rewrite prior phase entries.

---

## Phase 0 - Preflight, clock, atomic requirements

**Time (shell):** `2026-10-23 14:34:27 EEST`

**Git:** commit `e10c050` on `master` (follow-up amend commit may update this file)

**Elapsed / remaining:** clock start not set (receipt timestamp awaited).

### What was built
- `docs/MASTER_PROMPT.md` (confirmed present)
- `docs/project-clock.md` (awaiting receipt timestamp)
- `docs/requirements-matrix.csv` (217 atomic rows)
- `docs/feature-status.md` (all Not implemented)
- `docs/known-limitations.md`
- `docs/stack-decisions.md`
- `docs/ai-evidence/phase-reports.md` (this file)
- `scripts/generate_requirements_matrix.py`
- Laravel-oriented `.gitignore`
- Git repository initialized; Phase 0 docs committed

### Commands run + results
- `php -v` -> PHP 8.3.33
- `composer -V` -> 2.8.2
- `node -v` / `npm -v` -> v22.23.2 / 10.9.8
- `mysql --version` -> 8.0.46 client; `mysql -e` without password -> ERROR 1045
- Packagist: Laravel latest stable `v13.38.0` (php ^8.3); Filament `v5.10.3`; `filament/support` requires `illuminate/contracts ^11.28|^12.0|^13.0` and `livewire/livewire ^4.4.7`
- `python3 scripts/generate_requirements_matrix.py` -> TOTAL_ROWS=217
- `wc -l docs/requirements-matrix.csv` -> 218 (header + 217)
- `git init` + commit `e10c050`

### Requirement status summary
- Fully working: none
- Simulated: none
- Partial: none
- Not implemented: all matrix rows (217)

### Known bugs
- None in application code (no app yet).

### Deviations / assumptions
- Clock file created in awaiting state because human has not yet supplied receipt timestamp.
- Identity honesty: session is Auto/Composer, not Claude (disclosed).
- Master-prompt architecture items tagged section `MP` (12 rows) so they are tracked alongside PDF sections 1-13.

### Human manual verify
1. Confirm receipt date/time/timezone so clock can be finalized.
2. Spot-check matrix section counts vs PDF.
3. Confirm stack proposal in `docs/stack-decisions.md` before Phase 1 install.

### Next phase readiness
Ready for Phase 1 scaffolding after receipt timestamp is provided and clock is filled (or human explicitly authorizes provisional start).

---

## Phase 1 - Foundation: install, auth, roles, settings, audit, numbering, branding

**Time (shell):** `2026-10-23 15:00:03 EEST` (approx report time; verify with `date` at commit)

**Elapsed / remaining:** clock start not set (receipt timestamp still awaited).

### What was built
- Laravel 13.38.0 + Filament 5.10.3 + Livewire 4.4.8 on MySQL 8 (Docker `solar_crm_mysql:3308`)
- Users (phone, role, branch/team, is_active, last_login_at), 6 roles, DB permission matrix, Gates/Policies, RecordScoping helper
- SettingsService (typed, cache+invalidate, versions), AuditLogger (redaction), NumberingService (lockForUpdate), BrandingService
- Filament Settings page (branding/security/numbering + Unavailable tab), Change password page
- Demo seeders, README credentials, foundation Feature tests + MySQL concurrency test
- `.env.example` placeholders only

### Commands run + results
- `composer create-project laravel/laravel ^13.0` (prefer-dist) OK
- `composer require filament/filament:^5.10` OK -> v5.10.3
- `php artisan migrate:fresh --seed` OK
- `php artisan test --testsuite=Feature` -> **15 passed, 1 skipped** (MySQL-only test skipped on sqlite)
- `DB_CONNECTION=mysql ... php artisan test --filter=NumberingMysqlConcurrencyTest` -> **passed** (12 parallel allocations, no duplicates)
- `curl /admin/login` -> HTTP **200**, branding name present
- Earlier failure: parallel numbering under sqlite transaction wrap / wrong DB env for child processes â€” fixed by dedicated MySQL test class + env passthrough

### Requirement status summary (matrix)
- Fully working: 30
- Partial: 4
- Simulated: 0
- Not implemented: 183

### Known bugs
- None blocking Phase 1 tests. SET-01 manual logo persistence check not human-confirmed yet.

### Deviations / assumptions
- Dedicated Docker MySQL on 3308 instead of system MySQL (no sudo password for socket root).
- Custom RBAC (no Spatie/Shield) per Phase 1 boundary.
- Receipt clock still awaiting human timestamp.
- Identity: Auto/Composer session (not Claude).

### Human manual verify
1. Login as `admin@solar-crm.test` / `Password1!` at `/admin/login`.
2. Settings â†’ change app name + upload logo â†’ Save â†’ refresh â†’ logout/login (SET-01/SET-09).
3. Account â†’ Change password (SET-02): wrong current rejected; new works; old fails.
4. Login as `executive@solar-crm.test` â€” Settings nav should be inaccessible / 403 (SET-07).
5. Login as `inactive@solar-crm.test` â€” blocked.
6. Narrow browser to mobile width; confirm responsive Filament shell.
7. Provide assignment receipt timestamp for `docs/project-clock.md`.

### Next phase readiness
Foundation ready for domain modules (leads/visits) in Phase 2+.

---

## Phase 2 - Leads, customers, follow-ups, tasks

**Time (shell):** `2026-10-23 15:11:46 EEST`

**Elapsed / remaining:** clock start not set (receipt timestamp still awaited).

### What was built
- Lookups table + Filament LookupResource (Settings-managed dropdowns; deactivation preserves values)
- Leads (all PDF field groups), customers, interactions, stage history, tasks (polymorphic)
- LeadConversionService (transactional, idempotent), LeadDuplicateChecker, RecordScoping team/assigned
- CSV export for filtered leads; CSV import skipped; calendar Partial (list/due-today/overdue shipped)

### Commands run + results
- `php artisan migrate` Phase 2 tables OK
- `php artisan test --testsuite=Feature` -> **22 passed, 1 skipped**

### Requirement status
See matrix totals after Phase 2 update.

### Known bugs
- None blocking AT-02 tests.

### Deviations / assumptions
- Task calendar view not implemented (list tabs only).
- Lead CSV import Not implemented.
- Visit/Quote/Project/Ticket morph targets stubbed as labels until those modules exist.

### Human manual verify (AT-02 / SET-03)
1. Login admin@solar-crm.test / Password1!
2. Administration â†’ Lookups: add lead_source value; confirm it appears on Lead form Source + table filter.
3. Create lead, assign owner, add interaction note, schedule follow-up, search/filter.
4. Login as executive A vs B: cannot open the other's lead URL.
5. Convert lead to customer twice; same customer_number.

### Next phase readiness
Ready for visits / calculators / quotations phases.

---

## Phase 3 - Site visits, survey, documents, visit PDF

**Time (shell):** `2026-10-23 15:19:53 EEST`

**Elapsed / remaining:** clock start not set (receipt timestamp still awaited).

### What was built
- Visits: auto Visit ID (`NumberingService`), lead/customer link, schedule, assigned surveyor, configurable status lookups, site address, manual GPS, Demo preview map (no external calls), reschedule/cancel reason required, `visit_histories` log
- Survey + utility + outcome fields on Filament Visit form; preliminary BOM free-text (catalogue link later)
- Documents: polymorphic attachments, private `local` disk, UUID stored names, extension+MIME-by-content+max size (`documents.max_upload_kb`), authorized download route+policy, sensitive types role-restricted, audit on upload/delete
- Visit report PDF via `barryvdh/laravel-dompdf` v3.1.2 + Blade + `BrandingService` snapshot; filename `visit-report-{ref}-{Ymd-His}.pdf`
- Surveyor query/policy scope on `assigned_surveyor_id`

### Upload validation rules
- Max size: Settings `documents.max_upload_kb` (default 5120)
- Allowed extensions ? MIME: jpg/jpeg?image/jpeg, png, webp, gif, pdf, doc/docx, xls/xlsx, csv
- Detected with `mime_content_type` on real path; mismatch rejected
- Extra head-byte reject for `<?php` / `<?=` / `<script`

### PDF library decision
- Installed **barryvdh/laravel-dompdf ^3.1** (verified stack); Blade templates kept; sample opened via `pdfinfo`: 1 page A4, 879706 bytes, `%PDF-1.7`, Producer dompdf 3.1.6

### Commands run + results
- `php artisan migrate --force` ? visits/histories/documents OK
- `php artisan test --filter=VisitScopingAndDocumentsTest` ? **5 passed**
- AT-03 scripted: VIS-2026-0001 schedule?complete?upload?reschedule?history?PDF

### Requirement status
- REQ-3-C-001..004, REQ-5-C-001, REQ-MP-FILES, AT-03 ? Fully working
- REQ-3-C-005 map ? **Simulated** (Demo preview)
- REQ-8-A-003 / REQ-9-G-002 / REQ-10-011 ? Partial (visit PDF branding only)

### Known bugs
- None blocking AT-03 automated checks. Filament FileUpload temp path extension normalized in DocumentsRelationManager.

### Deviations / assumptions
- Map never calls external providers; labelled Demo preview.
- Preliminary BOM free-text until catalogue products exist.
- Polymorphic documents ready for quote/project/installation/ticket owners when those models land.

### Human manual verify (AT-03)
1. Login admin / surveyor; Field ? Visits ? create scheduled visit, assign surveyor.
2. Fill survey/utility/outcome; upload site/meter photo; mark completed.
3. Reschedule with reason; confirm Histories tab shows events.
4. Download visit report PDF; confirm branding + non-empty file.
5. As other surveyor: unassigned visit ? 403/404; download foreign doc ? 403.

### Next phase readiness
Ready for hybrid calculators / quotations (Phase 4+).

---

## Phase 4 - Product catalogue and inventory core

**Time (shell):** `2026-10-23 15:31:23 EEST`

**Elapsed / remaining:** clock start not set (receipt timestamp still awaited).

### What was built
- Products (auto PRD reference + SKU), categories/brands/units via lookups, configurable `product_attributes`, vendors, warehouses, Filament catalogue with search/filters/archive
- `stock_movements` ledger + `stock_balances` (updated only inside `StockService` transactions with `lockForUpdate`)
- States: available/reserved/allocated/issued/returned/damaged; negative stock blocked unless Settings `inventory.allow_negative_stock` + Super Admin / `inventory.settings` (audit `stock.negative_allowed`)
- Settings F tab; Low stock page; `stock:reconcile` / `stock:issue` commands
- Seed: 25 products, 2 warehouses, 3 vendors, opening movements; 5 low-stock SKUs

### Reconciliation test output
- `php artisan test --filter=StockServiceTest` ? **4 passed** (ledger reconcile empty mismatches; negative blocked; policy+permission override; archive still loadable)
- `StockMysqlConcurrencyTest` ? **1 passed** (10 parallel issues of qty 1 from stock 5 ? exactly 5 success / 5 fail; available=0)
- `php artisan stock:reconcile` on seeded DB ? `OK: balances reconcile to ledger.`

### Requirement status
- REQ-5-B-001..003, REQ-6-C-001, REQ-6-C-003, REQ-9-F-001..002 ? Fully working
- REQ-6-C-004 / AT-08 ? Partial (catalogue/stock/low-stock done; project BOM link Phase 7)
- REQ-6-C-005 PO/GRN ? Not implemented (bonus)

### Known bugs
- None blocking catalogue/stock tests.

### Deviations / assumptions
- No purchase orders/GRN.
- Project material allocation deferred to Phase 7.
- Soft reservation behavior setting stored; hard path is default enforcement via StockService.

### Human manual verify (AT-08 first half)
1. Inventory ? Products: search/filter by category/brand; open detail.
2. Low stock: shows PNL-HC-450, INV-VN-8K, BAT-VN-10KWH, PMP-AP-5HP, WR-DC-6MM.
3. Settings ? Inventory: reorder default / negative policy.
4. Warehouses / Vendors CRUD.

### Next phase readiness
Ready for hybrid calculators / quotations.

---

## Phase 5 - Calculators (hybrid, investment, EV) + hybrid PDF

**Time (shell):** `2026-10-23 15:47:36 EEST`

**Elapsed / remaining:** clock start not set (receipt timestamp still awaited).

### Spec first
- Authored `docs/calculation-spec.md` before coding (formulas, units, defaults, additional assumptions, validation, snapshots).

### What was built
- `HybridCalculatorService`, `InvestmentCalculatorService`, `EvCalculatorService` — server-side only; Settings-driven constants
- Immutable `calculation_runs` snapshots; re-run overlays current Settings into a **new** row
- Filament pages: Hybrid (load repeater + PDF), Investment, EV
- Settings ? Calculators tab (SET-04 keys)
- Hybrid PDF via Dompdf from **stored snapshot** only; investment/EV PDF Partial

### Worked examples (hand vs actual)

| Example | Key inputs | Hand expected | Actual |
|---------|------------|---------------|--------|
| A residential | loads 10×10W×5h + 2×60×8 + 1×150×24 crit; backup 4h; PSH5; PR0.75; Wp550; DoD0.9; ?0.95; m1.25; V48 | daily 5.06 kWh; batt 0.923 kWh; 3 panels / 1.65 kWp; annual gen 1.65×5×365×0.75=2258.44 | daily 5.06; batt 0.9234; panels 3; kWp 1.65; annual 2258.44 |
| B higher backup | +router 20W crit; backup 8h | batt ?2.093 kWh (>A) | 2.093; greater than A |
| C roof cap | constrain area 5 m² / 2.5 | max 2 panels / 1.1 kWp | panels 2; kWp 1.1 |

### Tests
- `HybridCalculatorTest` + `InvestmentAndEvCalculatorTest` ? **11 passed** (scenarios, sensitivity, immutability, validation, PDF/HTML snapshot equality)

### Status
- AT-04, AT-05, SET-04, REQ-4-* (except NPV/IRR) ? Fully working
- REQ-4-B-003 NPV/IRR ? Not implemented
- Investment/EV PDF ? Partial (hybrid PDF Fully working)

### Next phase readiness
Ready for quotations (Phase 6) consuming calculation runs.

---

## Phase 6 - Quotations, taxes, PDF, QR, signature

**Time (shell):** `2026-10-23 16:00:38 EEST`

**Elapsed / remaining:** clock start not set (receipt timestamp still awaited).

### What was built
- Quotations with revisions, workflow transitions, payment schedule, hybrid import from CalculationRun, pump proposal fields
- `QuotationTotals` (sole totals engine): CGST+SGST vs IGST via Settings `finance.tax_split_rule` / company vs customer state; Money minor-units half-up rounding; optional nearest-rupee round-off
- Immutable locked snapshots (tax/branding/lines/totals); edit after Sent ? new revision
- Branded PDF, public `/quotes/verify/{token}` (minimal fields), Endroid SVG QR, demo signature label, MessageService Simulated share

### Sample totals

**Hybrid QUO-2026-0001 (intra-state Maharashtra)** — PDF `quotation-quo-2026-0001-r1-20261023-130037.pdf` (880,865 bytes)

| Field | Amount |
|-------|--------|
| Subtotal | 2,000.00 |
| Discount (10%) | 200.00 |
| Taxable | 1,800.00 |
| CGST 9% | 162.00 |
| SGST 9% | 162.00 |
| Grand total | 2,124.00 |

**Pump QUO-2026-0002 (inter-state Karnataka)** — PDF `quotation-quo-2026-0002-r1-20261023-130038.pdf` (881,404 bytes)

| Field | Amount |
|-------|--------|
| Subtotal | 1,640.00 |
| Taxable | 1,640.00 |
| IGST 18% | 295.20 |
| Grand total | 1,935.20 |

### Tests
`QuotationTotalsAndWorkflowTest` ? **9 passed** (intra/inter hand totals, discount limit, round-off, snapshot immutability, PDF/HTML, auth, verify page privacy, QR SVG)

### Status
AT-06 Fully working; REQ-5-A-* Fully working; NPV N/A; order conversion still Phase 7.

### Next phase readiness
Ready for order/project conversion (Phase 7).
