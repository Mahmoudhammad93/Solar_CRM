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
- Earlier failure: parallel numbering under sqlite transaction wrap / wrong DB env for child processes ù fixed by dedicated MySQL test class + env passthrough

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
2. Settings ? change app name + upload logo ? Save ? refresh ? logout/login (SET-01/SET-09).
3. Account ? Change password (SET-02): wrong current rejected; new works; old fails.
4. Login as `executive@solar-crm.test` ù Settings nav should be inaccessible / 403 (SET-07).
5. Login as `inactive@solar-crm.test` ù blocked.
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
2. Administration ? Lookups: add lead_source value; confirm it appears on Lead form Source + table filter.
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
- `HybridCalculatorService`, `InvestmentCalculatorService`, `EvCalculatorService` ù server-side only; Settings-driven constants
- Immutable `calculation_runs` snapshots; re-run overlays current Settings into a **new** row
- Filament pages: Hybrid (load repeater + PDF), Investment, EV
- Settings ? Calculators tab (SET-04 keys)
- Hybrid PDF via Dompdf from **stored snapshot** only; investment/EV PDF Partial

### Worked examples (hand vs actual)

| Example | Key inputs | Hand expected | Actual |
|---------|------------|---------------|--------|
| A residential | loads 10ù10Wù5h + 2ù60ù8 + 1ù150ù24 crit; backup 4h; PSH5; PR0.75; Wp550; DoD0.9; ?0.95; m1.25; V48 | daily 5.06 kWh; batt 0.923 kWh; 3 panels / 1.65 kWp; annual gen 1.65ù5ù365ù0.75=2258.44 | daily 5.06; batt 0.9234; panels 3; kWp 1.65; annual 2258.44 |
| B higher backup | +router 20W crit; backup 8h | batt ?2.093 kWh (>A) | 2.093; greater than A |
| C roof cap | constrain area 5 mù / 2.5 | max 2 panels / 1.1 kWp | panels 2; kWp 1.1 |

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

**Hybrid QUO-2026-0001 (intra-state Maharashtra)** ù PDF `quotation-quo-2026-0001-r1-20261023-130037.pdf` (880,865 bytes)

| Field | Amount |
|-------|--------|
| Subtotal | 2,000.00 |
| Discount (10%) | 200.00 |
| Taxable | 1,800.00 |
| CGST 9% | 162.00 |
| SGST 9% | 162.00 |
| Grand total | 2,124.00 |

**Pump QUO-2026-0002 (inter-state Karnataka)** ù PDF `quotation-quo-2026-0002-r1-20261023-130038.pdf` (881,404 bytes)

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

---

## Phase 7 - Quotation?project, milestones, materials, My Orders

**Time (shell):** `2026-10-23 16:32:40 EEST`

**Git:** commit `365052e` on `master`

### What was built
- `ConvertQuotationToProject` ù Accepted only; `lockForUpdate`; unique `orders.quotation_id`; idempotent return of existing; copies snapshot lines/totals/schedule/site/customer; preserves quote; audit + quotation activity
- Orders / Projects migrations + models; milestones (10 defaults + weights/%); Installation (checklist/readings/Demo sign-off; completion PDF Partial); ProjectMaterial BOM from quote lines
- `AllocationService` ? `StockService` (reserve/allocate/issue/return/damage); shortage required vs available
- `ProjectMilestoneService` ù configurable transitions; hold/exception require reason; SLA due_at / overdue
- Filament: My Orders (Pending/Active/Completed/Cancelled, internal-only); Projects + relation managers; Convert action on Accepted quote
- Settings: `projects.default_delivery_days`, `default_duration_days`, `milestone_sla_days`
- Artisan `quote:convert` for concurrency tests

### Conversion data-mapping (quote snapshot ? order/project)

| Snapshot / quote field | Order | Project |
|------------------------|-------|---------|
| quotation.id | quotation_id (unique) | quotation_id |
| revision.id | quotation_revision_id | ù |
| customer_id | customer_id | customer_id |
| revision.site_address | site_address | ù |
| revision.system_type / proposal_type | project_type | ù |
| revision.system_capacity_kw | capacity_kw | ù |
| revision.totals.grand_total | contract_value | ù |
| revision.lines | lines_snapshot + ProjectMaterial rows | materials (BOM) |
| revision.totals | totals_snapshot | ù |
| revision.payment_schedule | payment_schedule | ù |
| revision.template_texts.scope / customer_terms | confirmed_scope | ù |
| owner_id | owner_id | sales_user_id |
| ù | number ORD-*, ordered_on, delivery_target, status | number PRJ-*, milestones, installation, % complete |

