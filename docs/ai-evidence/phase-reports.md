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
- Earlier failure: parallel numbering under sqlite transaction wrap / wrong DB env for child processes — fixed by dedicated MySQL test class + env passthrough

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
2. Settings → change app name + upload logo → Save → refresh → logout/login (SET-01/SET-09).
3. Account → Change password (SET-02): wrong current rejected; new works; old fails.
4. Login as `executive@solar-crm.test` — Settings nav should be inaccessible / 403 (SET-07).
5. Login as `inactive@solar-crm.test` — blocked.
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
2. Administration → Lookups: add lead_source value; confirm it appears on Lead form Source + table filter.
3. Create lead, assign owner, add interaction note, schedule follow-up, search/filter.
4. Login as executive A vs B: cannot open the other's lead URL.
5. Convert lead to customer twice; same customer_number.

### Next phase readiness
Ready for visits / calculators / quotations phases.
