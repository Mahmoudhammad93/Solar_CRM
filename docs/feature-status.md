# Feature Status

Updated after Phase 2.

| ID | Module | Feature | Status | Known bugs | Dependencies | Evidence |
|----|--------|---------|--------|------------|--------------|----------|
| REQ-1-001 | Process | Claude AI must be the only AI coding assistant used to generate/modify/refactor/debug project code | Not implemented |  |  |  |
| REQ-1-002 | Process | No manual coding of application source; use AI to generate/revise then run/test (IDE/terminal/Git/UI testin... | Not implemented |  |  |  |
| REQ-1-003 | Process | Maximum duration 30 hours from assignment receipt; stop at deadline and submit current state | Not implemented |  |  |  |
| REQ-1-004 | Process | Do not present incomplete or simulated features as fully functional | Not implemented |  |  |  |
| REQ-1-005 | Process | Keep Claude prompts/responses for architecture, generation, debugging, testing; provide exports/screenshots... | Not implemented |  |  |  |
| REQ-1-006 | Security | Use fictional seed data only; no real customer data, passwords, API tokens, or company secrets in the repos... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-1-007 | Security | Store credentials in environment variables or secure server-side secret mechanism | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-1-008 | Process | Candidate must understand submitted code; unexplained AI code may count against assessment | Not implemented |  |  |  |
| REQ-1-009 | Submission | Deliverable: runnable responsive web app with connected core workflows and demo credentials | Not implemented |  |  |  |
| REQ-1-010 | Submission | Deliverable: source repository/archive with migrations/schema, seed data, dependency/lock files, safe env s... | Not implemented |  |  |  |
| REQ-1-011 | Submission | Deliverable: README with setup, stack, architecture, DB setup, demo roles, test steps, deployment notes, kn... | Not implemented |  |  |  |
| REQ-1-012 | Submission | Deliverable: full-project screen-recording (live app, major modules, workflows, settings, WhatsApp demo, ca... | Not implemented |  |  |  |
| REQ-1-013 | Submission | Deliverable: Claude AI evidence (prompt history/export or screenshots) plus short validation/debugging note | Not implemented |  |  |  |
| REQ-1-014 | Submission | Deliverable: feature status sheet marking Fully working / Simulated / Partial / Not implemented | Not implemented |  |  |  |
| REQ-1-015 | Submission | Suggested recording structure: overview/stack (1-2m), lead?quote?project (4-6m), ops/inventory/tickets/perm... | Not implemented |  |  |  |
| REQ-1-016 | Process | Build original implementation; do not copy proprietary products, private repos, or third-party source code | Not implemented |  |  |  |
| REQ-2-001 | Architecture | Centralized CRM connecting sales, field survey, hybrid sizing, quotations, execution, inventory, service, a... | Not implemented |  |  |  |
| REQ-2-002 | Workflow | PRIMARY WORKFLOW: Lead ? follow-up/qualification ? customer/site visit ? hybrid calculation ? quotation ? a... | Not implemented |  |  |  |
| REQ-2-003 | UI | Consistent navigation, clear page titles, search/filter, validation messages, empty/loading/error states, r... | Not implemented |  |  |  |
| REQ-2-004 | Data model | Linked records (not disconnected duplicates): lead/customer links to visits, calculations, quotations, orde... | Partial |  |  | Phase6: quotes linked to lead/customer/visit/calculation_run |
| REQ-2-005 | Numbering | Unique reference IDs for leads, visits, quotations, orders/projects, products, invoices, tickets, AMC contr... | Partial |  |  | Phase6: quote numbering via NumberingService |
| REQ-2-006 | Dashboard | Dashboard metrics and reports calculated from stored data; no decorative hard-coded KPIs | Not implemented |  |  |  |
| REQ-2-007 | Settings | Dynamic business configuration in Settings tab and database; changes affect UI/calculations/templates/expor... | Not implemented |  |  |  |
| REQ-2-008 | Integrations | External services not integrated must be labeled Demo/Simulated and production setup documented | Simulated |  |  | Phase3: map Demo preview labelled; other externals still Demo/Simulated |
| REQ-2-009 | Auth | Role Super Admin: all modules, settings, users, roles/permissions, company config, audit logs, integrations | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions matrix |
| REQ-2-010 | Auth | Role Sales Manager: team leads, assignment, approvals, quotes, sales pipeline, team reports | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions matrix |
| REQ-2-011 | Auth | Role Sales Executive: assigned leads, follow-ups, visits, customers, calculators, quotations, own pipeline | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions matrix |
| REQ-2-012 | Auth | Role Surveyor/Engineer: assigned site visits, survey forms, GPS/site evidence, technical notes | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions matrix |
| REQ-2-013 | Auth | Role Project Manager/Operations: accepted orders, projects, milestones, materials, team allocation, install... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions matrix |
| REQ-2-014 | Auth | Role Inventory/Finance/Service: stock, procurement, invoice/payment visibility, support tickets, AMC | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles seeded + permissions matrix |
| REQ-3-A-001 | Dashboard | Summary cards: new leads, follow-ups due/overdue, scheduled visits, quotations drafted/sent/accepted, activ... | Not implemented |  |  |  |
| REQ-3-A-002 | Dashboard | Summary cards: recorded revenue and outstanding amounts when payment data exists | Not implemented |  |  |  |
| REQ-3-A-003 | Dashboard | Charts/lists: lead-stage pipeline, source mix, sales by team member, quote conversion, monthly revenue, pro... | Not implemented |  |  |  |
| REQ-3-A-004 | Dashboard | Charts/lists: recent activity, upcoming tasks and notifications | Not implemented |  |  |  |
| REQ-3-A-005 | Dashboard | Filters: date range, branch, team member, lead source, status | Not implemented |  |  |  |
| REQ-3-A-006 | Dashboard | KPI cards open underlying filtered records | Not implemented |  |  |  |
| REQ-3-A-007 | Dashboard | Activity center: timestamped events, task reminders, overdue highlights, read/unread notifications, action ... | Not implemented |  |  |  |
| REQ-3-B-001 | Leads | Lead identity fields: auto lead ID, created date/time, lead title, customer type, full name/company, contac... | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-B-002 | Leads | Lead location fields: address, city, district, state, PIN/postal, GPS if available, branch/territory | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-B-003 | Leads | Lead solar need fields: system type (hybrid, solar water pump, EV charging, other), property segment, roof/... | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-B-004 | Leads | Lead sales tracking: source, campaign/source details, assigned owner, stage, loss reason, probability, expe... | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-B-005 | Leads | Lead follow-up/files: next follow-up, reminder, outcome, call/meeting notes, documents/photos, interaction ... | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-B-006 | Leads | Lead actions: add/edit/view, assign/reassign, search/filter/sort, duplicate check, follow-up scheduling, cu... | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-B-007 | Leads | Lead import/export where feasible | Partial |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups CSV export done; import Not implemented |
| REQ-3-C-001 | Visits | Visit core: Visit ID, linked lead/customer, schedule, assigned surveyor, status, site address, GPS, resched... | Fully working |  |  | Phase3: VisitResource+history; Feature VisitScopingAndDocumentsTest 5 pass; AT-03 script VIS-2026-0001 |
| REQ-3-C-002 | Visits | Survey fields: property type, roof type/material, usable area, orientation/tilt, shading/obstructions, stru... | Fully working |  |  | Phase3: VisitForm survey section; AT-03 script + PDF report |
| REQ-3-C-003 | Visits | Utility details: bill/consumption evidence, monthly units, sanctioned load, single/three-phase, utility/DIS... | Fully working |  |  | Phase3: VisitForm utility section; AT-03 script |
| REQ-3-C-004 | Visits | Visit outcome: measurements, feasibility, customer needs, objections, feedback, recommended next step, prel... | Fully working |  |  | Phase3: VisitReportService Dompdf; PDF 879706 bytes 1 page A4 %PDF-1.7 |
| REQ-3-C-005 | Visits | Map/GPS real only when configured; otherwise manual coordinates and demo-labeled map preview | Simulated |  |  | Phase3: Demo preview placeholder only; no external map API |
| REQ-3-D-001 | Tasks | Tasks linkable to leads, customers, quotes, visits, projects, tickets | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-D-002 | Tasks | Task fields: title, description, owner, due date/time, priority, status, reminder, outcome | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-3-D-003 | Tasks | Task list/calendar view, overdue and due-today views, completion timestamps, linked-record activity history | Partial |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups list/due-today/overdue; calendar not shipped |
| REQ-4-001 | Calculators | Calculator must not return arbitrary/fixed results; show inputs, formulas/assumptions, intermediate totals,... | Fully working |  |  | Phase5: Hybrid/Investment/EV services + Filament; snapshot + PDF; calculation-spec.md |
| REQ-4-002 | Calculators | Results labeled as indicative estimates, not engineering certification | Fully working |  |  | Phase5: disclaimer on UI/PDF/snapshot |
| REQ-4-003 | Calculators | Configurable formulas and documented assumptions; validate non-negative, units, efficiency ranges, zero den... | Fully working |  |  | Phase5: validation + calculation-spec.md; HybridCalculatorTest |
| REQ-4-A-001 | HybridCalculator | Hybrid load table: appliance name, quantity, rated watts, hours/day, usage pattern, critical/non-critical, ... | Fully working |  |  | Phase5: load repeater + connected/daily/peak |
| REQ-4-A-002 | HybridCalculator | Hybrid solar/utility inputs: monthly units and/or bill, tariff/unit, usable roof area, proposed PV capacity... | Fully working |  |  | Phase5: PSH/PR/tariff/roof/export inputs from Settings |
| REQ-4-A-003 | HybridCalculator | Hybrid battery/inverter inputs: backup duration target, system voltage, battery chemistry, nominal capacity... | Fully working |  |  | Phase5: backup/DoD/voltage/efficiencies/generator |
| REQ-4-A-004 | HybridCalculator | Hybrid financial inputs: panel/inverter/battery/BOS prices, GST, installation, transport, structure/civil, ... | Fully working |  |  | Phase5: cost lines GST O&M subsidy |
| REQ-4-A-005 | HybridCalculator | Hybrid outputs: recommended PV kWp and panel count, roof-area-constrained size if enabled, daily/annual ene... | Fully working |  |  | Phase5: full outputs + hybrid PDF |
| REQ-4-A-006 | HybridCalculator | Display each selected input, unit and assumption beside results; do not hide battery/inverter rule path | Fully working |  |  | Phase5: intermediates + formula_path beside results |
| REQ-4-A-007 | HybridCalculator | If real tariff/net-metering/subsidy used: show state/utility, source/reference, effective date; else mark S... | Fully working |  |  | Phase5: Sample/Demo tariff labels unless sourced |
| REQ-4-B-001 | InvestmentCalculator | Investment calculator inputs: upfront cost, grant/subsidy, optional loan amount/interest/tenure, estimated ... | Fully working |  |  | Phase5: InvestmentCalculatorService inputs |
| REQ-4-B-002 | InvestmentCalculator | Investment calculator outputs: net initial investment, monthly/annual savings/revenue, annual operating cos... | Fully working |  |  | Phase5: net initial/savings/payback/ROI |
| REQ-4-B-003 | InvestmentCalculator | Investment calculator NPV/IRR (bonus) | Not implemented |  |  | NPV/IRR bonus deferred |
| REQ-4-C-001 | EVCalculator | EV charging income inputs: charger type/power kW, number of points, sessions/day, avg kWh/session, operatin... | Fully working |  |  | Phase5: EvCalculatorService inputs |
| REQ-4-C-002 | EVCalculator | EV charging outputs: energy sold, gross monthly/annual revenue, electricity cost, opex, estimated net profi... | Fully working |  |  | Phase5: energy/revenue/opex/profit/payback |
| REQ-4-HYBRID-SIZING | HybridCalculator | Acceptance: changing appliance qty/hours, backup target, panel wattage, peak sun hours, battery DoD or effi... | Fully working |  |  | Phase5: sensitivity tests qty/hours/backup/panel/PSH/DoD/eff |
| REQ-4-GEN-SAVINGS | Calculators | Acceptance: changing tariff, generation/loss, self-consumption or export rate changes projected savings and... | Fully working |  |  | Phase5: tariff/loss/export sensitivity + investment |
| REQ-4-COST-EST | HybridCalculator | Acceptance: changing product prices, GST, installation or transport updates line items and total; PDF match... | Fully working |  |  | Phase5: GST/install sensitivity; PDF from snapshot HTML equality |
| REQ-4-VALIDATION | Calculators | Acceptance: reject negative inputs, implausible efficiencies, invalid denominators, non-numeric inputs with... | Fully working |  |  | Phase5: negative/efficiency validation tests |
| REQ-5-A-001 | Quotations | Quotation common fields: auto quotation number, revision, created/valid-until, customer/lead/site, owner, s... | Fully working |  |  | Phase6: Quotation+revisions; Filament resource; validity from Settings |
| REQ-5-A-002 | Quotations | Quotation itemized lines: product/service, SKU, description/spec, qty, unit, unit price, discount, taxable ... | Fully working |  |  | Phase6: itemized lines + stock indicator; services lines |
| REQ-5-A-003 | Quotations | Quotation totals: subtotal, discount, taxable value, configured CGST/SGST or IGST, round-off if used, grand... | Fully working |  |  | Phase6: QuotationTotals CGST/SGST vs IGST; Money minor-units rounding |
| REQ-5-A-004 | Quotations | Hybrid proposal content: panel/inverter/battery/structure/protection/accessories, load and backup assumptio... | Fully working |  |  | Phase6: hybrid import from CalculationRun + template texts |
| REQ-5-A-005 | Quotations | Solar water-pump proposal: pump model/capacity, head/discharge/water requirement, controller, panels, struc... | Fully working |  |  | Phase6: pump_details + Sample/Demo subsidy note |
| REQ-5-A-006 | Quotations | Quote output/share: branded PDF, print view, QR to verification/detail, digital signature area, activity tr... | Fully working |  |  | Phase6: branded PDF, QR verify, demo signature, activity trail, Simulated share |
| REQ-5-A-007 | Quotations | Do not claim message sent unless provider confirms; demo actions must be labeled | Fully working |  |  | Phase6: MessageService demo never claims sent |
| REQ-5-B-001 | Catalogue | Product master fields: ID/SKU, category, brand/model/title, description, technical specs, wattage/capacity,... | Fully working |  |  | Phase4: ProductResource+SKU/specs/vendor/stock; CatalogueInventorySeeder 25 products |
| REQ-5-B-002 | Catalogue | Product categories cover panels, hybrid inverters, batteries, pumps, chargers, structures, wiring/protectio... | Fully working |  |  | Phase4: all 9 categories seeded with products |
| REQ-5-B-003 | Catalogue | Catalogue search, category/brand filters, sort, detail view, create/edit/archive, stock visibility; prices/... | Fully working |  |  | Phase4: search/filters/sort/archive/low-stock; Filament Products table |
| REQ-5-C-001 | Documents | Document attachments: bills, site photos, signed proposals, invoices, installation checklists, warranty doc... | Fully working |  |  | Phase3: polymorphic DocumentService private disk; DocumentsRelationManager; download policy |
| REQ-5-C-002 | Documents | Store filename/type/date/uploader metadata, secure access rules, downloadable links; restrict sensitive doc... | Not implemented |  |  |  |
| REQ-5-C-003 | Documents | Generate PDF quotes/proposals/reports with unique references and predictable filenames | Fully working |  |  | Phase6: quotation-{number}-r{rev}-{ts}.pdf |
| REQ-6-A-001 | Orders | Accepted quotation creates linked customer order/project without retyping core customer, line-item, amount ... | Not implemented |  |  |  |
| REQ-6-A-002 | Orders | Order fields: order ID/date, quote reference, customer/site, project type/capacity, contract value, confirm... | Not implemented |  |  |  |
| REQ-6-A-003 | Projects | Project fields: project ID, PM, assigned sales/engineering/installation team, planned/actual dates, priorit... | Not implemented |  |  |  |
| REQ-6-A-004 | Projects | Suggested milestones: Order Confirmed ? Documents Pending ? Site/Technical Approval ? Material Planning ? M... | Not implemented |  |  |  |
| REQ-6-A-005 | Projects | Installation record: crew, schedule, checklist, serial numbers where tracked, install photos, safety notes,... | Not implemented |  |  |  |
| REQ-6-A-006 | Projects | Show timeline, milestone timestamps, stuck/overdue projects, completion reports, progress updates | Not implemented |  |  |  |
| REQ-6-B-001 | Orders | My Orders: separate Pending/Active/Completed/Cancelled views; search by order/customer/quote; status/date f... | Not implemented |  |  |  |
| REQ-6-B-002 | Orders | If customer portal exists, customers only view own projects; else internal-only and document boundary | Not implemented |  |  |  |
| REQ-6-C-001 | Inventory | Track available, reserved, allocated, issued, returned, damaged quantities; deduct/reserve only via explici... | Fully working |  |  | Phase4: StockService states+locks; StockServiceTest+StockMysqlConcurrencyTest pass |
| REQ-6-C-002 | Inventory | Link BOM/material requirements to projects/orders; show required vs available vs allocated and shortage war... | Not implemented |  |  | Phase7 project allocation |
| REQ-6-C-003 | Inventory | Stock movement log: date, warehouse/branch, reference, quantity, user, reason; supplier/vendor name, contac... | Fully working |  |  | Phase4: stock_movements ledger + Vendor master (name/contact/address/tax/status/notes) |
| REQ-6-C-004 | Inventory | Low-stock alerts; material/product/order/project reports; reconciliation to stock transactions | Partial |  |  | Phase4: low-stock page + reconcile command; project/order reports later |
| REQ-6-C-005 | Inventory | Purchase requests/orders and goods-received workflow (bonus) | Not implemented |  |  | Bonus PO/GRN not built |
| REQ-7-A-001 | Tickets | Ticket fields: Ticket ID, customer, linked project/order/product, category, issue, priority/source, opened ... | Not implemented |  |  |  |
| REQ-7-A-002 | Tickets | Ticket states: Open, Assigned, In Progress, Waiting for Customer/Parts, Resolved, Closed; assignment, comme... | Not implemented |  |  |  |
| REQ-7-A-003 | AMC | AMC record: contract number, customer/site, start/end, coverage, visit frequency, SLA, amount, next service... | Not implemented |  |  |  |
| REQ-7-B-001 | Reports | Lead report: ID, source, owner, stage, creation date, follow-up, expected value, win/loss; date/source/stat... | Not implemented |  |  |  |
| REQ-7-B-002 | Reports | Customer report: profile, city/state, related leads, visits, quotes, projects/orders, tickets | Not implemented |  |  |  |
| REQ-7-B-003 | Reports | Quotation report: quote number, customer, date, subtotal/tax/total, status, owner, validity, conversion | Not implemented |  |  |  |
| REQ-7-B-004 | Reports | Sales/revenue report: period, team member, won deals, conversion, recorded revenue/payment; do not infer re... | Not implemented |  |  |  |
| REQ-7-B-005 | Reports | Order/project report: status, system type/capacity, manager, milestone, planned vs actual, delays, completion | Not implemented |  |  |  |
| REQ-7-B-006 | Reports | Product/tax report: stock/low-stock and tax summary from stored quote/invoice records and configured rates | Not implemented |  |  |  |
| REQ-7-B-007 | Reports | Ticket/AMC report: open/overdue/resolved, time-to-resolution if available, coverage, upcoming renewals | Not implemented |  |  |  |
| REQ-7-B-008 | Reports | Monthly business report: leads, quote conversion, orders/projects, sales value, recorded payments/outstandi... | Not implemented |  |  |  |
| REQ-7-B-009 | Reports | Reports support date-range/status/owner filters, sorting, totals, page size; CSV/Excel-compatible exports a... | Not implemented |  |  |  |
| REQ-7-C-001 | Admin | User management: name, email/login, phone, role, branch/team, active/inactive, last login if available; cre... | Partial |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 user model+seed; Filament UserResource not yet |
| REQ-7-C-002 | Admin | Role/permission management: restrict view/create/edit/delete/export for sensitive modules; demonstrate ?2 r... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-7-C-003 | Admin | Audit logs for important actions: user, action, entity/reference, timestamp, change summary; never log pass... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-7-C-004 | Admin | Backup/export plan and REST API approach; prefer one authenticated sample endpoint; full automated restore ... | Not implemented |  |  |  |
| REQ-7-C-005 | Security | Security: server-side checks, secure password hashing, validation, safe uploads, secrets in env/secure stor... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-001 | Settings | ONE dedicated Settings tab/page; all listed business configuration editable by authorized admins, persist i... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-002 | Settings | Settings organized into sub-tabs/cards with Save, Cancel, validation, confirmation for sensitive changes, s... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-003 | Settings | Unimplemented settings clearly marked unavailable rather than shown as working | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-A-001 | Settings-Branding | Editable app/website display name, organization/company name, logo, favicon, login-page logo, sidebar/heade... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-A-002 | Settings-Branding | Company details: legal name, display name, address, phone, support email, website, GSTIN/tax IDs, invoice a... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-A-003 | Settings-Branding | Logo/name changes appear in navigation, login where applicable, quotation PDFs, report headers, generated d... | Fully working |  |  | Phase6: quote PDF uses branding snapshot |
| REQ-8-A-004 | Settings-Branding | Secure logo upload with type/size validation, preview, replace, remove/reset-to-default | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-B-001 | Settings-Security | Signed-in user change password with current, new, confirmation; show policy, mismatch/weak errors, success | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-B-002 | Settings-Security | Admin-managed password reset/invitation, user activation/deactivation, session expiry/idle timeout and pass... | Not implemented |  |  |  |
| REQ-8-B-003 | Settings-Security | Never display/recover plaintext passwords; hash securely; do not log credentials; no secrets in browser cod... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-B-004 | Settings-Security | Optional: 2FA, login lockout, session/device list, security notification settings | Not implemented |  |  |  |
| REQ-8-C-001 | Settings-RBAC | Configure roles, module access and actions (view/create/edit/delete/export/approve/settings); protect setti... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-C-002 | Settings-RBAC | Configure branch/warehouse list, territory/region, assignment rules, team names, access scope | Partial |  |  | Phase4: warehouses/branches list; territory/assignment rules later |
| REQ-8-C-003 | Settings-Numbering | Configure company/fiscal-year details and numbering sequences/prefixes for leads, quotes, orders, invoices,... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-8-D-001 | Settings-Lists | Add/edit/deactivate lead sources, lead stages, customer types, property types, product categories, units, p... | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| REQ-8-D-002 | Settings-Lists | Where feasible: admin-defined custom fields (text/number/date/dropdown/checkbox) with label, required, defa... | Not implemented |  |  |  |
| REQ-8-D-003 | Settings-Workflow | Configure quotation statuses, project milestones, ticket workflow, AMC statuses and allowed transitions; ne... | Partial |  |  | Phase6: QuotationWorkflow transitions coded; Lookup UI for statuses later |
| REQ-8-D-004 | Settings-Lists | Configure table columns and defaults where feasible; deactivating config must not break existing records (p... | Not implemented |  |  |  |
| REQ-9-E-001 | Settings-Calculator | Hybrid calculator defaults editable: peak sun hours, PR/losses, panel wattage, battery chemistry, usable Do... | Fully working |  |  | Phase5: Settings Calculators tab + calc.hybrid.* keys |
| REQ-9-E-002 | Settings-Calculator | Editable rates/assumptions: electricity tariffs, export/net-metering prices, O&M defaults, component prices... | Fully working |  |  | Phase5: tariff/export/O&M/prices/GST in Settings |
| REQ-9-E-003 | Settings-Calculator | Each config keeps value, unit, effective date, status, source/reference, updated-by; real policy assumption... | Fully working |  |  | Phase5: settings value/unit/effective/source/is_sample |
| REQ-9-E-004 | Settings-Calculator | Calculator setting changes affect new calculations/reports; previously saved quotes/calculations retain ass... | Fully working |  |  | Phase5: snapshot immutability + rerun overlays settings |
| REQ-9-F-001 | Settings-Inventory | Product/inventory settings: category, brand, warranty defaults, units, tax rate, price defaults, serial/bat... | Fully working |  |  | Phase4: Settings Inventory tab + inventory.* keys; warehouses Filament |
| REQ-9-F-002 | Settings-Inventory | Configure allowed product types and component attributes for panels, hybrid inverters, batteries, pumps, EV... | Fully working |  |  | Phase4: product_attributes per category + Lookup product_category |
| REQ-9-G-001 | Settings-Templates | Configure numbering prefixes and sequence behavior for quote/invoice/order/project/ticket/AMC; validity day... | Not implemented |  |  |  |
| REQ-9-G-002 | Settings-Templates | Editable branded templates for quotation PDF, hybrid calculator report, site-visit report, installation/com... | Partial |  |  | Phase6: quote+hybrid+visit PDFs Blade; Settings template strings for quote sections |
| REQ-9-G-003 | Settings-Templates | Enable/disable QR code, signature block, footer/contact block, logo display, PDF/CSV/Excel export options w... | Fully working |  |  | Phase6: quotations.qr_enabled; signature block on PDF |
| REQ-9-H-001 | Settings-WhatsApp | WhatsApp settings page: provider/mode (Demo or configured), business phone/sender, business/account IDs, ap... | Not implemented |  |  |  |
| REQ-9-H-002 | Settings-WhatsApp | Secure server-side credential configuration, test-connection, connection status, webhook/callback if implem... | Not implemented |  |  |  |
| REQ-9-H-003 | Settings-WhatsApp | Editable message templates with safe placeholders, preview, validation for: lead assignment, follow-up remi... | Not implemented |  |  |  |
| REQ-9-H-004 | Settings-WhatsApp | WhatsApp actions: share quotation PDF/link, share calculator/report link, send approved template, open cust... | Simulated |  |  | Phase6: MessageService share labelled Simulated |
| REQ-9-H-005 | Settings-WhatsApp | Store message history: linked record, recipient, template, time, initiating user, status (queued/sent/deliv... | Not implemented |  |  |  |
| REQ-9-H-006 | Settings-WhatsApp | Respect customer consent/opt-in and opt-out; validate phone numbers with country code; safe errors for inva... | Not implemented |  |  |  |
| REQ-9-I-001 | Settings-Email | Configurable SMTP/provider mode and sender display name/address; server-side secret storage; test email act... | Not implemented |  |  |  |
| REQ-9-I-002 | Settings-Email | Editable email templates for quotes, visits, project updates, ticket updates, invoices, AMC reminders | Not implemented |  |  |  |
| REQ-9-I-003 | Settings-Email | Optional SMS/push channels may be documented if not implemented | Not implemented |  |  |  |
| REQ-9-I-004 | Settings-Notifications | Configure reminders, lead follow-up intervals, task due reminders, notification channels, working hours, ti... | Not implemented |  |  |  |
| REQ-10-J-001 | Settings-Regional | Locale, timezone, date/time format, currency symbol/precision, measurement units, rounding behavior, defaul... | Not implemented |  |  |  |
| REQ-10-J-002 | Settings-Finance | Tax rules/GST rates, company fiscal year, financial report parameters editable by authorized users, validat... | Fully working |  |  | Phase6: finance.* tax rates/rule in Settings Sample/Demo |
| REQ-10-J-003 | Settings-API | REST API configuration: enabled/disabled, API client/key metadata and rotation/revocation if implemented; n... | Not implemented |  |  |  |
| REQ-10-J-004 | Settings-Integrations | Map/GPS, signature, payment, backup and external-data integration status show Configured / Not configured /... | Not implemented |  |  |  |
| REQ-10-J-005 | Settings-System | Configure data-export formats, backup reminder/schedule, retention preferences, audit-log filter settings w... | Not implemented |  |  |  |
| SET-01 | Settings | SET-01 Change app/website display name and logo ? branding changes in nav/login and generated quotation/rep... | Partial |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 Branding settings UI+service present; manual logo refresh check pending human confirmation |
| SET-02 | Settings | SET-02 Change own password ? current validated; new/confirm mismatch rejected; new works and old fails | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| SET-03 | Settings | SET-03 Change dropdown/workflow value ? new lead source/stage in forms/filters; changed project/ticket work... | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups new lead_source in Lookup options |
| SET-04 | Settings | SET-04 Change hybrid defaults ? changing PSH, battery DoD, loss factor or panel wattage changes results and... | Fully working |  |  | Phase5: change PSH/DoD/loss/panel → new run changes; old snapshot fixed |
| SET-05 | Settings-WhatsApp | SET-05 Configure WhatsApp in Demo mode ? template preview works, demo visibly marked simulated, no false se... | Not implemented |  |  |  |
| SET-06 | Settings-WhatsApp | SET-06 Missing WhatsApp credentials ? useful setup message; never expose or request credentials in public/c... | Not implemented |  |  |  |
| SET-07 | Settings | SET-07 Role restrictions ? non-admin cannot save protected settings by UI or direct HTTP/API | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| SET-08 | Settings | SET-08 Audit history ? settings changes record user, timestamp, category, safe summary; secrets/passwords n... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| SET-09 | Settings | SET-09 Persistence ? saved settings remain after logout/login and server restart using DB-backed configuration | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-10-010 | UI | Responsive desktop/tablet/mobile design, accessible labels, search/filter/sort/pagination, sensible error s... | Not implemented |  |  |  |
| REQ-10-011 | Cross-cutting | PDF and CSV/Excel-compatible exports, quote QR code, signature capture/display area, document management, G... | Partial |  |  | Phase6: quote QR+signature+PDF; CSV exports later |
| REQ-10-012 | Integrations | Email/WhatsApp live delivery, live maps, external subsidy APIs, electronic signatures, payment gateway, pro... | Simulated |  |  | Phase3: live maps Simulated (Demo preview); other integrations unchanged |
| AT-01 | Acceptance | AT-01 Log in with demo credentials; demonstrate ?2 roles with different server-enforced permissions | Partial |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 roles/permissions ready; full UI demo pending human |
| AT-02 | Acceptance | AT-02 Create lead, assign, add follow-up/customer note, find via search/filter | Fully working |  |  | Phase2: Feature tests 22 pass; LeadConversion/Duplicate/Scoping/Lookup tests; migrate leads/customers/tasks/lookups |
| AT-03 | Acceptance | AT-03 Schedule and complete site visit with survey fields, photo/document support, visit history/report | Fully working |  |  | Phase3: tests 5 pass; scripted schedule/complete/upload/reschedule/history/PDF VIS-2026-0001 |
| AT-04 | Acceptance | AT-04 Run hybrid calculator; show changed inputs, units, assumptions, formula/intermediates, changing outpu... | Fully working |  |  | Phase5: hybrid UI+tests+PDF; 3 worked examples |
| AT-05 | Acceptance | AT-05 Run solar investment and EV income calculators; verify input changes affect results logically | Fully working |  |  | Phase5: investment+EV tests and Filament pages |
| AT-06 | Acceptance | AT-06 Create itemized hybrid and solar water-pump quotations, calculate configured taxes/totals, generate P... | Fully working |  |  | Phase6: QuotationTotalsAndWorkflowTest 9 pass; sample PDFs generated |
| AT-07 | Acceptance | AT-07 Accept quotation and convert to linked order/project without losing key data; update milestones and s... | Fully working |  |  | Phase7: ConvertQuotationToProject + milestones; ORD-2026-0001/PRJ-2026-0001 from QUO-2026-0003; tests 17+MySQL concurrency |
| AT-08 | Acceptance | AT-08 View catalogue/stock, link required materials to a project, show stock shortage/low-stock information | Fully working |  |  | Phase7: ProjectMaterial+AllocationService via StockService; shortage 12 req/2 avail; low-stock page retained |
| AT-09 | Acceptance | AT-09 Create, assign, update and close ticket linked to customer/project; show AMC record or renewal workflow | Not implemented |  |  |  |
| AT-10 | Acceptance | AT-10 Dashboard KPIs and core reports reflect stored records; export data and verify filters/totals | Not implemented |  |  |  |
| AT-11 | Acceptance | AT-11 Demonstrate dynamic settings: app name/logo, password change, new dropdown/workflow, calculator assum... | Not implemented |  |  |  |
| AT-12 | Acceptance | AT-12 README setup works; seed data/demo login exist; no secrets committed; known limitations listed | Not implemented |  |  |  |
| AT-13 | Acceptance | AT-13 Submit full-project recording and Claude-only evidence within the 30-hour limit | Not implemented |  |  |  |
| REQ-11-014 | Architecture | Related screens update consistently from one stored source of truth; avoid duplicate copies of same busines... | Not implemented |  |  |  |
| REQ-11-015 | UI | Show loading/success/failure feedback; validate input client and server where relevant; handle empty states | Not implemented |  |  |  |
| REQ-11-016 | Integrations | Integrations expose real status only when real provider/API response supports it; local success toast is no... | Not implemented |  |  |  |
| REQ-11-017 | Snapshots | Store snapshot of quote/calculation values so editing current settings does not silently alter previously a... | Fully working |  |  | Phase5: calculation_runs immutable JSON snapshot |
| REQ-12-001 | Submission | Submit working URL or local run steps, demo credentials, seed data, core flows accessible | Not implemented |  |  |  |
| REQ-12-002 | Submission | Submit source repo/archive, migrations/schema, env sample without secrets, dependency/lock files | Not implemented |  |  |  |
| REQ-12-003 | Submission | Submit README covering setup, stack, architecture, modules, database, demo accounts/roles, tests, deploymen... | Not implemented |  |  |  |
| REQ-12-004 | Submission | Submit one complete walkthrough recording preferably 12-20 minutes of live running application with narration | Not implemented |  |  |  |
| REQ-12-005 | Submission | Submit Claude evidence: prompt log/export or screenshots showing Claude-only coding; explain how generated ... | Not implemented |  |  |  |
| REQ-12-006 | Submission | Submit status sheet marking every feature Fully working / Simulated / Partial / Not implemented and list bu... | Not implemented |  |  |  |
| REQ-12-007 | Interview | Interview: explain architecture, data model/relationships, authentication, roles and permission enforcement | Not implemented |  |  |  |
| REQ-12-008 | Interview | Interview: walk lead?survey?hybrid calc?quote?order/project?materials?installation?ticket/service; explain ... | Not implemented |  |  |  |
| REQ-12-009 | Interview | Interview: explain hybrid sizing formula assumptions and units; predict results when interviewer changes ap... | Not implemented |  |  |  |
| REQ-12-010 | Interview | Interview: explain architecture choice, prompting approach, how incorrect generated code was identified/deb... | Not implemented |  |  |  |
| REQ-12-011 | Interview | Interview: demonstrate dynamic settings and how changes propagate to forms, calculations, documents, report... | Not implemented |  |  |  |
| REQ-12-012 | Interview | Interview: explain WhatsApp configuration, approved templates, consent/opt-out, demo vs real sending, deliv... | Not implemented |  |  |  |
| REQ-12-013 | Interview | Interview: answer questions on validation, error handling, security, REST APIs, indexing, performance, audi... | Not implemented |  |  |  |
| REQ-12-014 | Interview | Interview readiness: make a small live change using Claude AI, explain the diff, show the test result | Not implemented |  |  |  |
| REQ-13-001 | Calculators | Separate off-grid and on-grid calculator specs are replaced by one hybrid solar calculator; investment and ... | Not implemented |  |  |  |
| REQ-13-002 | Process | Public feature references are for research only; must not copy proprietary code or branding | Not implemented |  |  |  |
| REQ-13-003 | Calculators | Subsidy, net metering, tariffs, GST treatment, sizing and financial outputs depend on jurisdiction/utility/... | Not implemented |  |  |  |
| REQ-13-004 | Settings-Calculator | Real policy values must include source, applicable state/utility and effective date, and remain configurable | Not implemented |  |  |  |
| REQ-13-005 | Snapshots | Changing global settings must not silently rewrite historical quotes/reports already issued; preserve snaps... | Not implemented |  |  |  |
| REQ-13-006 | Process | A complete working core flow is more valuable than many decorative screens; disclose unfinished/simulated f... | Not implemented |  |  |  |
| REQ-MP-STACK | Architecture | Laravel + Filament + MySQL single application with one consistent responsive UI | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-MP-AUTH-SCOPE | Auth | Policies for authorization + query scoping (record-level) on resources/pages/actions/downloads/exports/API;... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-MP-NUMBERING | Numbering | NumberingService using sequences table, row locking in transaction, unique DB indexes, prefixes/format from... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-MP-SETTINGS-SVC | Settings | Typed SettingsService with safe cache invalidation on save, versioning/effective-date metadata where requir... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-MP-SECRETS-UI | Security | Secrets (SMTP, WhatsApp tokens, API keys) in environment/server-side only; UI shows Configured/Not configur... | Fully working |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 |
| REQ-MP-SNAPSHOTS | Snapshots | Calculation runs and quotation revisions store full JSON snapshots; issued snapshots never rewritten; edits... | Fully working |  |  | Phase6: locked revision immutable when GST settings change |
| REQ-MP-CALC-SPEC | Calculators | Calculations server-side only with explicit units and shown intermediates; document additional assumptions ... | Fully working |  |  | Phase5: docs/calculation-spec.md authored before code |
| REQ-MP-FILES | Documents | Private disk storage, randomized stored names, MIME/extension/size validation by content, authorized downlo... | Fully working |  |  | Phase3: randomized names; MIME+ext+size; DocumentDownloadController; sensitive types; tests MIME/403 |
| REQ-MP-QR | Quotations | Quote QR verification page may expose only minimal configurable fields via unguessable token | Fully working |  |  | Phase6: public token page minimal fields; QR SVG via endroid/qr-code |
| REQ-MP-DEMO-DRIVERS | Integrations | External services behind interfaces with Demo drivers; never send real messages or call paid services by de... | Not implemented |  |  |  |
| REQ-MP-CONVERT-IDEM | Orders | Conversion quotation?project must be idempotent (no duplicate projects) | Not implemented |  |  |  |
| REQ-MP-TESTS | Tests | Required automated tests: calculation hand-computed, authorization, numbering concurrency (MySQL), snapshot... | Partial |  |  | Phase1: migrate:fresh --seed; Feature tests 15 pass + MySQL concurrency pass; login HTTP 200 Foundation tests present; domain calc/stock/snapshot tests later |

Totals: {'Not implemented': 170, 'Fully working': 41, 'Partial': 6}
Total features: 217