### Shortage example (AT-08)
- Project material `PNL-HC-550`: required **12**, warehouse AT08S available **2** ? shortage **10**, `has_shortage=true`
- Reserve 5 blocked: ùInsufficient stockù negative stock blocked by policyù
- Low-stock samples still visible: PNL-HC-450 (8/15), INV-VN-8K (2/2), BAT-VN-10KWH (3/3)

### Manual AT-07 evidence
- QUO-2026-0003 Accepted ? ORD-2026-0001 / PRJ-2026-0001
- contract_value 19399.2 equals revision grand_total; site ùAT-07 Demo Site, Nagpurù; capacity 4.4; payment schedule 30/40/30
- 10 milestones; after complete Order Confirmed ? percent_complete 5%, current `documents_pending`
- Second convert: idempotent message ùOrder/project already existsùù

### Tests
- `ConvertQuotationToProjectTest` + `ProjectMilestoneAndAllocationTest` ù **8 passed** (sqlite)
- `ConvertQuotationMysqlConcurrencyTest` ù **1 passed** (6 parallel `quote:convert`, single order)
- Quotation workflow suite still **9 passed** (sent?accepted status-only on locked revision)

### Status
AT-07 Fully working; AT-08 Fully working; installation completion PDF Partial.

### Next phase readiness
Ready for invoices/tickets/AMC (Phase 8).

---

## Phase 8 - Tickets, AMC, invoice/payment visibility

**Time (shell):** `2026-10-23 16:49:59 EEST`

**Git:** commit `929e9a8` on `master`

### What was built
- Tickets: numbering, SLA target from `tickets.default_sla_hours`, TicketWorkflow (Open‚Üí‚Ä¶‚ÜíClosed), assign/comments/history, Filament Service ‚Üí Tickets, resolution PDF
- AMC: contracts with renewal_reminder_on / next_service_on; `amc:renewal-reminders` (+ daily schedule entry; automation Partial)
- Invoices: one per order from snapshot totals/schedule; manual payments; outstanding = total ‚àí payments; overpayment rejected; gateway labelled Simulated; branded invoice PDF
- Settings Service tab for ticket/AMC SLA

### Payment example
| Step | Amount | Outstanding | Status |
|------|--------|-------------|--------|
| INV-2026-0001 created from ORD-2026-0001 | grand 19,399.20 | 19,399.20 | issued |
| Payment AT09-UPI-1 | 5,000.00 | 14,399.20 | partial |

### Manual AT-09
- TKT-2026-0002 linked to project/order ‚Üí assigned ‚Üí in_progress ‚Üí resolved ‚Üí closed (5 history rows); PDF ~880KB
- AMC-2026-0001 (ends 2027-10-23, reminder 2027-09-23, next service 2027-01-23)
- AMC-2026-0002 listed by `amc:renewal-reminders` (ends within 30 days)

### Tests
`TicketAmcInvoiceTest` ‚Äî **5 passed** (SLA, transitions, assign/close+scope, outstanding/overpay, AMC reminder)

### Status
AT-09 Fully working; AMC scheduling Partial; payment gateway Simulated.

### Next phase readiness
Ready for dashboard/reports (AT-10) / settings polish.

---

## Phase 9 - Dashboard, activity center, reports, exports

**Time (shell):** `2026-10-23 17:04:39 EEST`

**Git:** commit `fafb8a9` on `master`

### What was built
- `DashboardMetrics` + `DashboardFilters` ‚Äî all KPI cards/charts from stored data (no hard-coded numbers); role scoping via `RecordScoping`
- Filament Dashboard filters (date/branch/member/source/status) + widgets: KPI stats, sales mix/conversion, pipeline, source, monthly revenue, project status, recent activity/upcoming tasks
- KPI deep-links (`DashboardKpiQuery`) on Leads/Visits/Quotations/Projects/Tickets/Invoices lists
- Activity center: overdue tasks, audit history, read/unread `UserNotification`s, sync overdue reminders
- Reports hub: 8 `ReportQuery` classes feed on-screen table, CSV (UTF-8 BOM), and PDF; `reports.export` enforced server-side
- Migration `user_notifications`

