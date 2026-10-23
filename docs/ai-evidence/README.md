# AI evidence package (Validation V8)

**Generated:** `2026-10-23 18:12:41 EEST` (shell `date`)  
**Rule:** Organize authentic artifacts only. Do not invent, backdate, or fabricate chat exports. This index matches `ls` + `git log` at generation time.

---

## 1. Tools used (honest)

| Tool | Role in this repo |
|------|-------------------|
| **Cursor** | IDE / agent host for implementation sessions |
| **Coding agent in recorded sessions** | Contemporaneous phase reports state **Auto/Composer**, not Claude (`phase-reports.md` Phase 0 / Phase 1 “Identity honesty”; also `docs/known-limitations.md`) |
| **Assignment expectation** | PDF §1 / `docs/MASTER_PROMPT.md` require Claude-only coding |
| **Claude chat (planning)** | You stated the planning conversation was done in Claude chat and that its **export will be included** under `exports/` — **not present yet** (see Gaps) |
| **Shell / PHPUnit / Docker MySQL** | Verification (`php artisan test`, `migrate:fresh --seed`, browser dry-runs) |

**Do not misread this package as proof that Claude generated every commit.** The git history and append-only `phase-reports.md` are the primary process log for what actually ran in-repo. Add Claude planning (and any Claude coding) exports yourself under `exports/` so the evaluator can compare.

---

## 2. Exact phase prompts (verbatim sources)

| Prompt | Location | Status |
|--------|----------|--------|
| Master / standing rules | [`docs/MASTER_PROMPT.md`](../MASTER_PROMPT.md) | **Present** (verbatim standing prompt) |
| Assignment PDF | [`docs/Solar_CRM_First_Round_Assignment_Claude_AI_Only.pdf`](../Solar_CRM_First_Round_Assignment_Claude_AI_Only.pdf) | **Present** |
| Extracted text | [`docs/assignment-extracted.txt`](../assignment-extracted.txt) | **Present** |
| Per-phase prompts you paste | `docs/ai-evidence/prompts/` | **Missing** — directory exists with placeholder only; paste `phase-00.md` … `phase-12.md` / `validation-v*.md` yourself |
| Validation V5–V8 chat instructions | Not filed as separate prompt files | **Missing** unless you export them into `prompts/` or `exports/` |

When you paste phase prompts, keep them **verbatim** (no editing). This README will then point at those filenames without rewriting their text.

---

## 3. Where raw exports / screenshots live

**Path:** `docs/ai-evidence/exports/`

**Proven contents at V8 generation** (`ls -la docs/ai-evidence/exports`):

```
README.md   ← placeholder only (“place exports here”) — NOT chat evidence
```

**No Cursor chat JSON/HTML/PDF exports and no screenshots are present.**  
You add them yourself. Suggested names (examples only — use real filenames when saving):

- `exports/claude-planning-chat.*` — Claude planning conversation export  
- `exports/cursor-phase-0.*` … `cursor-phase-12.*` — Cursor chat exports per phase  
- `exports/cursor-validation-v1.*` … `v8.*` — validation chats  
- `exports/screenshots/` — UI / error / test failure screenshots  

Do **not** ask an agent to regenerate fake chat transcripts.

---

## 4. Phase reports log

**File:** [`phase-reports.md`](phase-reports.md)  
**Policy:** Append-only. Prior phase entries must not be rewritten (MASTER_PROMPT / project rule).  
**Contains:** Phases 0–12 and Validation V1–V7 entries with shell timestamps, commands, and status summaries.

---

## 5. Validation / debugging note (real examples only)

Wrong or incomplete outputs were found by **failing tests**, **hand math**, **security review**, **clean-clone dry-run**, or **seed/UI linkage checks** — then fixed in the commits below. Sources: `phase-reports.md`, `docs/security-verification.md`, `docs/calculation-verification.md`, `git log`.

