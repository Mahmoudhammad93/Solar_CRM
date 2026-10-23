# Acceptance & Settings Evidence (Validation V1)

**Generated:** `2026-10-23 17:23:25 EEST` (shell `date`)  
**Clock:** `docs/project-clock.md` still **AWAITING ASSIGNMENT RECEIPT TIMESTAMP** — remaining hours cannot be computed until you set Start.  
**Preflight:** `php artisan migrate:fresh --seed` OK; `php artisan test --compact` → **88 passed, 3 skipped, 0 failed** (91 tests).  
**Demo reset:** `php artisan demo:reset --force`  
**Walkthrough:** `docs/demo-script.md`

**Status legend**

| Status | Meaning |
|--------|---------|
| Pass | Automated evidence green; expected behavior met in code/tests |
| Partial | Core automated path works; UI/manual demo or secondary item still needs your confirmation |
| Fail | Broken (none open after Validation V1) |
| Not implemented | Not built / human deliverable outstanding |

**Manual confirmation required before promoting these to Pass:** AT-01 (full UI role demo), SET-01 (logo refresh in browser), AT-11 (end-to-end settings walkthrough on screen), AT-13 (recording + Claude-only package). Reply with confirmations and I will update statuses.

---

## Summary table

| ID | Status | Automated | Manual needed |
|----|--------|-----------|---------------|
| AT-01 | Partial | Yes | Login UI demo ≥2 roles |
| AT-02 | Pass | Yes | Optional UI spot-check |
| AT-03 | Pass | Yes | Optional UI spot-check |
| AT-04 | Pass | Yes | Optional UI spot-check |
| AT-05 | Pass | Yes | Optional UI spot-check |
| AT-06 | Pass | Yes | Optional UI spot-check |
| AT-07 | Pass | Yes | Optional UI spot-check |
| AT-08 | Pass | Yes | Optional UI spot-check |
| AT-09 | Pass | Yes | Optional UI spot-check |
| AT-10 | Pass | Yes | Optional UI filter/export |
| AT-11 | Partial | Yes | Full settings UI walkthrough |
| AT-12 | Pass | Yes | Confirm README follows on clean machine |
| AT-13 | Not implemented | No | Recording + Claude evidence + clock |
| SET-01 | Partial | Yes | Logo change visible after refresh |
| SET-02 | Pass | Yes | Optional UI |
| SET-03 | Pass | Yes | Optional UI |
| SET-04 | Pass | Yes | Optional UI |
| SET-05 | Pass | Yes | Optional UI preview |
| SET-06 | Pass | Yes | Confirm no secret fields in UI |
| SET-07 | Pass | Yes | Optional Livewire/HTTP as exec |
| SET-08 | Pass | Yes | Optional audit UI |
| SET-09 | Pass | Yes | Optional after logout/login |

---

## AT-01 — Demo login & role permissions

**Expected (PDF):** Log in with demo credentials; demonstrate ≥2 roles with different server-enforced permissions.  
**Related:** REQ-8-C-001, SET-07, REQ-12-001  

**Steps (automated):** Seed demo users; assert executive cannot access Settings; admin can; deactivated blocked; executive lead scoping.  
**Automated tests:**  
- `tests/Feature/Foundation/SettingsPermissionTest.php`  
- `tests/Feature/Foundation/DeactivatedUserLoginTest.php`  
- `tests/Feature/Leads/LeadScopingTest.php`  
- `tests/Feature/Settings/SettingsCompletionAndApiTest.php` (non-admin Settings/API)  
Command: `php artisan test --compact tests/Feature/Foundation tests/Feature/Leads/LeadScopingTest.php tests/Feature/Settings/SettingsCompletionAndApiTest.php`

**Manual steps for you:**  
1. `/admin/login` as `executive@solar-crm.test` / `Password1!` → no Settings save.  
2. Logout → `admin@solar-crm.test` → Settings OK.  
3. Try `inactive@solar-crm.test` → blocked.

**Actual:** Automated gates/scoping/deactivated login **Pass**. Full on-screen role demo **awaiting your confirmation**.  
**Status:** **Partial**

---

## AT-02 — Lead create / assign / follow-up / search

**Expected:** Create lead, assign, add follow-up/customer note, find via search/filter.  
**Related:** REQ-3-B-*, REQ-3-D-*  

