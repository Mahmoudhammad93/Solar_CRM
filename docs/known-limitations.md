# Known Limitations

## Process / environment
- Assignment receipt timestamp not yet provided; `docs/project-clock.md` remains awaiting start time.
- This Cursor session is Auto/Composer, not Claude. Claude-only evidence for the evaluator may need a separate Claude session; disclosed honestly.
- Local MySQL for the app is Docker container `solar_crm_mysql` on port **3308** (not the system MySQL socket).

## Phase 5 (current)
- Investment and EV **PDF reports** are Partial (hybrid PDF fully working from snapshot).
- NPV/IRR on investment calculator **Not implemented** (bonus).
- Tariff/export/subsidy values are **Sample/Demo** unless Settings include source metadata.
- Project BOM / material allocation still Phase 7; PO/GRN Not implemented.
- Map preview remains Simulated.
- Filament User/Role CRUD UI not yet built (users/roles seeded).
- Composer TLS is disabled on this machine (`disable-tls`); fix before any production use.

## Demo passwords
Demo passwords are local-only fictional values documented in README. Do not reuse outside this assignment environment.
