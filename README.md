# Solar CRM Management System

Rootflash Technology first-round assignment prototype: Laravel + Filament + MySQL Solar CRM.

## Stack (installed)

| Package | Version |
|---------|---------|
| PHP | 8.3.x |
| Laravel | 13.38.0 |
| Filament | 5.10.3 |
| Livewire | 4.4.8 |
| MySQL | 8.0 (Docker `solar_crm_mysql` on host port **3308**) |

See `docs/stack-decisions.md`.

## Local setup

1. Start MySQL (dedicated container used in Phase 1):

```bash
docker start solar_crm_mysql
# or recreate:
docker run -d --name solar_crm_mysql \
  -e MYSQL_ROOT_PASSWORD=solar_crm_root_local \
  -e MYSQL_DATABASE=solar_crm \
  -e MYSQL_USER=solar_crm \
  -e MYSQL_PASSWORD=solar_crm_pass_local \
  -p 3308:3306 mysql:8.0
```

2. Copy env and install:

```bash
cp .env.example .env
# set DB_* to match container (port 3308)
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Open `http://127.0.0.1:8000/admin/login`.

## Demo credentials (fictional / local-only)

| Role | Email | Password |
|------|-------|----------|
| Super Admin | `admin@solar-crm.test` | `Password1!` |
| Sales Manager | `manager@solar-crm.test` | `Password1!` |
| Sales Executive | `executive@solar-crm.test` | `Password1!` |
| Surveyor / Engineer | `surveyor@solar-crm.test` | `Password1!` |
| Project Manager / Operations | `pm@solar-crm.test` | `Password1!` |
| Inventory / Finance / Service | `ops@solar-crm.test` | `Password1!` |
| Deactivated (login blocked) | `inactive@solar-crm.test` | `Password1!` |

## Tests

```bash
php artisan test --testsuite=Feature
# MySQL concurrency (numbering locks):
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3308 DB_DATABASE=solar_crm_test \
  DB_USERNAME=solar_crm DB_PASSWORD=solar_crm_pass_local \
  php artisan test --filter=NumberingMysqlConcurrencyTest
```

## Docs

- `docs/MASTER_PROMPT.md`, `docs/project-clock.md`, `docs/requirements-matrix.csv`
- `docs/feature-status.md`, `docs/known-limitations.md`, `docs/ai-evidence/phase-reports.md`

## Communications (WhatsApp / Email) — production setup

Defaults are **Demo / SIMULATED – not delivered**. Live delivery is not claimed.

### WhatsApp
1. Keep `whatsapp.mode=demo` in Settings → Communications until a tested provider client exists.
2. Put secrets **only** in server env (never in the Settings UI or DB):
   - `WHATSAPP_TOKEN`
   - `WHATSAPP_PHONE_NUMBER_ID`
   - `WHATSAPP_BUSINESS_ACCOUNT_ID` (optional)
   - `WHATSAPP_ALLOW_LIVE_TEST=false` / `WHATSAPP_ALLOW_LIVE_SEND=false` unless you intentionally enable calls
3. UI shows **Configured / Not configured** only; it never asks for a token.
4. Webhook/callback: **Not implemented** in this build.
5. Configured provider HTTP client: **Not implemented** (interface + stub only).

### Email
1. Sender name/address: Settings → Communications.
2. SMTP secrets: `MAIL_*` in `.env` only (`MAIL_MAILER=smtp`, host, username, password).
3. Test email action: **Unavailable** (demo/log path only).

### Reminders
Run the scheduler so in-app notifications are created from stored leads/tasks:

```bash
php artisan reminders:dispatch
# or cron: * * * * * php /path/to/artisan schedule:run
```

### Consent
Customers must be **opted_in** (with timestamp/source) before WhatsApp template sends. Opt-out and unknown consent block sending.

## Known limitations

See `docs/known-limitations.md`. Integrations default to Demo/Simulated; see production setup above.
