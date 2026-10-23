# Known Limitations

## Process / environment
- Assignment receipt timestamp not yet provided; `docs/project-clock.md` remains awaiting start time.
- This Cursor session is Auto/Composer, not Claude. Claude-only evidence for the evaluator may need a separate Claude session; disclosed honestly.
- Local MySQL for the app is Docker container `solar_crm_mysql` on port **3308** (not the system MySQL socket).

## Phase 3 (current)
- Map preview is **Simulated** (`Demo preview` UI only; no Google/Mapbox/OSM API calls).
- Preliminary visit BOM is free-text until the product catalogue exists (helper text notes this).
- Editable PDF templates in Settings (REQ-9-G-002) are Partial: visit report is a branded Blade view, not Settings-editable.
- Document morph owners for quote/project/installation/ticket are schema-ready; UI relation managers ship with Visits (and Lead morph exists); other modules attach later.
- Filament User/Role CRUD UI not yet built (users/roles seeded).
- WhatsApp/email/payment/e-sign/subsidy/production backups remain future Demo drivers.
- Composer TLS is disabled on this machine (`disable-tls`); fix before any production use.

## Demo passwords
Demo passwords are local-only fictional values documented in README. Do not reuse outside this assignment environment.
