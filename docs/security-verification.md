# Security & Authorization Verification (Validation V2)

**Generated:** `2026-10-23 17:34:27 EEST` (shell `date`)  
**Scope:** PDF §7C, SET-07, SEC — server-side checks via HTTP/Livewire/Gates (not UI hiding).  
**Preflight:** `migrate:fresh --seed` OK; full suite **101 passed, 3 skipped, 0 failed** (104 tests).  
**Primary automated suite:** `php artisan test --compact tests/Feature/Security/SecurityVerificationTest.php`  
**Related suites:** Foundation (settings/password/deactivated/audit), Leads/Visits scoping, Settings/API, Quotations verify, Dashboard export, Communications redaction.

**Status legend:** Pass = expected denial/allow proven · Partial = mitigated with residual note · Fail = unauthorized path open · N/A = not in scope for role

---

## Findings (V2)

| ID | Severity | Finding | Disposition | Fix commit |
|----|----------|---------|-------------|------------|
| SEC-V2-01 | Medium | API tokens with **empty `abilities`** previously skipped ability check (fail-open) | **Fixed** — require `leads:read` or `*` | `c6e7736` |
| SEC-V2-02 | Medium | Public `/quotes/verify/{token}` had **no rate limit** (token brute-force surface) | **Fixed** — `throttle:quote-verify` 30/min/IP | `c6e7736` |
| SEC-V2-03 | Low | Document download lacked dedicated throttle | **Fixed** — `throttle:document-download` 60/min | `c6e7736` |
| SEC-V2-04 | Medium | `User::$fillable` included `role_id` / `is_active` (mass-assignment privilege escalation if request ever filled) | **Fixed** — removed from fillable; seeders use `forceFill` | `c6e7736` |
| SEC-V2-05 | Low | Client filenames with `../` — Symfony already strips path; added `safeOriginalName` + reject when raw name still contains traversal | **Hardened** | `c6e7736` |
| SEC-V2-06 | Info | Demo password `Password1!` in README/seeders | **Accepted** — documented demo-only; no production secrets | — |
| SEC-V2-07 | Info | Optional 2FA / device list (REQ-8-B-004) | **Not implemented** — known-limitations | — |
| SEC-V2-08 | Low | `LeadPolicy::export` allows `leads.view` **or** `leads.export` | **Documented** — Reports Hub still requires `reports.view` / export checks | residual |
| SEC-V2-09 | Info | DocumentPolicy: non-Visit/Lead morph owners → any `documents.view` holder | **Documented** — limited morph types in use | residual |

**Unauthorized-access paths known after V2:** none open. Residuals documented above + `docs/known-limitations.md`.

---

## Permission matrix (expected = `PermissionCatalog::defaultRoleGrants`)

Y = allowed · — = denied. Actuals asserted by `test_permission_matrix_matches_default_role_grants` (DB pivot + `User::hasPermission`).

