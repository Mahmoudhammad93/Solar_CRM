# Known Limitations

Honest disclosure for evaluators (PDF §12 / AT-12). Calculator outputs are **indicative estimates, not engineering certification**. Demo data and passwords are **fictional / local-only**.

**Shell time when finalized (V9):** see `docs/submission-checklist.md` header (`date` output).  
**Clock:** `docs/project-clock.md` is still **AWAITING ASSIGNMENT RECEIPT TIMESTAMP** — elapsed vs the 30-hour deadline **cannot be computed** until you set Start.

---

## Process / identity / time

- **Receipt clock unset** — no Start/deadline values; do not guess. Provide receipt date/time/timezone to fill the clock before claiming AT-13 timing compliance.
- **Assistant identity:** Implementation sessions recorded in `docs/ai-evidence/phase-reports.md` as **Cursor + Auto/Composer**, not Claude. PDF §1 / `MASTER_PROMPT.md` require Claude-only coding. This gap is disclosed here and in `docs/ai-evidence/README.md`. Claude planning/chat exports are **pending human add** under `docs/ai-evidence/exports/`.
- **AT-13 screen recording** — not produced in-repo; marked pending on you (`docs/demo-script.md` is the script).
- Local MySQL is Docker `solar_crm_mysql` on host port **3308** (not the system MySQL socket).

---

## Simulated integrations (labelled in UI / README)

| Integration | Status | What works | What production needs |
|-------------|--------|------------|------------------------|
| WhatsApp outbound | **Simulated** | Demo driver; `demo_simulated` / `failed` logs; template preview; no secret fields in UI | Tested provider client, env secrets, webhooks, honest provider statuses |
| Email outbound | **Simulated** | `MAIL_MAILER=log` by default; settings Configured/Not configured | Verified SMTP/API + test-send |
| Maps / live tiles | **Simulated** | Manual GPS + Demo map preview | Map provider key + Configured from real API |
| Subsidy / tariff feeds | **Sample/Demo** | Editable Settings; Sample/Demo labels when unsourced | Jurisdiction APIs with source + effective date |
| E-signature | **Simulated** | Demo signature area on quote/install — **not legally verified** | E-sign provider or wet-sign process |
| Payment gateway | **Simulated** | Manual payments; `payment_gateway_note` on invoices | PSP + webhooks; never invent paid |
| Production backups | **Partial** | `docs/backup-plan.md` + reminder preference; restore **Unavailable** | Scheduled off-site backup + authorized restore |

Never invent WhatsApp/email `queued` / `sent` / `delivered` without a real provider response.

---

## Unimplemented / Partial product items

- **NPV/IRR** (bonus) — Not implemented (`REQ-4-B-003`).
- **Purchase orders / GRN** (bonus) — Not implemented (`REQ-6-C-005`).
- **Admin custom fields** — Not implemented (`REQ-8-D-002`).
- **Optional 2FA / lockout / device list** — Not implemented (`REQ-8-B-004`).
- **Task calendar view** — Partial (list / due-today / overdue only).
- **Lead CSV import** — Partial (export exists; import not built).
- **Filament User/Role CRUD UI** — not built (seeded users/roles only).
- **Installation completion PDF** — Partial.
- **AMC scheduling automation** — Partial; needs OS `schedule:run` + queue worker.
- **Milestone / partial billing beyond one invoice per order** — Not implemented.
- **Customer portal** — Not implemented (My Orders is internal-only).
- **SMS / push** — Not implemented.
- **WhatsApp webhook / live Configured client** — Not implemented (stub only).

---

## Calculation limitations

- Results are **indicative estimates**, not engineering certification (shown on UI/PDF/snapshots).
- Assumptions (PSH, PR/loss, DoD, tariffs, subsidies) are configuration-driven; Sample/Demo when not sourced.
- **NPV/IRR** not implemented; investment calculator provides payback/ROI-style outputs only.
- Changing Settings affects **new** runs only; historical `calculation_runs` / quotation revisions keep immutable JSON snapshots (verified in tests / V3 hand-math).

---

## Security caveats

- Demo password `Password1!` is documented for local evaluation only — **change before any shared/staging deploy**.
- Optional 2FA/lockout not built.
- `LeadPolicy::export` is broader than `leads.export` alone; reports remain gated by `reports.view` (see `docs/security-verification.md`).
- DocumentPolicy is strict for Visit/Lead morphs; looser for rarely used morph types.
- Composer may run with TLS disabled on this build agent (`disable-tls`) — **re-enable TLS for production** / CI.
- Public quote verify exposes minimal fields via unguessable token + rate limit; do not expand fields without review.
- API tokens: plaintext shown once; empty abilities fail closed; throttle 60/min.

---

## Performance / scale limits

- Built and tested as a **single-server demo** (Filament admin + MySQL), not load-tested for multi-tenant SaaS scale.
- Wide tables use in-card horizontal scroll on narrow viewports (by design).
- Dashboard/report queries are scoped SQL over demo-sized data; expect index/query review before large production volumes.
- Queue/scheduler are **database driver + cron** — without `queue:work` / `schedule:run`, reminders and queued jobs will not run.
- Concurrent numbering/stock tested on MySQL; SQLite skips lock-concurrency cases.

---

## UI / acceptance residuals

- Manual confirmation still useful for AT-01 (on-screen multi-role demo), SET-01 (logo refresh), AT-11 (full settings walkthrough) — automated gates Pass; see `docs/acceptance-evidence.md`.
- Full keyboard-only tour and every empty-state variant not exhaustively automated (`docs/ui-workflow-checklist.md`).

---

## Feature status counts (Validation V6 sheet)

| Status | Count |
|--------|------:|
| Fully working | 171 |
| Simulated | 7 |
| Partial | 13 |
| Not implemented | 26 |
| **Total** | **217** |

Source: `docs/feature-status.md` / `docs/feature-status.csv`.

---

## Demo passwords (fictional)

Shared local password: `Password1!` — see README. Do not reuse outside this assignment environment. Reset with `php artisan demo:reset --force`.
