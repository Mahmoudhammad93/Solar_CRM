# Known Limitations

## Process / environment
- Assignment receipt timestamp not yet provided; `docs/project-clock.md` remains awaiting start time.
- This Cursor session is Auto/Composer, not Claude. Claude-only evidence for the evaluator may need a separate Claude session; disclosed honestly.
- Local MySQL for the app is Docker container `solar_crm_mysql` on port **3308** (not the system MySQL socket).

## Phase 6 (current)
- Quote?order/project conversion is **not** built (Phase 7 / AT-07).
- Email/WhatsApp share is **Simulated** via MessageService (no real send).
- E-signature is explicitly **Demo e-signature, not legally verified**.
- Tax split rule defaults are **Sample/Demo** configurable rates (not tax advice).
- Investment/EV PDFs remain Partial; NPV/IRR Not implemented.
- Filament User/Role CRUD UI not yet built.
- Composer TLS is disabled on this machine (`disable-tls`); fix before any production use.

## Demo passwords
Demo passwords are local-only fictional values documented in README. Do not reuse outside this assignment environment.
