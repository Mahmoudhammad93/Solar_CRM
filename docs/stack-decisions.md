# Stack Decisions (Phase 0 — draft, no install yet)

Verified against local tooling and Packagist metadata on **2026-10-23** (commands: `php -v`, `composer -V`, `composer show … --all`, Packagist `p2` JSON for `filament/filament` / `filament/support`, `node -v`, `npm -v`, `mysql --version`, `php -m`).

## Local environment

| Tool | Version observed |
|------|------------------|
| PHP | 8.3.33 (cli, NTS) |
| Composer | 2.8.2 |
| Node | v22.23.2 |
| npm | 10.9.8 |
| MySQL client | 8.0.46-0ubuntu0.24.04.4 |
| Git | 2.43.0 |
| PHP extensions | pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, bcmath, fileinfo, **intl** present |

**Compatibility note:** MySQL server appears installed (client works) but passwordless login as OS user `hammad` failed (`ERROR 1045`). App DB user/password will be configured in `.env` during scaffolding; do not assume root without password.

## Proposed application stack (latest mutually compatible stable)

| Package / layer | Proposed version | Reason |
|-----------------|------------------|--------|
| PHP runtime | **8.3.x** (local 8.3.33) | Satisfies Laravel 13 (`php ^8.3`) and Filament 5 (`php ^8.2`) |
| Laravel | **^13.38** (latest stable seen: `v13.38.0`) | Latest stable Laravel; `php ^8.3` matches environment |
| Filament | **^5.10** (latest stable seen: `v5.10.3`) | Latest Filament; `filament/support` requires `illuminate/contracts: ^11.28\|^12.0\|^13.0` ? **compatible with Laravel 13** |
| Livewire | **^4.4.7** (as required by Filament support) | Pulled transitively by Filament 5; do not pin below Filament’s constraint |
| MySQL | **8.0.x** | Matches client; Laravel Eloquent/MySQL locking for numbering concurrency tests |
| Frontend build | **Vite** (Laravel default) + Node 22 | Compatible with Laravel’s current Vite plugin; no separate SPA |
| Testing | **Pest** (PHPUnit under the hood), aligned to Laravel 13 scaffolding defaults at install time | Required calculation/auth/numbering/snapshot/stock tests |
| PDF generation | Decide at install (e.g. Dompdf or similar via Composer) | Needed for branded quote/calculator/visit reports; exact package chosen in Phase 1 with `composer show` |
| Excel/CSV | Native CSV + optional PhpSpreadsheet if needed | PDF asks CSV/Excel-compatible exports |

## Alternatives considered

| Option | Why not chosen (for now) |
|--------|---------------------------|
| Laravel 12.x (`v12.70.1` available) | Also Filament-compatible; skipped because master prompt asks for **latest** mutually compatible stable, and Filament 5 explicitly allows illuminate `^13.0` |
| Filament 3.x | Older major; would force older Livewire/Laravel pairing and miss current stable |
| Separate SPA (React/Vue) front-end | Conflicts with “single application with one consistent responsive UI” Filament-first approach |

## Risks / concerns before Phase 1 install

1. **Laravel 13 + Filament 5 + Livewire 4** is the newest stable combo — fewer community tutorials than Laravel 11/Filament 3; budget extra debug time.
2. **Composer SSL note:** environment printed `You are running Composer with SSL/TLS protection disabled` during `composer show`. Investigate/fix before production; for local Packagist queries it still returned package metadata.
3. **MySQL credentials** must be set before migrations; Phase 1 will create a dedicated local DB named in `.env` only.
4. No application packages installed yet (Phase 0 boundary). Exact lockfile versions will be recorded after `composer create-project` / `filament:install`.

## Approval gate

Stack change away from Laravel + Filament + MySQL requires human approval. Proceeding with the table above unless the human objects before Phase 1.