**Automated tests:** `LeadConversionTest`, `LeadDuplicateAndLookupTest`, `LeadScopingTest`  
Command: `php artisan test --compact tests/Feature/Leads`

**Manual:** Sales → Leads → open `Demo Lead 1/2`; search “Demo Lead”; filter stage/source.

**Actual:** Feature tests Pass; demo seed has 20 leads with follow-ups.  
**Status:** **Pass**

---

## AT-03 — Site visit survey / docs / report

**Expected:** Schedule and complete site visit with survey fields, photo/document support, visit history/report.  
**Related:** REQ-3-C-*  

**Automated tests:** `tests/Feature/Visits/VisitScopingAndDocumentsTest.php`  
Command: `php artisan test --compact tests/Feature/Visits`

**Manual:** Visits → `Demo site DEMO-VISIT-3…` completed; survey fields; documents relation.

**Actual:** Visit tests Pass; 6 seeded visits.  
**Status:** **Pass**

---

## AT-04 — Hybrid calculator

**Expected:** Run hybrid calculator; show inputs, units, assumptions, intermediates, changing outputs; export report.  
**Related:** REQ-4-A-*, SET-04  

**Automated tests:** `tests/Feature/Calculators/HybridCalculatorTest.php`  
Command: `php artisan test --compact tests/Feature/Calculators/HybridCalculatorTest.php`

**Manual:** Calculators → Hybrid; open seeded run; export PDF.

**Actual:** Hand-math examples + snapshot immutability Pass; PDF path covered.  
**Status:** **Pass**

---

## AT-05 — Investment & EV calculators

**Expected:** Run solar investment and EV income calculators; input changes affect results logically.  
**Related:** REQ-4-B-*, REQ-4-C-*  

**Automated tests:** `tests/Feature/Calculators/InvestmentAndEvCalculatorTest.php`  
Command: `php artisan test --compact tests/Feature/Calculators/InvestmentAndEvCalculatorTest.php`

**Manual:** Open Investment + EV pages; tweak an input; observe outputs.

**Actual:** Calculator tests Pass; seeded calculation_runs include investment + EV.  
**Status:** **Pass**

---

## AT-06 — Quotations hybrid + pump, tax, PDF, status

**Expected:** Create itemized hybrid and pump quotations; configured taxes/totals; PDF; status changes.  
**Related:** REQ-5-A-*, REQ-5-B-*  

**Automated tests:** `tests/Feature/Quotations/QuotationTotalsAndWorkflowTest.php`  
Command: `php artisan test --compact tests/Feature/Quotations`

**Manual:** Quotations → Accepted hybrid + Sent pump; Download PDF; transition UI.

**Actual:** Totals/workflow/PDF tests Pass; seed has 8 quotes across statuses.  
**Status:** **Pass**

---

## AT-07 — Quote → order/project + milestones

**Expected:** Accept quotation → linked order/project without losing key data; update milestones; show progress.  
**Related:** REQ-6-A-*  

**Automated tests:** `ConvertQuotationToProjectTest`, `ProjectMilestoneAndAllocationTest` (+ MySQL concurrency skipped/pass on MySQL)  
Command: `php artisan test --compact tests/Feature/Orders`

**Manual:** My Orders → project milestones at different progress.

**Actual:** Conversion + milestone tests Pass; 3 seeded projects.  
**Status:** **Pass**

---

## AT-08 — Catalogue, materials, shortage / low-stock

**Expected:** View catalogue/stock; link materials to project; show shortage/low-stock.  
**Related:** REQ-6-C-*, REQ-9-F-*  

**Automated tests:** `StockServiceTest`, `ProjectMilestoneAndAllocationTest`  
Command: `php artisan test --compact tests/Feature/Inventory tests/Feature/Orders/ProjectMilestoneAndAllocationTest.php`

**Manual:** Project materials shortage line; Low stock products page.

**Actual:** Stock/allocation tests Pass; demo shortage required=100 seeded.  
**Status:** **Pass**

---

## AT-09 — Tickets, AMC, invoice visibility

**Expected:** Create/assign/update/close ticket linked to customer/project; AMC record or renewal workflow.  
**Related:** REQ-7-A-*  

**Automated tests:** `tests/Feature/Service/TicketAmcInvoiceTest.php`  
Command: `php artisan test --compact tests/Feature/Service/TicketAmcInvoiceTest.php`  
Also: `php artisan amc:renewal-reminders`

**Manual:** Tickets across statuses; AMC near renewal; invoices with partial payment.

