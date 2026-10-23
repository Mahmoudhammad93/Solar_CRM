# Known Limitations

## Process / environment
- Assignment receipt timestamp not yet provided; `docs/project-clock.md` remains awaiting start time.
- This Cursor session is Auto/Composer, not Claude. Claude-only evidence for the evaluator may need a separate Claude session; disclosed honestly.
- Local MySQL for the app is Docker container `solar_crm_mysql` on port **3308** (not the system MySQL socket).

## Phase 1 complete / still missing
- Domain modules (leads, visits, calculators, quotations, orders, inventory, tickets, reports) are **not implemented**.
- Filament User/Role CRUD UI not yet built (users/roles are seeded; matrix editable later via DB/admin tools).
- SET-01 (logo + app name after refresh/logout) needs **human manual confirmation** (branding service + Settings UI are implemented).
- WhatsApp/email/maps/payment/e-sign/subsidy/production backups remain future Demo drivers.
- PDF branding snapshot service exists (`BrandingService::pdfSnapshot`) but quote PDFs are not generated yet.
- Composer TLS is disabled on this machine (`disable-tls`); fix before any production use.

## Demo passwords
Demo passwords are local-only fictional values documented in README. Do not reuse outside this assignment environment.