### AT-10 KPI vs SQL (seeded sample leads)

| KPI | DashboardMetrics | SQL count | Match |
|-----|------------------|-----------|-------|
| new_leads (30d) | 3 | 3 | yes |
| follow_ups_due (today) | 1 | 1 | yes |
| follow_ups_overdue (< today) | 1 | 1 | yes |

### Report export check
- Lead report filter `source=web`: rows=2, CSV data rows=2, totals.expected_value_sum=3000, UTF-8 BOM present
- Product/tax `quote_tax_total` equals sum of revision CGST+SGST+IGST (test)

### Tests
`tests/Feature/Dashboard/DashboardAndReportsTest.php` ‚Äî **6 passed** (KPI=DB, executive scope, report totals=row sums, CSV count, tax summary, deep-link + export permission)

### Status
AT-10 Fully working.

### Next phase readiness
Ready for settings polish / AT-11 / packaging.

---

## Phase 10 - WhatsApp (Demo), Email, notifications, reminders

**Time (shell):** `2026-10-23 17:12:14 EEST`

**Git:** commit `bb9b16f` on `master`

### What was built
- `WhatsAppProvider` + `DemoWhatsAppDriver` (default) + `ConfiguredWhatsAppDriver` stub (**Not implemented** live client)
- Expanded `MessageService` / `MessageTemplateService` / `PhoneNumber` / consent on Customer
- `message_logs` (status: `demo_simulated` | `failed` only); UI badge **SIMULATED ‚Äì not delivered**
- Filament **Communications** settings: mode, non-secret display fields, Configured/Not configured, setup message, templates + preview, test-connection (Demo simulated); webhook **Not implemented**
- Email mode log/smtp display; SMTP secrets env-only; test email **Unavailable**
- Reminder settings + `reminders:dispatch` ‚Üí in-app `UserNotification`s; scheduled every 15 minutes
- Quotation share / open wa.me actions labelled honestly
- README production setup notes

### Tests
`MessagingAndRemindersTest` ‚Äî **8 passed** (demo_simulated, opt-out block, invalid phone, unknown placeholder, redaction, non-admin 403, reminders dispatch, test-connection simulated)

### Status
SET-05 Fully working; SET-06 Fully working; live delivery = Simulated; configured provider / webhook = Not implemented.

### Screenshots to capture (manual)
1. Communications ‚Üí WhatsApp: Demo mode + credential **Not configured** setup message (no secret fields)
2. Template preview with sample data
3. Test connection notification labelled SIMULATED
4. Quotation Share action result: SIMULATED ‚Äì not delivered
5. Message logs table badge SIMULATED ‚Äì not delivered
6. Customer consent Opted out ‚Üí send blocked error
7. Email tab: SMTP Not configured / test email Unavailable

### Next phase readiness
Ready for AT-11 settings polish / packaging / recording.

---

## Phase 11 - Settings completion, REST API, security hardening

**Time (shell):** `2026-10-23 17:17:26 EEST`

**Git:** commit `31ef6f2` on `master`

### What was built
- Settings tabs extended: Lists & workflows, Regional & finance, Documents, Integrations status cards, API & system (real Save maps ‚Äî no decorative dead controls)
- Lookups expanded: ticket_type/status, quotation_status, amc_status, notification_category, territory; Ticket/Quotation/Amc workflow **labels** from Lookups; inactive preserve
- Branches Filament resource; assignment rule setting
- REST API: `api.enabled`, `ApiToken` (hash + prefix; plaintext once), `GET /api/v1/leads` scoped + paginated; `docs/api.md`
- `docs/backup-plan.md`; SecurityHeaders; RateLimiter `api`/`login`
- Custom fields: **Not implemented** (labelled)

### Tests
`SettingsCompletionAndApiTest` ‚Äî **9 passed** (SET-03 source/status, SET-07, SET-08 redaction, SET-09 cache, API 401/503/200/403 + scoping)

### Secrets check
- `.gitignore` includes `.env`; no AWS/RSA private keys in app/config; `.env` not committed

### Settings coverage (PDF ¬ß8‚Äì10 ‚Üí status ‚Üí evidence)