| Role | Module | view | create | edit | delete | export | approve | settings |
|------|--------|------|--------|------|--------|--------|---------|----------|
| `super-admin` | dashboard | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | leads | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | customers | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | visits | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | tasks | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | calculators | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | quotations | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | products | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | documents | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | orders | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | projects | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | inventory | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | tickets | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | amc | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | reports | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | users | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | settings | Y | Y | Y | Y | Y | Y | Y |
| `super-admin` | audit | Y | Y | Y | Y | Y | Y | Y |
| `sales-manager` | dashboard | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | leads | Y | Y | Y | Y | Y | Y | — |
| `sales-manager` | customers | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | visits | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | tasks | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | calculators | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | quotations | Y | Y | Y | Y | Y | Y | — |
| `sales-manager` | products | — | — | — | — | — | — | — |
| `sales-manager` | documents | — | — | — | — | — | — | — |
| `sales-manager` | orders | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | projects | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | inventory | — | — | — | — | — | — | — |
| `sales-manager` | tickets | — | — | — | — | — | — | — |
| `sales-manager` | amc | — | — | — | — | — | — | — |
| `sales-manager` | reports | Y | Y | Y | — | Y | Y | — |
| `sales-manager` | users | Y | — | — | — | — | — | — |
| `sales-manager` | settings | — | — | — | — | — | — | — |
| `sales-manager` | audit | — | — | — | — | — | — | — |
| `sales-executive` | dashboard | Y | Y | Y | — | — | — | — |
| `sales-executive` | leads | Y | Y | Y | — | — | — | — |
| `sales-executive` | customers | Y | Y | Y | — | — | — | — |
| `sales-executive` | visits | Y | Y | Y | — | — | — | — |
| `sales-executive` | tasks | Y | Y | Y | — | — | — | — |
| `sales-executive` | calculators | Y | Y | Y | — | — | — | — |
| `sales-executive` | quotations | Y | Y | Y | — | — | — | — |
| `sales-executive` | products | Y | Y | Y | — | — | — | — |
| `sales-executive` | documents | Y | Y | Y | — | — | — | — |
| `sales-executive` | orders | Y | Y | Y | — | — | — | — |
| `sales-executive` | projects | — | — | — | — | — | — | — |
| `sales-executive` | inventory | — | — | — | — | — | — | — |
| `sales-executive` | tickets | — | — | — | — | — | — | — |
| `sales-executive` | amc | — | — | — | — | — | — | — |
| `sales-executive` | reports | — | — | — | — | — | — | — |
| `sales-executive` | users | — | — | — | — | — | — | — |
| `sales-executive` | settings | — | — | — | — | — | — | — |
| `sales-executive` | audit | — | — | — | — | — | — | — |
| `surveyor-engineer` | dashboard | Y | Y | Y | — | — | — | — |
| `surveyor-engineer` | leads | — | — | — | — | — | — | — |
| `surveyor-engineer` | customers | — | — | — | — | — | — | — |
| `surveyor-engineer` | visits | Y | Y | Y | — | — | — | — |
| `surveyor-engineer` | tasks | Y | Y | Y | — | — | — | — |
| `surveyor-engineer` | calculators | — | — | — | — | — | — | — |
| `surveyor-engineer` | quotations | — | — | — | — | — | — | — |
| `surveyor-engineer` | products | — | — | — | — | — | — | — |
| `surveyor-engineer` | documents | Y | Y | Y | — | — | — | — |
| `surveyor-engineer` | orders | — | — | — | — | — | — | — |
| `surveyor-engineer` | projects | — | — | — | — | — | — | — |
| `surveyor-engineer` | inventory | — | — | — | — | — | — | — |
| `surveyor-engineer` | tickets | — | — | — | — | — | — | — |
| `surveyor-engineer` | amc | — | — | — | — | — | — | — |
| `surveyor-engineer` | reports | — | — | — | — | — | — | — |
| `surveyor-engineer` | users | — | — | — | — | — | — | — |
| `surveyor-engineer` | settings | — | — | — | — | — | — | — |
| `surveyor-engineer` | audit | — | — | — | — | — | — | — |
| `project-manager` | dashboard | Y | Y | Y | — | Y | — | — |
| `project-manager` | leads | — | — | — | — | — | — | — |
| `project-manager` | customers | — | — | — | — | — | — | — |
| `project-manager` | visits | — | — | — | — | — | — | — |
| `project-manager` | tasks | Y | Y | Y | — | Y | — | — |
| `project-manager` | calculators | — | — | — | — | — | — | — |
| `project-manager` | quotations | — | — | — | — | — | — | — |
| `project-manager` | products | — | — | — | — | — | — | — |
| `project-manager` | documents | Y | Y | Y | — | Y | — | — |
| `project-manager` | orders | Y | Y | Y | — | Y | — | — |
| `project-manager` | projects | Y | Y | Y | — | Y | — | — |
| `project-manager` | inventory | Y | Y | Y | — | Y | — | — |
| `project-manager` | tickets | Y | Y | Y | — | Y | — | — |
| `project-manager` | amc | Y | Y | Y | — | Y | — | — |
| `project-manager` | reports | Y | Y | Y | — | Y | — | — |
| `project-manager` | users | — | — | — | — | — | — | — |
| `project-manager` | settings | — | — | — | — | — | — | — |
| `project-manager` | audit | — | — | — | — | — | — | — |
| `inventory-finance-service` | dashboard | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | leads | — | — | — | — | — | — | — |
| `inventory-finance-service` | customers | — | — | — | — | — | — | — |
| `inventory-finance-service` | visits | — | — | — | — | — | — | — |
| `inventory-finance-service` | tasks | — | — | — | — | — | — | — |
| `inventory-finance-service` | calculators | — | — | — | — | — | — | — |
| `inventory-finance-service` | quotations | — | — | — | — | — | — | — |
| `inventory-finance-service` | products | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | documents | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | orders | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | projects | — | — | — | — | — | — | — |
| `inventory-finance-service` | inventory | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | tickets | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | amc | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | reports | Y | Y | Y | — | Y | — | — |
| `inventory-finance-service` | users | — | — | — | — | — | — | — |
| `inventory-finance-service` | settings | — | — | — | — | — | — | — |
| `inventory-finance-service` | audit | — | — | — | — | — | — | — |

---

## Control coverage

