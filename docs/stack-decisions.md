# Stack Decisions

Verified and installed during Phase 1 (2026-10-23).

## Local environment

| Tool | Version observed |
|------|------------------|
| PHP | 8.3.33 (cli, NTS) |
| Composer | 2.8.2 |
| Node | v22.23.2 |
| npm | 10.9.8 |
| MySQL | 8.0.46 via Docker container `solar_crm_mysql` (host port **3308**) |
| Git | 2.43.0 |

Host MySQL root uses socket auth (no passwordless access for OS user). Phase 1 uses a **dedicated Docker MySQL** so other databases are untouched.

## Installed application stack

| Package / layer | Installed version | Reason |
|-----------------|-------------------|--------|
| PHP runtime | 8.3.33 | Meets Laravel 13 `php ^8.3` and Filament 5 `php ^8.2` |
| Laravel | **v13.38.0** | Latest mutually compatible stable at install time |
| Filament | **v5.10.3** | Latest stable; `filament/support` allows `illuminate/contracts ^11.28\|^12.0\|^13.0` |
| Livewire | **v4.4.8** | Required by Filament 5 (`^4.4.7`) |
| MySQL | 8.0.x | Row locks for numbering concurrency tests |
| Testing | PHPUnit 12 (Laravel default) | Pest not required; PHPUnit covers Phase 1 tests |
| Permissions | Custom `roles` / `permissions` / `role_permissions` + Gates/Policies | No Spatie/Shield — interview-friendly and verified without extra package risk |
| Frontend | Vite (Laravel default) | Filament ships compiled assets; npm build optional for app CSS |
| PDF | **barryvdh/laravel-dompdf v3.1.x** (installed Phase 3) | Compatible with Laravel 13; Blade templates for visit/quote reports; simplest verified option |
| QR | **endroid/qr-code ^6** (Phase 6) | SVG writer; quote verification URLs; no external API |

## Composer note

Environment has Composer `disable-tls=true` / `secure-http=false`. Prefer re-enabling TLS for production machines. Local Packagist installs succeeded with `--prefer-dist`.

## Fallback used

None for Laravel/Filament versions. Permission approach: plain policies + `role_permissions` table (as Phase 1 recovery path preferred).
