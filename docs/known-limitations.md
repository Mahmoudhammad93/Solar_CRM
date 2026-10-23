# Known Limitations

Updated as work progresses. Phase 0 baseline (no application yet).

## Process / environment
- Assignment receipt timestamp not yet provided by the human; `docs/project-clock.md` is incomplete until that is set.
- This Cursor session is Auto/Composer, not Claude. Claude-only evidence for the evaluator may be incomplete or need a separate Claude session; this is disclosed honestly (see Phase 0 report).
- MySQL client is installed (8.0.46) but default local login for OS user `hammad` without a password failed (`ERROR 1045`). Database credentials will be configured in `.env` during app scaffolding (Phase 1+).

## Application
- No Laravel/Filament application scaffolded yet (Phase 0 boundary).
- No features implemented; see `docs/feature-status.md` (all **Not implemented**).

## Integrations (planned Demo by default)
Per PDF §2 / §10: WhatsApp/email live delivery, live maps, external subsidy APIs, e-signature, payment gateway, and production backups may be simulated when labelled and documented. Production wiring will be README-only unless credentials are deliberately configured later.