| Control | Expected | Automated evidence | Actual | Status | Req IDs |
|---------|----------|-------------------|--------|--------|---------|
| Role × module × action grants | Catalog defaults | `SecurityVerificationTest::test_permission_matrix_matches_default_role_grants` | Matches DB + hasPermission | Pass | REQ-7-C-005, AT-01 |
| Settings save (SET-07) | Non-admin forbidden | `SettingsPermissionTest`, `SettingsCompletionAndApiTest::test_set07_*`, Livewire `ManageSettings` | 403 / Gate deny | Pass | SET-07 |
| Executive cross-lead IDOR | 403/404 | `LeadScopingTest`, `SecurityVerificationTest::test_executive_and_surveyor_cross_access_by_id_denied` | 404 | Pass | REQ-7-C-005 |
| Surveyor cross-visit IDOR | 403/404/deny | same + `VisitScopingAndDocumentsTest` | Denied | Pass | REQ-7-C-005 |
| Document download authz | Owner/policy | `VisitScopingAndDocumentsTest::test_unauthorized_document_download_returns_403` | 403 | Pass | REQ-5-C-002 |
| Export / reports | `reports.view` required | `SecurityVerificationTest::test_export_permission_blocks_reports_hub_for_executive`, `DashboardAndReportsTest` | Forbidden for exec | Pass | AT-10 |
| API token auth + scope | Bearer + ability + permission | `SettingsCompletionAndApiTest`, `test_api_token_empty_abilities_and_wrong_scope_denied` | 401/403/503 | Pass | Phase 11 API |
| API record scoping | Exec sees own only | `test_api_leads_200_and_scopes_executive` | Scoped | Pass | REQ-7-C-005 |
| Deactivated login/session | Blocked | `DeactivatedUserLoginTest`, `test_deactivated_user_session_blocked_from_panel_routes` | Login reject / 403 | Pass | SET / SEC |
| Password change | Own password only; no role escalate | `PasswordChangeTest`, `test_password_change_does_not_mass_assign_role_or_active` | Password only | Pass | SET-02 |
| Mass assignment | Cannot fill role_id/is_active | `test_user_fill_rejects_privileged_role_and_active_fields` | Ignored | Pass | SEC |
| Upload MIME disguise | Reject PHP-as-jpg | `VisitScopingAndDocumentsTest::test_disallowed_mime_php_renamed_as_jpg_is_rejected` | ValidationException | Pass | REQ-7-C-005 |
| Upload oversize | Reject > max KB | `test_upload_rejects_oversize_path_traversal_and_stores_safe_name` | Rejected | Pass | REQ-7-C-005 |
| Path traversal filename | UUID storage + sanitize | same + `safeOriginalName` | Pass | Pass | REQ-7-C-005 |
| Quote verify IDOR | Opaque 48-char token; no numeric ID | `test_quote_verify_idor_wrong_token_and_rate_limit`, `QuotationTotalsAndWorkflowTest::test_verification_page_exposes_no_private_fields` | 404 on bad/ID; minimal fields | Pass | SEC |
| Quote verify rate limit | 30/min/IP | `test_quote_verify_idor_wrong_token_and_rate_limit` | 429 | Pass | SEC |
| XSS notes/branding | Escaped in verify Blade | `test_xss_in_company_name_escaped_on_verify_page` | Entities escaped | Pass | SEC |
| SQLi search/API filters | Bound parameters | `test_sql_injection_attempt_on_api_stage_filter_is_bound` | Empty result, no dump | Pass | SEC |
| Secrets in audit/message logs | Redacted | `AuditRedactionTest`, `test_secrets_never_appear_in_audit_or_message_logs`, messaging tests | `[REDACTED]` | Pass | SET-08 |
| Secrets in git / tree | No live keys | Manual `rg` + `git log -G` (V2) | Demo password only | Pass | REQ-7-C-005 |
| Login rate limit | Filament 5 attempts | Framework `Login::rateLimit(5)` (Filament built-in) | Present | Pass | SEC |
| API rate limit | 60/min | `throttle:api` + `AppServiceProvider` | Configured | Pass | API |

---

## Manual checks (for you)

Do **not** mark Pass on these until you confirm:

1. As `executive@solar-crm.test`, open a lead URL belonging to another executive by pasting `/admin/leads/{id}/edit` — expect 403/404 (not the record).
2. As `surveyor@solar-crm.test`, paste another surveyor’s `/admin/visits/{id}/edit` — expect denial.
3. Logged out, open `/quotes/verify/{valid_token}` — see totals only; try `/quotes/verify/{numeric_id}` — 404.
4. Confirm Settings page is unreachable / save forbidden when logged in as executive (SET-07 UI).
5. Optional: hammer `/quotes/verify/…` and confirm HTTP 429 after ~30/min from one IP.

---

## Secrets scan notes

- **Tracked `.env`:** none.
- **High-signal patterns** (AWS `AKIA…`, `sk_live_`, PEM private keys, `rf_` live tokens): **no matches** in app tree (excluding vendor) or recent git history (`git log -G`).
- **Demo password `Password1!`:** intentionally in README, demo-script, seeders, tests — local assignment only.

---

## Regression commands

```bash
php artisan migrate:fresh --seed
php artisan test --compact tests/Feature/Security/SecurityVerificationTest.php
php artisan test --compact
```

