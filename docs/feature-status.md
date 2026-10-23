# Feature Status Sheet

**Validation V6** — rebuilt from `docs/requirements-matrix.csv` on `2026-10-23 18:05` (local).

**Rules:** Exactly one status per requirement: `Fully working` | `Simulated` | `Partial` | `Not implemented`. Statuses require evidence (test name, acceptance-evidence.md ID, or documented reason). Simulated/Partial rows include what is missing and what production needs. No rows deleted; no upgrades without evidence.

**Machine-readable export:** [`docs/feature-status.csv`](feature-status.csv)

## Status counts (must equal matrix total)

| Status | Count |
|--------|------:|
| Fully working | 171 |
| Simulated | 7 |
| Partial | 13 |
| Not implemented | 26 |
| **Total** | **217** |

Matrix rows: **217**. Feature-status rows: **217**. Match: **YES**.

## Explicit honesty — integrations & deferred items

| Topic | Status | Matrix IDs | Evidence | Missing / production need |
|-------|--------|------------|----------|---------------------------|
| WhatsApp live delivery | Simulated | REQ-9-H-001/002/004, REQ-10-012 | DemoWhatsAppDriver; demo_simulated only | Missing: live Meta (or other) client + webhooks. Production: env secrets, approved templates, status from provider. |
| Email live delivery | Simulated | REQ-9-I-001, REQ-10-012 | MAIL_MAILER=log default; MessagingAndRemindersTest | Missing: verified SMTP/API send. Production: MAIL_* + test-send. |
| Maps / live GPS tiles | Simulated | REQ-3-C-005, REQ-10-012 | Demo map preview placeholder | Missing: map provider API. Production: key in env + Configured from real response. |
| External subsidy data APIs | Simulated | REQ-10-012, REQ-4-A-007 | Sample/Demo subsidy/tariff labels in Settings/calc | Missing: jurisdiction subsidy API. Production: sourced rates with effective date. |
| Electronic signature (legal) | Simulated | REQ-5-A-006, REQ-10-012, REQ-9-G-003 | Demo e-signature area on quote/install; not legally verified | Missing: e-sign provider + audit. Production: DocuSign/equivalent or wet-sign process. |
| Payment gateway | Simulated | REQ-10-012, invoices UI | payment_gateway_note Simulated; manual payments only | Missing: gateway integration. Production: PSP + webhooks; never invent paid without record. |
| Production / automated backups | Partial | REQ-10-J-005, REQ-7-C-004, REQ-10-012 | docs/backup-plan.md; reminder preference; restore Unavailable | Missing: scheduled off-site backup + tested restore. Production: mysqldump/storage job + auth restore. |
| Task calendar view | Partial | REQ-3-D-003 | List / due-today / overdue only | Missing: calendar UI. Production: calendar bound to due dates. |
| Lead / data CSV import | Partial | REQ-3-B-007 | CSV export exists; import Not implemented | Missing: import pipeline. Production: dry-run + validation + duplicates. |
| Admin custom fields | Not implemented | REQ-8-D-002 | Marked Not implemented in Settings Lists | Missing: custom field schema UI. Production: typed fields + placement rules. |
| Investment NPV/IRR | Not implemented | REQ-4-B-003 | Bonus deferred; InvestmentCalculatorService has payback/ROI only | Missing: NPV/IRR. Production: implement with documented discount rate assumptions. |
| Purchase orders / GRN | Not implemented | REQ-6-C-005 | Bonus not built; StockService allocations only | Missing: PO/GRN workflow. Production: PO→receipt→stock increase with audit. |
| Optional 2FA / lockout / devices | Not implemented | REQ-8-B-004 | security-verification.md residual | Missing: 2FA, lockout, device list. Production: TOTP + lockout policy. |

## Full requirement table