| Finding | How found | Fix (commit / evidence) |
|---------|-----------|-------------------------|
| Numbering concurrency failed under SQLite / wrong DB env for child processes | Phase 1 tests | Dedicated `NumberingMysqlConcurrencyTest` + MySQL env — Phase 1 report |
| `ExampleTest` expected `/` behavior wrong after Filament redirect to `/admin` | Phase 12 suite | Fixed in Phase 12 work; suite **88 passed, 3 skipped** (`632bb23` / report) |
| My Orders Filament tabs missing `$query` parameter | Runtime / Livewire | `e64eefe` *fix: My Orders tabs must name the query parameter $query* |
| API empty abilities fail-open; quote verify & document download unthrottled; `role_id`/`is_active` on User `$fillable` | Validation V2 security matrix / tests | `c6e7736` — fail-closed abilities, throttles, `forceFill` in seeder; `SecurityVerificationTest` |
| Demo golden path accepted quote not on Lead 3 | Validation V4 UI workflow | `f70bd71` — `DemoScenarioSeeder` fix (UI-V4-01) |
| Hand-math vs calculator services | Validation V3 | `95e48e6` — `CalculationVerificationTest`; **no formula bugs**; all scenarios reconciled |
| `package-lock.json` missing; `npm install --omit=optional` breaks Vite/rolldown | Validation V5 clean clone | `d25b7a4` — commit lockfile; README documents wait + no omit-optional |
| Messaging claiming sent/delivered | Design honesty / tests | Demo statuses `demo_simulated`\|`failed` only — `MessagingAndRemindersTest` (Phase 10 / V1) |

**Not “wrong Claude output” in a recoverable transcript sense:** many fixes were agent/test-driven iterations recorded in phase reports. Without exports in `exports/`, we cannot attribute a specific chat turn to a bug — only the git/test trail above.

---

## 6. Phase → commit → tests index

`git log --oneline` at V8 (newest first, 34 commits):

```
203e6c4 docs: Validation V7 timed live demo script with dry-run marks.
af7d8ef docs: Validation V6 final feature status sheet and CSV export.
08dd44a docs: record Validation V5 commit hash.
d25b7a4 docs: Validation V5 clean-setup README proof and package-lock.
316fd69 docs: refresh README for AT-12 clean-setup review path.
158559f docs: record Validation V4 commit hash
f70bd71 docs: Validation V4 UI workflow checklist and demo golden path.
823ef2c docs: record Validation V3 commit hash
95e48e6 docs/test: Validation V3 calculation hand-math reconciliation.
67bf87e docs: record Validation V2 commit hash
c6e7736 security: Validation V2 hardening and permission matrix evidence.
e24ac09 docs: record Validation V1 commit hash
3a113b6 docs: Validation V1 acceptance evidence for AT/SET checklist.
06b0e3b docs: record Phase 12 commit hash in phase report
632bb23 Phase 12: fictional demo scenario seed, reset command, and walkthrough script.
8465bad docs: record Phase 11 commit hash in phase report
31ef6f2 Phase 11: complete Settings A–J gaps, REST leads API, and security hardening.
f881d82 docs: record Phase 10 commit hash in phase report
bb9b16f Phase 10: honest WhatsApp/email Demo layer, templates, consent, and reminders.
ccee4d6 docs: record Phase 9 commit hash in phase report
fafb8a9 Phase 9: real-data dashboard KPIs, activity center, and ReportQuery exports.
e64eefe fix: My Orders tabs must name the query parameter $query
0f042f6 docs: record Phase 8 commit hash in phase report
929e9a8 Phase 8: service tickets, AMC, and invoice payment visibility.
c630f76 docs: point Phase 7 report at final commit hash
365052e Phase 7: idempotent quote-to-project conversion, milestones, and materials.
42fe511 Phase 6: quotations with configurable tax totals, snapshots, PDF, and QR
5b9514f Phase 5: configuration-driven hybrid/investment/EV calculators with snapshots
58b22c7 Phase 4: product catalogue, warehouses, vendors, and StockService ledger
085a3a8 Phase 3: visits, survey, secure documents, and branded visit PDFs
edc2ef5 Phase 2: leads, customers, lookups, tasks with scoping
0c2ccc9 Phase 1: Laravel/Filament foundation with auth, settings, numbering
fe0cbe2 Phase 0: record shell evidence and commit hash in phase report
e10c050 Phase 0: preflight docs, atomic requirements matrix, stack draft
```

### Mapping (implementation commit → primary tests / docs)