**Actual:** Ticket/AMC/invoice tests Pass; seed 6 tickets, 2 AMC, 2 invoices.  
**Status:** **Pass**

---

## AT-10 — Dashboard KPIs & reports / exports

**Expected:** Dashboard KPIs and core reports reflect stored records; export and verify filters/totals.  
**Related:** REQ-3-A-*, REQ-7-B-*, REQ-2-006  

**Automated tests:** `tests/Feature/Dashboard/DashboardAndReportsTest.php`  
Command: `php artisan test --compact tests/Feature/Dashboard`

**Manual:** Dashboard filters; click KPI deep-link; Reports → CSV export compare row count.

**Actual:** KPI=SQL, report totals, CSV BOM, scoping tests Pass; post-seed KPIs non-zero.  
**Status:** **Pass**

---

## AT-11 — Dynamic settings demonstration

**Expected:** Demonstrate dynamic settings: app name/logo, password, dropdown/workflow, calculator assumptions, integrations honesty.  
**Related:** SET-01…SET-09, REQ-8-*, REQ-9-*, REQ-10-J-*  

**Automated tests:** `SettingsCompletionAndApiTest`, `PasswordChangeTest`, `HybridCalculatorTest` (SET-04), `MessagingAndRemindersTest`, `SettingsPermissionTest`  
Command: `php artisan test --compact tests/Feature/Settings tests/Feature/Foundation/PasswordChangeTest.php tests/Feature/Communications`

**Manual:** Follow `docs/demo-script.md` §10 Settings polish end-to-end on screen.

**Actual:** Automated SET suite Pass; **full UI narrative awaits your confirmation**.  
**Status:** **Partial**

---

## AT-12 — README, seed, no secrets, limitations

**Expected:** README setup works; seed/demo login exist; no secrets committed; known limitations listed.  
**Related:** REQ-12-001, REQ-12-002, REQ-MP-SECRETS-UI  

**Automated / verification (Validation V5):**  
- Temp clean clone under `../solar-crm-v5-verify` from committed tree  
- Sequence: `git clone` → Docker MySQL ready → `cp .env.example .env` → `composer install` → `npm ci`/`npm install` + `npm run build` → `key:generate` → `migrate:fresh --seed` → `storage:link` → `php artisan serve` → `/admin/login` HTTP 200 → `admin@solar-crm.test` / `Password1!` auth OK  
- Demo users in README + `DemoUserSeeder`  
- `.gitignore` includes `.env`; `git ls-files .env` empty; `package-lock.json` + `composer.lock` committed  
- `docs/known-limitations.md`, `docs/demo-script.md`  

**Gaps found & fixed in README:** MySQL ready-wait loop; do not use `npm install --omit=optional`; commit `package-lock.json`; clarify Docker auto-creates `solar_crm`.

**Actual:** Fresh setup works exactly as documented; no secrets in README/git.  
**Status:** **Pass**

---

## AT-13 — Recording + Claude-only evidence within 30h

**Expected:** Submit full-project recording and Claude-only evidence within the 30-hour limit.  
**Related:** REQ-1-012, REQ-12-004, REQ-12-005  

**Automated tests:** None (submission artifact).  

**Manual steps for you:**  
1. Provide assignment **receipt timestamp** → fill `docs/project-clock.md`.  
2. Record 12–20 min walkthrough using `docs/demo-script.md`.  
3. Package Claude-only coding evidence as required by evaluator (this session is Auto/Composer — disclosed in known-limitations).  
4. Submit before hard deadline (start+30h).

**Actual:** Not produced in-repo. Clock not started.  
**Status:** **Not implemented**

---

## SET-01 — Branding name/logo

**Expected:** Change app/website display name and logo → branding in nav/login and quotation/report PDFs; refresh does not revert.  
**Related:** REQ-8-A-001…004  

**Automated:** Settings save of `branding.*`; BrandingService used in panel + quote PDFs (prior phase evidence).  
Command: `php artisan test --compact tests/Feature/Foundation/SettingsServiceTest.php tests/Feature/Settings/SettingsCompletionAndApiTest.php`

**Manual:** Settings → Branding → change app name + logo → refresh → check nav + generate quote PDF.

**Actual:** Persistence/service Pass; **browser logo refresh confirmation pending**.  
**Status:** **Partial**

---

## SET-02 — Change own password

