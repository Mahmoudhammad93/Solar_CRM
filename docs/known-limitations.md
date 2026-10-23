# Known Limitations

## Process / environment
- Assignment receipt timestamp not yet provided; `docs/project-clock.md` remains awaiting start time.
- This Cursor session is Auto/Composer, not Claude. Claude-only evidence for the evaluator may need a separate Claude session; disclosed honestly.
- Local MySQL for the app is Docker container `solar_crm_mysql` on port **3308** (not the system MySQL socket).

## Communications (Phase 10)
- WhatsApp/Email outbound is **Demo / SIMULATED – not delivered** by default. Statuses are only `demo_simulated` or `failed` unless a real provider response exists.
- **Configured WhatsApp provider client**: Not implemented (interface + stub; no untested HTTP client).
- WhatsApp **webhook/callback**: Not implemented.
- Email **test send** action: Unavailable / Not implemented for live SMTP.
- Live delivery requires env credentials + future tested provider; never invent `queued`/`sent`/`delivered`.

## Other
- **Payment gateway** is **Simulated/not implemented** — manual payments only; labelled on invoices.
- AMC **scheduling automation** is **Partial** — `amc:renewal-reminders` + `reminders:dispatch` need a scheduler process.
- Partial/milestone billing beyond one invoice per order is **Not implemented**.
- Installation **completion-report PDF** remains **Partial**.
- My Orders is **internal-only** (no customer portal in this build).
- E-signature is explicitly **Demo e-signature, not legally verified**.
- Filament User/Role CRUD UI not yet built.
- Composer TLS is disabled on this machine (`disable-tls`); fix before any production use.

## Demo passwords
Demo passwords are local-only fictional values documented in README. Do not reuse outside this assignment environment.

## Validation V1
- See `docs/acceptance-evidence.md` for AT/SET status. Manual confirmation still required for AT-01, AT-11, SET-01 UI checks; AT-13 not started (clock awaiting receipt).