| Phase / validation | Primary commit(s) | Primary tests / evidence docs |
|--------------------|-------------------|-------------------------------|
| Phase 0 | `e10c050`, `fe0cbe2` | Matrix generator; no app tests yet |
| Phase 1 | `0c2ccc9` | `tests/Feature/Foundation/*` (Settings, Numbering MySQL concurrency, Password, Audit, Deactivated) |
| Phase 2 | `edc2ef5` | `tests/Feature/Leads/*` |
| Phase 3 | `085a3a8` | `tests/Feature/Visits/VisitScopingAndDocumentsTest.php` |
| Phase 4 | `58b22c7` | `tests/Feature/Inventory/StockServiceTest.php`, `StockMysqlConcurrencyTest.php` |
| Phase 5 | `5b9514f` | `HybridCalculatorTest`, `InvestmentAndEvCalculatorTest`; `docs/calculation-spec.md` |
| Phase 6 | `42fe511` | `QuotationTotalsAndWorkflowTest` |
| Phase 7 | `365052e` (+ `e64eefe` My Orders fix) | `ConvertQuotationToProjectTest`, `ProjectMilestoneAndAllocationTest`, `ConvertQuotationMysqlConcurrencyTest` |
| Phase 8 | `929e9a8` | `TicketAmcInvoiceTest` |
| Phase 9 | `fafb8a9` | `DashboardAndReportsTest` |
| Phase 10 | `bb9b16f` | `MessagingAndRemindersTest` |
| Phase 11 | `31ef6f2` | `SettingsCompletionAndApiTest` |
| Phase 12 | `632bb23` | Demo seed + `demo:reset`; suite green per report |
| V1 Acceptance | `3a113b6` | `docs/acceptance-evidence.md` |
| V2 Security | `c6e7736` | `SecurityVerificationTest`; `docs/security-verification.md` |
| V3 Calculations | `95e48e6` | `CalculationVerificationTest`; `docs/calculation-verification.md` |
| V4 UI / workflow | `f70bd71` | `docs/ui-workflow-checklist.md` |
| V5 Clean setup | `d25b7a4` | README + clean-clone notes |
| V6 Status sheet | `af7d8ef` | `docs/feature-status.md` / `.csv` |
| V7 Demo script | `203e6c4` | `docs/demo-script.md` |
| V8 Evidence org | *(this commit)* | This README |

---

## 7. Directory proof (`ls -R docs/ai-evidence`)

At V8 generation:

```
docs/ai-evidence:
  README.md          ← this file (added in V8)
  phase-reports.md   ← present (append-only log)
  exports/
    README.md        ← placeholder only
  prompts/
    README.md        ← placeholder only
```

**Absent:** any `*.json` / `*.html` / `*.pdf` chat exports, any screenshot images, any pasted phase prompt bodies.

---

## 8. Gaps — what you must still add

1. **Claude planning chat export** → `docs/ai-evidence/exports/`  
2. **Cursor chat exports / screenshots** for Phases 0–12 and Validation V1–V8 → `exports/`  
3. **Verbatim phase prompt files** you sent → `docs/ai-evidence/prompts/`  
4. **Reconciliation note for PDF Claude-only rule** vs recorded Auto/Composer identity (evaluator-facing honesty already in `known-limitations.md`; strengthen with your Claude exports if any coding was redone in Claude)  
5. **AT-13 recording** (separate from this folder; see `acceptance-evidence.md`)  
6. **Assignment receipt timestamp** in `docs/project-clock.md` (still awaiting)

Until those files exist on disk, do not claim the evidence package is complete for AT-13 / REQ-1-005 / REQ-12-005.

---

## 9. Related docs

| Doc | Purpose |
|-----|---------|
| [`phase-reports.md`](phase-reports.md) | Chronological build log |
| [`../known-limitations.md`](../known-limitations.md) | Includes assistant-identity disclosure |
| [`../acceptance-evidence.md`](../acceptance-evidence.md) | AT/SET status; AT-13 open |
| [`../feature-status.md`](../feature-status.md) | Final status sheet (V6) |
| [`../demo-script.md`](../demo-script.md) | Recording script (V7) |
| [`../MASTER_PROMPT.md`](../MASTER_PROMPT.md) | Standing prompt (verbatim) |