**Expected:** Current validated; mismatch rejected; new works and old fails.  
**Related:** REQ-8-B-001  

**Automated tests:** `tests/Feature/Foundation/PasswordChangeTest.php`  
Command: `php artisan test --compact --filter=PasswordChangeTest`

**Manual:** Account → Change password.

**Actual:** Livewire tests Pass.  
**Status:** **Pass**

---

## SET-03 — Dropdown / workflow values

**Expected:** New lead source/stage in forms/filters; changed project/ticket workflow reflected.  
**Related:** REQ-8-D-001, REQ-8-D-003  

**Automated tests:** `SettingsCompletionAndApiTest::test_set03_*`, `LeadDuplicateAndLookupTest`  
Command: `php artisan test --compact --filter=set03`

**Manual:** Lookups → add source → appear on Lead form; ticket status labels from Lookups.

**Actual:** Lookup options + TicketWorkflow labels Pass; transitions remain code-backed (documented).  
**Status:** **Pass**

---

## SET-04 — Hybrid defaults affect new runs

**Expected:** Changing PSH, DoD, loss, panel wattage changes results; prior snapshot fixed.  
**Related:** REQ-9-E-004  

**Automated tests:** `HybridCalculatorTest` snapshot/rerun cases  
Command: `php artisan test --compact tests/Feature/Calculators/HybridCalculatorTest.php`

**Manual:** Change Settings → Calculators PSH; re-run vs old CalculationRun.

**Actual:** Pass.  
**Status:** **Pass**

---

## SET-05 — WhatsApp Demo mode

**Expected:** Demo mode; template preview; visibly simulated; no false sent/delivered.  
**Related:** REQ-9-H-001…005  

**Automated tests:** `MessagingAndRemindersTest` (`demo_simulated`, never sent/delivered)  
Command: `php artisan test --compact tests/Feature/Communications`

**Manual:** Communications → Preview; Share quote → SIMULATED badge; Message logs.

**Actual:** Pass.  
**Status:** **Pass**

---

## SET-06 — Missing WhatsApp credentials

**Expected:** Useful setup message; never expose/request credentials in client-side field.  
**Related:** REQ-9-H-002, REQ-MP-SECRETS-UI  

**Automated tests:** `MessagingAndRemindersTest::test_test_connection_demo_is_labelled_simulated`; Communications page has Placeholder only (no token inputs).  
Command: `php artisan test --compact --filter=test_connection`

**Manual:** Open Communications → WhatsApp; confirm no secret inputs; read setup message.

**Actual:** Pass (code inspection + tests).  
**Status:** **Pass**

---

## SET-07 — Non-admin cannot save protected settings

**Expected:** Non-admin cannot save protected settings by UI or direct HTTP/API.  
**Related:** REQ-8-C-001  

**Automated tests:** `SettingsPermissionTest`, `SettingsCompletionAndApiTest::test_set07_*` / API 403  
Command: `php artisan test --compact tests/Feature/Foundation/SettingsPermissionTest.php --filter=set07`

**Manual:** Login as executive → Settings forbidden.

**Actual:** Pass.  
**Status:** **Pass**

---

## SET-08 — Audit history redaction

**Expected:** Settings changes audit user/time/summary; secrets/passwords not logged.  
**Related:** REQ-7-C-003  

**Automated tests:** `AuditRedactionTest`, `SettingsCompletionAndApiTest::test_set08_*`, messaging redaction  
Command: `php artisan test --compact tests/Feature/Foundation/AuditRedactionTest.php --filter=set08`

**Manual:** Audit log after settings save; confirm no raw tokens.

**Actual:** Pass.  
**Status:** **Pass**

---

## SET-09 — Settings persistence

**Expected:** Saved settings remain after logout/login and server restart (DB-backed).  
**Related:** REQ-8-001  

**Automated tests:** `SettingsCompletionAndApiTest::test_set09_setting_persists_after_cache_forget`  
Command: `php artisan test --compact --filter=set09`

**Manual:** Save locale → logout/login → value still present; optional `php artisan config:clear`.

**Actual:** DB + cache-forget persistence Pass.  
**Status:** **Pass**

---

## Defects fixed during Validation V1

None required beyond prior Phase 12 `ExampleTest` redirect expectation (already committed). Suite green after fresh seed.

## Bugs recorded

None new. Open honesty items remain Partial/Not implemented as above (AT-01, AT-11, SET-01 manual; AT-13 submission).
