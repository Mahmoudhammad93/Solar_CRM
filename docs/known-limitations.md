# Known Limitations

## Process / environment
- Assignment receipt timestamp not yet provided; `docs/project-clock.md` remains awaiting start time.
- This Cursor session is Auto/Composer, not Claude. Claude-only evidence for the evaluator may need a separate Claude session; disclosed honestly.
- Local MySQL for the app is Docker container `solar_crm_mysql` on port **3308** (not the system MySQL socket).

## Phase 4 (current)
- Map preview remains **Simulated** (Demo preview only).
- Project BOM / material allocation and shortage-on-project (AT-08 second half) deferred to Phase 7 — AT-08 marked **Partial**.
- Purchase orders / GRN (REQ-6-C-005) **Not implemented** (bonus).
- Editable PDF templates in Settings still Partial (visit report Blade only).
- Filament User/Role CRUD UI not yet built (users/roles seeded).
- WhatsApp/email/payment/e-sign/subsidy/production backups remain future Demo drivers.
- Composer TLS is disabled on this machine (`disable-tls`); fix before any production use.

## Demo passwords
Demo passwords are local-only fictional values documented in README. Do not reuse outside this assignment environment.