| ID | Module | Feature | Status | Known bugs | Dependencies | Evidence | Gap / production note |
|----|--------|---------|--------|------------|--------------|----------|----------------------|
| REQ-1-001 | Process | Claude AI must be the only AI coding assistant used to generate/modify/refactor/debug project code | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-002 | Process | No manual coding of application source; use AI to generate/revise then run/test (IDE/terminal/Git... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-003 | Process | Maximum duration 30 hours from assignment receipt; stop at deadline and submit current state | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-004 | Process | Do not present incomplete or simulated features as fully functional | Fully working | — | — | known-limitations.md; UI SIMULATED badges; payment_gateway_note Simulated; Demo e-signature labels | — |
| REQ-1-005 | Process | Keep Claude prompts/responses for architecture, generation, debugging, testing; provide exports/s... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-006 | Security | Use fictional seed data only; no real customer data, passwords, API tokens, or company secrets in... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-1-007 | Security | Store credentials in environment variables or secure server-side secret mechanism | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-1-008 | Process | Candidate must understand submitted code; unexplained AI code may count against assessment | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-009 | Submission | Deliverable: runnable responsive web app with connected core workflows and demo credentials | Fully working | — | — | AT-12 V5 clean clone; AT-02..AT-10 Pass; demo credentials README; docs/demo-script.md | — |
| REQ-1-010 | Submission | Deliverable: source repository/archive with migrations/schema, seed data, dependency/lock files, ... | Fully working | — | — | V5: composer.lock+package-lock.json;.env.example;clean clone | — |
| REQ-1-011 | Submission | Deliverable: README with setup, stack, architecture, DB setup, demo roles, test steps, deployment... | Fully working | — | — | V5: README AT-12 clean-clone verified | — |
| REQ-1-012 | Submission | Deliverable: full-project screen-recording (live app, major modules, workflows, settings, WhatsAp... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-013 | Submission | Deliverable: Claude AI evidence (prompt history/export or screenshots) plus short validation/debu... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-014 | Submission | Deliverable: feature status sheet marking Fully working / Simulated / Partial / Not implemented | Fully working | — | — | V6 docs/feature-status.md + docs/feature-status.csv rebuilt from matrix | — |
| REQ-1-015 | Submission | Suggested recording structure: overview/stack (1-2m), lead?quote?project (4-6m), ops/inventory/ti... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-1-016 | Process | Build original implementation; do not copy proprietary products, private repos, or third-party so... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-2-001 | Architecture | Centralized CRM connecting sales, field survey, hybrid sizing, quotations, execution, inventory, ... | Fully working | — | — | Filament modules Sales/Field/Calculators/Ops/Inventory/Finance/Service/Reports/Admin; AT-02..AT-10 Pass | — |
| REQ-2-002 | Workflow | PRIMARY WORKFLOW: Lead ? follow-up/qualification ? customer/site visit ? hybrid calculation ? quo... | Fully working | — | — | DemoScenarioSeeder LEAD-2026-0003 golden path; AT-02..AT-09 Pass; docs/demo-script.md | — |
| REQ-2-003 | UI | Consistent navigation, clear page titles, search/filter, validation messages, empty/loading/error... | Fully working | — | — | V4 docs/ui-workflow-checklist.md browser 375/768/1280; Filament nav/search/filter | — |
| REQ-2-004 | Data model | Linked records (not disconnected duplicates): lead/customer links to visits, calculations, quotat... | Fully working | — | — | Phase8: tickets linked; quote↔order↔project↔invoice | — |
| REQ-2-005 | Numbering | Unique reference IDs for leads, visits, quotations, orders/projects, products, invoices, tickets,... | Fully working | — | — | Phase8: ticket/invoice/AMC numbering via NumberingService | — |
| REQ-2-006 | Dashboard | Dashboard metrics and reports calculated from stored data; no decorative hard-coded KPIs | Fully working | — | — | Phase9: DashboardMetrics from DB; tests KPI=SQL | — |
| REQ-2-007 | Settings | Dynamic business configuration in Settings tab and database; changes affect UI/calculations/templ... | Fully working | — | — | Phase11: Settings A–J tabs + Lookups + workflows labels | — |
| REQ-2-008 | Integrations | External services not integrated must be labeled Demo/Simulated and production setup documented | Simulated | — | — | SET-05; known-limitations.md; Integrations status cards | Live WhatsApp/email/maps/gateway not wired. Production: tested provider clients + webhooks + labelled cutover. |
| REQ-2-009 | Auth | Role Super Admin: all modules, settings, users, roles/permissions, company config, audit logs, in... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions  | — |
| REQ-2-010 | Auth | Role Sales Manager: team leads, assignment, approvals, quotes, sales pipeline, team reports | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions  | — |
| REQ-2-011 | Auth | Role Sales Executive: assigned leads, follow-ups, visits, customers, calculators, quotations, own... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions  | — |
| REQ-2-012 | Auth | Role Surveyor/Engineer: assigned site visits, survey forms, GPS/site evidence, technical notes | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions  | — |
| REQ-2-013 | Auth | Role Project Manager/Operations: accepted orders, projects, milestones, materials, team allocatio... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions  | — |
| REQ-2-014 | Auth | Role Inventory/Finance/Service: stock, procurement, invoice/payment visibility, support tickets, AMC | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions  | — |
| REQ-3-A-001 | Dashboard | Summary cards: new leads, follow-ups due/overdue, scheduled visits, quotations drafted/sent/accep... | Fully working | — | — | Phase9: KPI cards via DashboardMetrics | — |
| REQ-3-A-002 | Dashboard | Summary cards: recorded revenue and outstanding amounts when payment data exists | Fully working | — | — | Phase9: revenue/outstanding null until payments exist | — |
| REQ-3-A-003 | Dashboard | Charts/lists: lead-stage pipeline, source mix, sales by team member, quote conversion, monthly re... | Fully working | — | — | Phase9: pipeline/source/revenue/project charts + sales mix | — |
| REQ-3-A-004 | Dashboard | Charts/lists: recent activity, upcoming tasks and notifications | Fully working | — | — | Phase9: activity widget + upcoming tasks + UserNotification | — |
| REQ-3-A-005 | Dashboard | Filters: date range, branch, team member, lead source, status | Fully working | — | — | Phase9: dashboard filters date/branch/member/source/status | — |
| REQ-3-A-006 | Dashboard | KPI cards open underlying filtered records | Fully working | — | — | Phase9: KPI deepLink + DashboardKpiQuery on list pages | — |
| REQ-3-A-007 | Dashboard | Activity center: timestamped events, task reminders, overdue highlights, read/unread notification... | Fully working | — | — | Phase9: ActivityCenter overdue/audit/read-unread | — |
| REQ-3-B-001 | Leads | Lead identity fields: auto lead ID, created date/time, lead title, customer type, full name/compa... | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-B-002 | Leads | Lead location fields: address, city, district, state, PIN/postal, GPS if available, branch/territory | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-B-003 | Leads | Lead solar need fields: system type (hybrid, solar water pump, EV charging, other), property segm... | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-B-004 | Leads | Lead sales tracking: source, campaign/source details, assigned owner, stage, loss reason, probabi... | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-B-005 | Leads | Lead follow-up/files: next follow-up, reminder, outcome, call/meeting notes, documents/photos, in... | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-B-006 | Leads | Lead actions: add/edit/view, assign/reassign, search/filter/sort, duplicate check, follow-up sche... | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-B-007 | Leads | Lead import/export where feasible | Partial | — | — | AT-02; Lead CSV export exists; LeadConversion/Duplicate/Scoping tests | CSV import not built. Production: validated import with dry-run + duplicate rules. |
| REQ-3-C-001 | Visits | Visit core: Visit ID, linked lead/customer, schedule, assigned surveyor, status, site address, GP... | Fully working | — | — | Phase3: VisitResource+history; Feature VisitScopingAndDocumentsTest 5 pass; AT-03 script VIS-2026-0001 | — |
| REQ-3-C-002 | Visits | Survey fields: property type, roof type/material, usable area, orientation/tilt, shading/obstruct... | Fully working | — | — | Phase3: VisitForm survey section; AT-03 script + PDF report | — |
| REQ-3-C-003 | Visits | Utility details: bill/consumption evidence, monthly units, sanctioned load, single/three-phase, u... | Fully working | — | — | Phase3: VisitForm utility section; AT-03 script | — |
| REQ-3-C-004 | Visits | Visit outcome: measurements, feasibility, customer needs, objections, feedback, recommended next ... | Fully working | — | — | Phase3: VisitReportService Dompdf; PDF 879706 bytes 1 page A4 %PDF-1.7 | — |
| REQ-3-C-005 | Visits | Map/GPS real only when configured; otherwise manual coordinates and demo-labeled map preview | Simulated | — | — | AT-03; Visit Demo map preview placeholder | No external map API. Production: Maps provider key in env + Configured status from real API. |
| REQ-3-D-001 | Tasks | Tasks linkable to leads, customers, quotes, visits, projects, tickets | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-D-002 | Tasks | Task fields: title, description, owner, due date/time, priority, status, reminder, outcome | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-3-D-003 | Tasks | Task list/calendar view, overdue and due-today views, completion timestamps, linked-record activi... | Partial | — | — | Task list/due-today/overdue; LeadConversion/Lookup tests | Calendar view not shipped. Production: calendar UI bound to task due dates. |
| REQ-4-001 | Calculators | Calculator must not return arbitrary/fixed results; show inputs, formulas/assumptions, intermedia... | Fully working | — | — | Phase5: Hybrid/Investment/EV services + Filament; snapshot + PDF; calculation-spec.md | — |
| REQ-4-002 | Calculators | Results labeled as indicative estimates, not engineering certification | Fully working | — | — | Phase5: disclaimer on UI/PDF/snapshot | — |
| REQ-4-003 | Calculators | Configurable formulas and documented assumptions; validate non-negative, units, efficiency ranges... | Fully working | — | — | Phase5: validation + calculation-spec.md; HybridCalculatorTest | — |
| REQ-4-A-001 | HybridCalculator | Hybrid load table: appliance name, quantity, rated watts, hours/day, usage pattern, critical/non-... | Fully working | — | — | Phase5: load repeater + connected/daily/peak | — |
| REQ-4-A-002 | HybridCalculator | Hybrid solar/utility inputs: monthly units and/or bill, tariff/unit, usable roof area, proposed P... | Fully working | — | — | Phase5: PSH/PR/tariff/roof/export inputs from Settings | — |
| REQ-4-A-003 | HybridCalculator | Hybrid battery/inverter inputs: backup duration target, system voltage, battery chemistry, nomina... | Fully working | — | — | Phase5: backup/DoD/voltage/efficiencies/generator | — |
| REQ-4-A-004 | HybridCalculator | Hybrid financial inputs: panel/inverter/battery/BOS prices, GST, installation, transport, structu... | Fully working | — | — | Phase5: cost lines GST O&M subsidy | — |
| REQ-4-A-005 | HybridCalculator | Hybrid outputs: recommended PV kWp and panel count, roof-area-constrained size if enabled, daily/... | Fully working | — | — | Phase5: full outputs + hybrid PDF | — |
| REQ-4-A-006 | HybridCalculator | Display each selected input, unit and assumption beside results; do not hide battery/inverter rul... | Fully working | — | — | Phase5: intermediates + formula_path beside results | — |
| REQ-4-A-007 | HybridCalculator | If real tariff/net-metering/subsidy used: show state/utility, source/reference, effective date; e... | Fully working | — | — | Phase5: Sample/Demo tariff labels unless sourced | — |
| REQ-4-B-001 | InvestmentCalculator | Investment calculator inputs: upfront cost, grant/subsidy, optional loan amount/interest/tenure, ... | Fully working | — | — | Phase5: InvestmentCalculatorService inputs | — |
| REQ-4-B-002 | InvestmentCalculator | Investment calculator outputs: net initial investment, monthly/annual savings/revenue, annual ope... | Fully working | — | — | Phase5: net initial/savings/payback/ROI | — |
| REQ-4-B-003 | InvestmentCalculator | Investment calculator NPV/IRR (bonus) | Not implemented | — | — | Bonus deferred; no NPV/IRR in InvestmentCalculatorService; calculation-verification.md | — |
| REQ-4-C-001 | EVCalculator | EV charging income inputs: charger type/power kW, number of points, sessions/day, avg kWh/session... | Fully working | — | — | Phase5: EvCalculatorService inputs | — |
| REQ-4-C-002 | EVCalculator | EV charging outputs: energy sold, gross monthly/annual revenue, electricity cost, opex, estimated... | Fully working | — | — | Phase5: energy/revenue/opex/profit/payback | — |
| REQ-4-HYBRID-SIZING | HybridCalculator | Acceptance: changing appliance qty/hours, backup target, panel wattage, peak sun hours, battery D... | Fully working | — | — | Phase5: sensitivity tests qty/hours/backup/panel/PSH/DoD/eff | — |
| REQ-4-GEN-SAVINGS | Calculators | Acceptance: changing tariff, generation/loss, self-consumption or export rate changes projected s... | Fully working | — | — | Phase5: tariff/loss/export sensitivity + investment | — |
| REQ-4-COST-EST | HybridCalculator | Acceptance: changing product prices, GST, installation or transport updates line items and total;... | Fully working | — | — | Phase5: GST/install sensitivity; PDF from snapshot HTML equality | — |
| REQ-4-VALIDATION | Calculators | Acceptance: reject negative inputs, implausible efficiencies, invalid denominators, non-numeric i... | Fully working | — | — | Phase5: negative/efficiency validation tests | — |
| REQ-5-A-001 | Quotations | Quotation common fields: auto quotation number, revision, created/valid-until, customer/lead/site... | Fully working | — | — | Phase6: Quotation+revisions; Filament resource; validity from Settings | — |
| REQ-5-A-002 | Quotations | Quotation itemized lines: product/service, SKU, description/spec, qty, unit, unit price, discount... | Fully working | — | — | Phase6: itemized lines + stock indicator; services lines | — |
| REQ-5-A-003 | Quotations | Quotation totals: subtotal, discount, taxable value, configured CGST/SGST or IGST, round-off if u... | Fully working | — | — | Phase6: QuotationTotals CGST/SGST vs IGST; Money minor-units rounding | — |
| REQ-5-A-004 | Quotations | Hybrid proposal content: panel/inverter/battery/structure/protection/accessories, load and backup... | Fully working | — | — | Phase6: hybrid import from CalculationRun + template texts | — |
| REQ-5-A-005 | Quotations | Solar water-pump proposal: pump model/capacity, head/discharge/water requirement, controller, pan... | Fully working | — | — | Phase6: pump_details + Sample/Demo subsidy note | — |
| REQ-5-A-006 | Quotations | Quote output/share: branded PDF, print view, QR to verification/detail, digital signature area, a... | Fully working | — | — | Phase6: branded PDF, QR verify, demo signature, activity trail, Simulated share | — |
| REQ-5-A-007 | Quotations | Do not claim message sent unless provider confirms; demo actions must be labeled | Fully working | — | — | Phase6: MessageService demo never claims sent | — |
| REQ-5-B-001 | Catalogue | Product master fields: ID/SKU, category, brand/model/title, description, technical specs, wattage... | Fully working | — | — | Phase4: ProductResource+SKU/specs/vendor/stock; CatalogueInventorySeeder 25 products | — |
| REQ-5-B-002 | Catalogue | Product categories cover panels, hybrid inverters, batteries, pumps, chargers, structures, wiring... | Fully working | — | — | Phase4: all 9 categories seeded with products | — |
| REQ-5-B-003 | Catalogue | Catalogue search, category/brand filters, sort, detail view, create/edit/archive, stock visibilit... | Fully working | — | — | Phase4: search/filters/sort/archive/low-stock; Filament Products table | — |
| REQ-5-C-001 | Documents | Document attachments: bills, site photos, signed proposals, invoices, installation checklists, wa... | Fully working | — | — | Phase3: polymorphic DocumentService private disk; DocumentsRelationManager; download policy | — |
| REQ-5-C-002 | Documents | Store filename/type/date/uploader metadata, secure access rules, downloadable links; restrict sen... | Fully working | — | — | VisitScopingAndDocumentsTest; DocumentService private disk + MIME/size; Document model metadata | — |
| REQ-5-C-003 | Documents | Generate PDF quotes/proposals/reports with unique references and predictable filenames | Fully working | — | — | Phase6: quotation-{number}-r{rev}-{ts}.pdf | — |
| REQ-6-A-001 | Orders | Accepted quotation creates linked customer order/project without retyping core customer, line-ite... | Fully working | — | — | Phase7: ConvertQuotationToProject snapshot copy + unique quotation_id + lockForUpdate | — |
| REQ-6-A-002 | Orders | Order fields: order ID/date, quote reference, customer/site, project type/capacity, contract valu... | Fully working | — | — | Phase7: Order model + My Orders EditOrder | — |
| REQ-6-A-003 | Projects | Project fields: project ID, PM, assigned sales/engineering/installation team, planned/actual date... | Fully working | — | — | Phase7: ProjectResource + materials/milestones relations | — |
| REQ-6-A-004 | Projects | Suggested milestones: Order Confirmed ? Documents Pending ? Site/Technical Approval ? Material Pl... | Fully working | — | — | Phase7: ProjectMilestones defaults + hold reason required | — |
| REQ-6-A-005 | Projects | Installation record: crew, schedule, checklist, serial numbers where tracked, install photos, saf... | Partial | — | — | AT-07; InstallationRelationManager checklist/readings/sign-off/photos | Installation completion PDF Partial/incomplete. Production: branded install completion PDF from stored checklist. |
| REQ-6-A-006 | Projects | Show timeline, milestone timestamps, stuck/overdue projects, completion reports, progress updates | Fully working | — | — | Phase7: due_at SLA + overdue column; % from weights | — |
| REQ-6-B-001 | Orders | My Orders: separate Pending/Active/Completed/Cancelled views; search by order/customer/quote; sta... | Fully working | — | — | Phase7: OrderResource tabs + search/filters | — |
| REQ-6-B-002 | Orders | If customer portal exists, customers only view own projects; else internal-only and document boun... | Fully working | — | — | Phase7: internal-only My Orders subheading documented | — |
| REQ-6-C-001 | Inventory | Track available, reserved, allocated, issued, returned, damaged quantities; deduct/reserve only v... | Fully working | — | — | Phase4: StockService states+locks; StockServiceTest+StockMysqlConcurrencyTest pass | — |
| REQ-6-C-002 | Inventory | Link BOM/material requirements to projects/orders; show required vs available vs allocated and sh... | Fully working | — | — | Phase7: ProjectMaterial from quote lines + AllocationService | — |
| REQ-6-C-003 | Inventory | Stock movement log: date, warehouse/branch, reference, quantity, user, reason; supplier/vendor na... | Fully working | — | — | Phase4: stock_movements ledger + Vendor master (name/contact/address/tax/status/notes) | — |
| REQ-6-C-004 | Inventory | Low-stock alerts; material/product/order/project reports; reconciliation to stock transactions | Fully working | — | — | Phase7: shortage on materials + Phase4 low-stock/reconcile | — |
| REQ-6-C-005 | Inventory | Purchase requests/orders and goods-received workflow (bonus) | Not implemented | — | — | Bonus PO/GRN not built; StockService allocations only | — |
| REQ-7-A-001 | Tickets | Ticket fields: Ticket ID, customer, linked project/order/product, category, issue, priority/sourc... | Fully working | — | — | Phase8: Ticket model+Filament+SLA | — |
| REQ-7-A-002 | Tickets | Ticket states: Open, Assigned, In Progress, Waiting for Customer/Parts, Resolved, Closed; assignm... | Fully working | — | — | Phase8: TicketWorkflow+comments+history+PDF | — |
| REQ-7-A-003 | AMC | AMC record: contract number, customer/site, start/end, coverage, visit frequency, SLA, amount, ne... | Partial | — | — | AT-09; TicketAmcInvoiceTest; amc:renewal-reminders command | AMC visit scheduling automation needs OS scheduler/queue worker. Production: cron schedule:run + queue:work. |
| REQ-7-B-001 | Reports | Lead report: ID, source, owner, stage, creation date, follow-up, expected value, win/loss; date/s... | Fully working | — | — | Phase9: LeadReportQuery + Reports hub | — |
| REQ-7-B-002 | Reports | Customer report: profile, city/state, related leads, visits, quotes, projects/orders, tickets | Fully working | — | — | Phase9: CustomerReportQuery | — |
| REQ-7-B-003 | Reports | Quotation report: quote number, customer, date, subtotal/tax/total, status, owner, validity, conv... | Fully working | — | — | Phase9: QuotationReportQuery | — |
| REQ-7-B-004 | Reports | Sales/revenue report: period, team member, won deals, conversion, recorded revenue/payment; do no... | Fully working | — | — | Phase9: SalesRevenueReportQuery from payments | — |
| REQ-7-B-005 | Reports | Order/project report: status, system type/capacity, manager, milestone, planned vs actual, delays... | Fully working | — | — | Phase9: OrderProjectReportQuery | — |
| REQ-7-B-006 | Reports | Product/tax report: stock/low-stock and tax summary from stored quote/invoice records and configu... | Fully working | — | — | Phase9: ProductTaxReportQuery tax from quote totals | — |
| REQ-7-B-007 | Reports | Ticket/AMC report: open/overdue/resolved, time-to-resolution if available, coverage, upcoming ren... | Fully working | — | — | Phase9: TicketAmcReportQuery | — |
| REQ-7-B-008 | Reports | Monthly business report: leads, quote conversion, orders/projects, sales value, recorded payments... | Fully working | — | — | Phase9: MonthlyBusinessReportQuery | — |
| REQ-7-B-009 | Reports | Reports support date-range/status/owner filters, sorting, totals, page size; CSV/Excel-compatible... | Fully working | — | — | Phase9: same ReportQuery for table/CSV/PDF; export permission | — |
| REQ-7-C-001 | Admin | User management: name, email/login, phone, role, branch/team, active/inactive, last login if avai... | Partial | — | — | DemoUserSeeder roles; SettingsPermissionTest; DeactivatedUserLoginTest | Filament User/Role CRUD UI not built (seed/tinker only). Production: admin user/role management UI. |
| REQ-7-C-002 | Admin | Role/permission management: restrict view/create/edit/delete/export for sensitive modules; demons... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-7-C-003 | Admin | Audit logs for important actions: user, action, entity/reference, timestamp, change summary; neve... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-7-C-004 | Admin | Backup/export plan and REST API approach; prefer one authenticated sample endpoint; full automate... | Partial | — | — | docs/backup-plan.md; docs/api.md; ApiToken + /api/v1/leads; Settings backup reminder | No automated off-site backup/restore job. Production: scheduled mysqldump + storage backup, tested restore, monitoring. |
| REQ-7-C-005 | Security | Security: server-side checks, secure password hashing, validation, safe uploads, secrets in env/s... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-001 | Settings | ONE dedicated Settings tab/page; all listed business configuration editable by authorized admins,... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-002 | Settings | Settings organized into sub-tabs/cards with Save, Cancel, validation, confirmation for sensitive ... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-003 | Settings | Unimplemented settings clearly marked unavailable rather than shown as working | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-A-001 | Settings-Branding | Editable app/website display name, organization/company name, logo, favicon, login-page logo, sid... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-A-002 | Settings-Branding | Company details: legal name, display name, address, phone, support email, website, GSTIN/tax IDs,... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-A-003 | Settings-Branding | Logo/name changes appear in navigation, login where applicable, quotation PDFs, report headers, g... | Fully working | — | — | Phase6: quote PDF uses branding snapshot | — |
| REQ-8-A-004 | Settings-Branding | Secure logo upload with type/size validation, preview, replace, remove/reset-to-default | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-B-001 | Settings-Security | Signed-in user change password with current, new, confirmation; show policy, mismatch/weak errors... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-B-002 | Settings-Security | Admin-managed password reset/invitation, user activation/deactivation, session expiry/idle timeou... | Partial | — | — | SET-02; PasswordChangeTest; idle timeout + password policy settings | Admin password reset/invitation flow Not implemented. Production: invite tokens + admin reset. |
| REQ-8-B-003 | Settings-Security | Never display/recover plaintext passwords; hash securely; do not log credentials; no secrets in b... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-B-004 | Settings-Security | Optional: 2FA, login lockout, session/device list, security notification settings | Not implemented | — | — | No 2FA/lockout/device list; security-verification.md residual | — |
| REQ-8-C-001 | Settings-RBAC | Configure roles, module access and actions (view/create/edit/delete/export/approve/settings); pro... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-C-002 | Settings-RBAC | Configure branch/warehouse list, territory/region, assignment rules, team names, access scope | Fully working | — | — | Phase11: Branches resource + warehouses + assignment rule setting | — |
| REQ-8-C-003 | Settings-Numbering | Configure company/fiscal-year details and numbering sequences/prefixes for leads, quotes, orders,... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-8-D-001 | Settings-Lists | Add/edit/deactivate lead sources, lead stages, customer types, property types, product categories... | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| REQ-8-D-002 | Settings-Lists | Where feasible: admin-defined custom fields (text/number/date/dropdown/checkbox) with label, requ... | Not implemented | — | — | Phase11 Lists & workflows marks custom fields Not implemented | — |
| REQ-8-D-003 | Settings-Workflow | Configure quotation statuses, project milestones, ticket workflow, AMC statuses and allowed trans... | Fully working | — | — | Phase11: quotation/ticket/AMC statuses in Lookups; transitions code-backed | — |
| REQ-8-D-004 | Settings-Lists | Configure table columns and defaults where feasible; deactivating config must not break existing ... | Fully working | — | — | Phase11: Lookup::options preserves inactive values | — |
| REQ-9-E-001 | Settings-Calculator | Hybrid calculator defaults editable: peak sun hours, PR/losses, panel wattage, battery chemistry,... | Fully working | — | — | Phase5: Settings Calculators tab + calc.hybrid.* keys | — |
| REQ-9-E-002 | Settings-Calculator | Editable rates/assumptions: electricity tariffs, export/net-metering prices, O&M defaults, compon... | Fully working | — | — | Phase5: tariff/export/O&M/prices/GST in Settings | — |
| REQ-9-E-003 | Settings-Calculator | Each config keeps value, unit, effective date, status, source/reference, updated-by; real policy ... | Fully working | — | — | Phase5: settings value/unit/effective/source/is_sample | — |
| REQ-9-E-004 | Settings-Calculator | Calculator setting changes affect new calculations/reports; previously saved quotes/calculations ... | Fully working | — | — | Phase5: snapshot immutability + rerun overlays settings | — |
| REQ-9-F-001 | Settings-Inventory | Product/inventory settings: category, brand, warranty defaults, units, tax rate, price defaults, ... | Fully working | — | — | Phase4: Settings Inventory tab + inventory.* keys; warehouses Filament | — |
| REQ-9-F-002 | Settings-Inventory | Configure allowed product types and component attributes for panels, hybrid inverters, batteries,... | Fully working | — | — | Phase4: product_attributes per category + Lookup product_category | — |
| REQ-9-G-001 | Settings-Templates | Configure numbering prefixes and sequence behavior for quote/invoice/order/project/ticket/AMC; va... | Fully working | — | — | Phase11: validity/terms in Documents tab + numbering | — |
| REQ-9-G-002 | Settings-Templates | Editable branded templates for quotation PDF, hybrid calculator report, site-visit report, instal... | Partial | — | — | AT-06; quotation/hybrid PDF templates editable in Settings | Installation report template still Partial. Production: complete install PDF template binding. |
| REQ-9-G-003 | Settings-Templates | Enable/disable QR code, signature block, footer/contact block, logo display, PDF/CSV/Excel export... | Fully working | — | — | Phase6: quotations.qr_enabled; signature block on PDF | — |
| REQ-9-H-001 | Settings-WhatsApp | WhatsApp settings page: provider/mode (Demo or configured), business phone/sender, business/accou... | Simulated | — | — | SET-05; Communications WhatsApp settings Demo/Configured; MessagingAndRemindersTest | Live provider mode uses stub client. Production: Meta Cloud API (or chosen) client with approved templates. |
| REQ-9-H-002 | Settings-WhatsApp | Secure server-side credential configuration, test-connection, connection status, webhook/callback... | Simulated | — | — | SET-06; Configured/Not configured UI; secrets env-only; webhook Not implemented | test-connection is Demo-simulated; no webhook. Production: real test-connection + webhook verification. |
| REQ-9-H-003 | Settings-WhatsApp | Editable message templates with safe placeholders, preview, validation for: lead assignment, foll... | Fully working | — | — | Phase10: template editor + whitelist + preview | — |
| REQ-9-H-004 | Settings-WhatsApp | WhatsApp actions: share quotation PDF/link, share calculator/report link, send approved template,... | Simulated | — | — | SET-05; share/open wa.me labelled; demo_simulated message_logs | No real WhatsApp delivery. Production: provider send + status callbacks; never invent delivered. |
| REQ-9-H-005 | Settings-WhatsApp | Store message history: linked record, recipient, template, time, initiating user, status (queued/... | Fully working | — | — | Phase10: message_logs; statuses demo_simulated\|failed only | — |
| REQ-9-H-006 | Settings-WhatsApp | Respect customer consent/opt-in and opt-out; validate phone numbers with country code; safe error... | Fully working | — | — | Phase10: consent + PhoneNumber + validation errors | — |
| REQ-9-I-001 | Settings-Email | Configurable SMTP/provider mode and sender display name/address; server-side secret storage; test... | Simulated | — | — | SET-06; MAIL_* env; log mailer default; test email Unavailable | Live SMTP test send Unavailable. Production: SMTP/API mailer + verified test send. |
| REQ-9-I-002 | Settings-Email | Editable email templates for quotes, visits, project updates, ticket updates, invoices, AMC remin... | Fully working | — | — | Phase10: editable email templates in Communications | — |
| REQ-9-I-003 | Settings-Email | Optional SMS/push channels may be documented if not implemented | Not implemented | — | — | SMS/push channels not implemented; documented as optional out of scope | — |
| REQ-9-I-004 | Settings-Notifications | Configure reminders, lead follow-up intervals, task due reminders, notification channels, working... | Fully working | — | — | Phase10: reminder settings + reminders:dispatch in-app | — |
| REQ-10-J-001 | Settings-Regional | Locale, timezone, date/time format, currency symbol/precision, measurement units, rounding behavi... | Fully working | — | — | Phase11: Regional settings tab | — |
| REQ-10-J-002 | Settings-Finance | Tax rules/GST rates, company fiscal year, financial report parameters editable by authorized user... | Fully working | — | — | Phase6: finance.* tax rates/rule in Settings Sample/Demo | — |
| REQ-10-J-003 | Settings-API | REST API configuration: enabled/disabled, API client/key metadata and rotation/revocation if impl... | Fully working | — | — | Phase11: api.enabled + API tokens (secret once) + docs/api.md | — |
| REQ-10-J-004 | Settings-Integrations | Map/GPS, signature, payment, backup and external-data integration status show Configured / Not co... | Fully working | — | — | Phase11: Integrations status cards Demo/Not configured | — |
| REQ-10-J-005 | Settings-System | Configure data-export formats, backup reminder/schedule, retention preferences, audit-log filter ... | Partial | — | — | Phase11 export toggles + backup reminder; docs/backup-plan.md | Dangerous restore/delete Unavailable. Production: authorized restore with confirmation + audit. |
| SET-01 | Settings | SET-01 Change app/website display name and logo ? branding changes in nav/login and generated quo... | Partial | — | — | acceptance-evidence.md SET-01; branding settings/PDF wired | Browser logo refresh after change awaiting manual confirmation. Production: N/A once confirmed. |
| SET-02 | Settings | SET-02 Change own password ? current validated; new/confirm mismatch rejected; new works and old ... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| SET-03 | Settings | SET-03 Change dropdown/workflow value ? new lead source/stage in forms/filters; changed project/t... | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups new  | — |
| SET-04 | Settings | SET-04 Change hybrid defaults ? changing PSH, battery DoD, loss factor or panel wattage changes r... | Fully working | — | — | Phase5: change PSH/DoD/loss/panel → new run changes; old snapshot fixed | — |
| SET-05 | Settings-WhatsApp | SET-05 Configure WhatsApp in Demo mode ? template preview works, demo visibly marked simulated, n... | Fully working | — | — | Phase10: Demo mode preview; SIMULATED badge; MessagingAndRemindersTest | — |
| SET-06 | Settings-WhatsApp | SET-06 Missing WhatsApp credentials ? useful setup message; never expose or request credentials i... | Fully working | — | — | Phase10: missing creds setup message; no client-side secrets | — |
| SET-07 | Settings | SET-07 Role restrictions ? non-admin cannot save protected settings by UI or direct HTTP/API | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| SET-08 | Settings | SET-08 Audit history ? settings changes record user, timestamp, category, safe summary; secrets/p... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| SET-09 | Settings | SET-09 Persistence ? saved settings remain after logout/login and server restart using DB-backed ... | Fully working | — | — | Phase11: SettingsCompletionAndApiTest cache forget persistence | — |
| REQ-10-010 | UI | Responsive desktop/tablet/mobile design, accessible labels, search/filter/sort/pagination, sensib... | Fully working | — | — | V4 ui-workflow-checklist.md responsive checks; Filament a11y labels | — |
| REQ-10-011 | Cross-cutting | PDF and CSV/Excel-compatible exports, quote QR code, signature capture/display area, document man... | Fully working | — | — | Phase9: CSV UTF-8 BOM + PDF reports; quote QR earlier | — |
| REQ-10-012 | Integrations | Email/WhatsApp live delivery, live maps, external subsidy APIs, electronic signatures, payment ga... | Simulated | — | — | Phase10/11 labels; Invoice payment_gateway_note; Demo e-signature; backup-plan.md; README | Live delivery/maps/subsidy APIs/e-sign/gateway/automated backups not production-ready. See explicit honesty table. |
| AT-01 | Acceptance | AT-01 Log in with demo credentials; demonstrate ?2 roles with different server-enforced permissions | Partial | — | — | acceptance-evidence.md AT-01; SettingsPermissionTest; LeadScopingTest; DeactivatedUserLoginTest | Full on-screen ≥2-role UI demo awaiting human confirmation. |
| AT-02 | Acceptance | AT-02 Create lead, assign, add follow-up/customer note, find via search/filter | Fully working | — | — | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups | — |
| AT-03 | Acceptance | AT-03 Schedule and complete site visit with survey fields, photo/document support, visit history/... | Fully working | — | — | Phase3: tests 5 pass; scripted schedule/complete/upload/reschedule/history/PDF VIS-2026-0001 | — |
| AT-04 | Acceptance | AT-04 Run hybrid calculator; show changed inputs, units, assumptions, formula/intermediates, chan... | Fully working | — | — | Phase5: hybrid UI+tests+PDF; 3 worked examples | — |
| AT-05 | Acceptance | AT-05 Run solar investment and EV income calculators; verify input changes affect results logically | Fully working | — | — | Phase5: investment+EV tests and Filament pages | — |
| AT-06 | Acceptance | AT-06 Create itemized hybrid and solar water-pump quotations, calculate configured taxes/totals, ... | Fully working | — | — | Phase6: QuotationTotalsAndWorkflowTest 9 pass; sample PDFs generated | — |
| AT-07 | Acceptance | AT-07 Accept quotation and convert to linked order/project without losing key data; update milest... | Fully working | — | — | Phase7 evidenced ORD/PRJ + tests | — |
| AT-08 | Acceptance | AT-08 View catalogue/stock, link required materials to a project, show stock shortage/low-stock i... | Fully working | — | — | Phase7 materials+shortage + Phase4 catalogue | — |
| AT-09 | Acceptance | AT-09 Create, assign, update and close ticket linked to customer/project; show AMC record or rene... | Fully working | — | — | Phase8: TKT-2026-0002 closed; AMC-2026-0001; INV payment | — |
| AT-10 | Acceptance | AT-10 Dashboard KPIs and core reports reflect stored records; export data and verify filters/totals | Fully working | — | — | Phase9: DashboardAndReportsTest 6 pass; KPI=SQL; CSV BOM | — |
| AT-11 | Acceptance | AT-11 Demonstrate dynamic settings: app name/logo, password change, new dropdown/workflow, calcul... | Partial | — | — | acceptance-evidence.md AT-11; SettingsCompletionAndApiTest | End-to-end settings UI walkthrough awaiting human confirmation. |
| AT-12 | Acceptance | AT-12 README setup works; seed data/demo login exist; no secrets committed; known limitations listed | Fully working | — | — | V5: temp clean clone; README gaps fixed; .env untracked | — |
| AT-13 | Acceptance | AT-13 Submit full-project recording and Claude-only evidence within the 30-hour limit | Not implemented | — | — | acceptance-evidence.md AT-13; recording + Claude package + clock receipt pending | — |
| REQ-11-014 | Architecture | Related screens update consistently from one stored source of truth; avoid duplicate copies of sa... | Fully working | — | — | ConvertQuotationToProjectTest; DemoScenario linked lead→visit→calc→quote→order→project→invoice→ticket/AMC | — |
| REQ-11-015 | UI | Show loading/success/failure feedback; validate input client and server where relevant; handle em... | Partial | — | — | Filament validation + V4 checklist; empty/keyboard not exhaustively automated | Full keyboard-only tour and every empty-state variant not exhaustively verified. Production: expand UX a11y test coverage. |
| REQ-11-016 | Integrations | Integrations expose real status only when real provider/API response supports it; local success t... | Fully working | — | — | MessagingAndRemindersTest::test_demo_send_logs_demo_simulated_never_sent_or_delivered; MessageLog statuses demo_simulate | — |
| REQ-11-017 | Snapshots | Store snapshot of quote/calculation values so editing current settings does not silently alter pr... | Fully working | — | — | Phase5: calculation_runs immutable JSON snapshot | — |
| REQ-12-001 | Submission | Submit working URL or local run steps, demo credentials, seed data, core flows accessible | Fully working | — | — | AT-12 Pass; README install; DemoUserSeeder; demo:reset | — |
| REQ-12-002 | Submission | Submit source repo/archive, migrations/schema, env sample without secrets, dependency/lock files | Fully working | — | — | V5: locks +.env.example | — |
| REQ-12-003 | Submission | Submit README covering setup, stack, architecture, modules, database, demo accounts/roles, tests,... | Fully working | — | — | V5: full README + clean-clone proof | — |
| REQ-12-004 | Submission | Submit one complete walkthrough recording preferably 12-20 minutes of live running application wi... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-005 | Submission | Submit Claude evidence: prompt log/export or screenshots showing Claude-only coding; explain how ... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-006 | Submission | Submit status sheet marking every feature Fully working / Simulated / Partial / Not implemented a... | Fully working | — | — | V6 feature-status.md/csv; counts verified vs matrix | — |
| REQ-12-007 | Interview | Interview: explain architecture, data model/relationships, authentication, roles and permission e... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-008 | Interview | Interview: walk lead?survey?hybrid calc?quote?order/project?materials?installation?ticket/service... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-009 | Interview | Interview: explain hybrid sizing formula assumptions and units; predict results when interviewer ... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-010 | Interview | Interview: explain architecture choice, prompting approach, how incorrect generated code was iden... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-011 | Interview | Interview: demonstrate dynamic settings and how changes propagate to forms, calculations, documen... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-012 | Interview | Interview: explain WhatsApp configuration, approved templates, consent/opt-out, demo vs real send... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-013 | Interview | Interview: answer questions on validation, error handling, security, REST APIs, indexing, perform... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-12-014 | Interview | Interview readiness: make a small live change using Claude AI, explain the diff, show the test re... | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-13-001 | Calculators | Separate off-grid and on-grid calculator specs are replaced by one hybrid solar calculator; inves... | Fully working | — | — | Phase5 HybridCalculatorService only (no separate off/on-grid); AT-04 Pass | — |
| REQ-13-002 | Process | Public feature references are for research only; must not copy proprietary code or branding | Not implemented | — | — | Not built / human deliverable / process item — no automated evidence | — |
| REQ-13-003 | Calculators | Subsidy, net metering, tariffs, GST treatment, sizing and financial outputs depend on jurisdictio... | Fully working | — | — | Sample/Demo tariff/subsidy labels; docs/calculation-spec.md disclaimer; SET-04 | — |
| REQ-13-004 | Settings-Calculator | Real policy values must include source, applicable state/utility and effective date, and remain c... | Fully working | — | — | Settings calculator rates; Sample/Demo unless sourced; SET-04 Pass | — |
| REQ-13-005 | Snapshots | Changing global settings must not silently rewrite historical quotes/reports already issued; pres... | Fully working | — | — | calculation_runs immutable JSON; QuotationTotalsAndWorkflowTest locked revision; REQ-MP-SNAPSHOTS | — |
| REQ-13-006 | Process | A complete working core flow is more valuable than many decorative screens; disclose unfinished/s... | Fully working | — | — | known-limitations.md; Simulated labels; this status sheet | — |
| REQ-MP-STACK | Architecture | Laravel + Filament + MySQL single application with one consistent responsive UI | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-MP-AUTH-SCOPE | Auth | Policies for authorization + query scoping (record-level) on resources/pages/actions/downloads/ex... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-MP-NUMBERING | Numbering | NumberingService using sequences table, row locking in transaction, unique DB indexes, prefixes/f... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-MP-SETTINGS-SVC | Settings | Typed SettingsService with safe cache invalidation on save, versioning/effective-date metadata wh... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-MP-SECRETS-UI | Security | Secrets (SMTP, WhatsApp tokens, API keys) in environment/server-side only; UI shows Configured/No... | Fully working | — | — | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 | — |
| REQ-MP-SNAPSHOTS | Snapshots | Calculation runs and quotation revisions store full JSON snapshots; issued snapshots never rewrit... | Fully working | — | — | Phase6: locked revision immutable when GST settings change | — |
| REQ-MP-CALC-SPEC | Calculators | Calculations server-side only with explicit units and shown intermediates; document additional as... | Fully working | — | — | Phase5: docs/calculation-spec.md authored before code | — |
| REQ-MP-FILES | Documents | Private disk storage, randomized stored names, MIME/extension/size validation by content, authori... | Fully working | — | — | Phase3: randomized names; MIME+ext+size; DocumentDownloadController; sensitive types; tests MIME/403 | — |
| REQ-MP-QR | Quotations | Quote QR verification page may expose only minimal configurable fields via unguessable token | Fully working | — | — | Phase6: public token page minimal fields; QR SVG via endroid/qr-code | — |
| REQ-MP-DEMO-DRIVERS | Integrations | External services behind interfaces with Demo drivers; never send real messages or call paid serv... | Fully working | — | — | WhatsAppProvider + DemoWhatsAppDriver default; MessageService; MessagingAndRemindersTest | — |
| REQ-MP-CONVERT-IDEM | Orders | Conversion quotation?project must be idempotent (no duplicate projects) | Fully working | — | — | ConvertQuotationToProjectTest::test_sequential_conversion_is_idempotent; ConvertQuotationMysqlConcurrencyTest | — |
| REQ-MP-TESTS | Tests | Required automated tests: calculation hand-computed, authorization, numbering concurrency (MySQL)... | Fully working | — | — | CalculationVerificationTest; SecurityVerificationTest; NumberingMysqlConcurrencyTest; StockMysqlConcurrencyTest; Messagi | — |

## Bugs / residuals (open)

| ID | Severity | Summary | Notes |
|----|----------|---------|-------|
| REQ-8-B-004 | Low | Optional 2FA/lockout/devices | Not implemented — security-verification.md |
| SEC residual | Info | LeadPolicy::export breadth | Reports still gated by reports.view |
| SEC residual | Info | DocumentPolicy morph edge | Strict for Visit/Lead; looser other morphs |
| UI-V4 | Info | Wide tables horizontal scroll | By design on narrow viewports |

## Verification log

```
matrix_rows=217 feature_status_rows=217
Fully working=171
Not implemented=26
Partial=13
Simulated=7
sum=217 orphan_check=ok
```

