# Backup & restore plan

Automated cloud backup integration: **Not configured** in this build. Use host-level mysqldump (or equivalent) on a schedule.

## Dump (MySQL)

```bash
# Example — adjust host/port/user/db from .env (never commit real passwords)
mysqldump -h 127.0.0.1 -P 3308 -u solar_crm -p \
  --single-transaction --routines --triggers solar_crm \
  | gzip > "/var/backups/solar_crm/$(date +%Y%m%d-%H%M%S).sql.gz"
```

Also back up:

- `storage/app` (private documents)
- `.env` (separate secure secret store — not in the same public backup share)

## Storage & retention

- Store dumps off-box (encrypted object storage or secure file server).
- Suggested retention: 7 daily + 4 weekly + 3 monthly (adjust via Settings → backup reminder preference; automated purge is **Unavailable**).
- Settings key `system.backup_reminder_days` is a preference for ops reminders only.

## Restore (manual — dangerous)

1. Confirm authorization (Super Admin / ops).
2. Take a fresh dump of the current DB before overwrite.
3. Stop app workers (`php artisan down` / stop queue & scheduler).
4. Restore:

```bash
gunzip < backup.sql.gz | mysql -h 127.0.0.1 -P 3308 -u solar_crm -p solar_crm
```

5. `php artisan migrate --force` only if the dump is older than current migrations — prefer restoring a dump that already matches the code revision.
6. `php artisan up`

**No automated restore UI** is shipped. Any future restore action must require confirmation + authorization and must never run from a public URL without auth.