| PDF item | Status | Evidence |
|----------|--------|----------|
| 8.A Branding | Fully working | ManageSettings Branding; SET-01 |
| 8.B Password / idle | Fully working / Partial | Change password + idle; admin invite Not implemented |
| 8.C RBAC / branches | Fully working | Gates + Branches + Warehouses + assignment rule |
| 8.D Dynamic lists / workflows | Fully working | Lookups + workflow labels; custom fields Not implemented |
| 9.E Calculators | Fully working | Existing Calculators tab |
| 9.F Inventory | Fully working | Existing Inventory tab |
| 9.G Documents/templates | Fully working / Partial | Documents tab toggles; install PDF Partial |
| 9.H‚ÄìI WhatsApp/Email | Simulated / Fully working | Phase 10 Communications |
| 10.J Regional/tax | Fully working | Regional & finance tab |
| 10.J API | Fully working | api.enabled + tokens + /api/v1/leads |
| 10.J Integration cards | Fully working | Integrations tab Demo/Not configured |
| 10.J Backup/export | Partial | Preferences + backup-plan.md; restore Unavailable |
| SET-01..SET-09 | Evidenced | Tests + prior phases; SET-01/09 updated Fully working |
| AT-11 | Fully working | This phase tests + coverage table |
| Custom fields (F) | Not implemented | Explicit label in Lists & workflows |

### Next phase readiness
Ready for packaging / recording / AT-12‚Äì13.

---

## Phase 12 - Demo data and end-to-end demonstration scenario

**Time (shell):** `2026-10-23 17:22:49 EEST`

**Git:** commit `632bb23` on `master`

### What was built
- `DemoScenarioSeeder` (DEMO ONLY, idempotent via `demo.scenario_version=12`) using Numbering/Quotation/Convert/Invoice/Ticket/AMC/Calculator services
- `php artisan demo:reset --force` (blocked in production; requires --force)
- `docs/demo-script.md` walkthrough click path for AT-01‚Ä¶AT-13
- Wired into `DatabaseSeeder` after catalogue

### Verification
- `migrate:fresh --seed` OK
- Full suite: **88 passed, 3 skipped** (ExampleTest fixed for `/` ‚Üí `/admin` redirect)
- All 6 active demo users present; inactive flagged
- Dashboard KPIs non-trivial (leads 20, accepted quotes 3, active projects 3, open tickets 4, revenue from payments)

### Seeded counts (post fresh seed)

| Table | Count |
|-------|------:|
| users | 7 |
| branches | 2 |
| leads | 20 |
| customers | 5 |
| visits | 6 |
| calculation_runs | 5 |
| quotations | 8 |
| orders / projects | 3 / 3 |
| project_materials | 7 (shortage lines present) |
| invoices / payments | 2 / 2 |
| tickets | 6 |
| amc_contracts | 2 |
| tasks | 4 |
| user_notifications | 3 |
| products | 25 |
| lookups | 131 |
| audit_logs | 210 |

### Demo users
See README / demo-script (Password1!): admin, manager, executive, surveyor, pm, ops (+ inactive).

### Status
AT-12 Fully working. Ready for FEATURE FREEZE.

---

## Validation V1 - Acceptance evidence

**Time (shell):** `2026-10-23 17:25:36 EEST`

**Git:** commit `3a113b6`

### Actions
- `migrate:fresh --seed` OK
- Full suite: **88 passed, 3 skipped, 0 failed**
- Created `docs/acceptance-evidence.md` (AT-01..13, SET-01..09)
- Honest Partial: AT-01, AT-11, SET-01 (await human UI confirmation)
- Not implemented: AT-13 (recording/Claude/clock)
- Clock still awaiting receipt timestamp ‚Äî remaining time unknown

### Defects fixed this validation
None (suite already green).

---

## Validation V2 - Security verification

**Time (shell):** `2026-10-23 17:34+ EEST`  
**Git:** commit `c6e7736`

### Actions
- Hardened API ability check (fail closed on empty abilities)
- Added `throttle:quote-verify` (30/min) and `throttle:document-download` (60/min)
- Removed `role_id`/`is_active` from User `$fillable`; DemoUserSeeder uses `forceFill`
- DocumentService `safeOriginalName` + traversal reject
- Added `tests/Feature/Security/SecurityVerificationTest.php` (13 tests) + `docs/security-verification.md`
- Secrets scan: no live keys; demo Password1! only
- Full suite after seed: **101 passed, 3 skipped, 0 failed**

### Findings fixed
SEC-V2-01..05 (see security-verification.md). Residuals: 2FA optional, LeadPolicy::export breadth, DocumentPolicy morph edge.
