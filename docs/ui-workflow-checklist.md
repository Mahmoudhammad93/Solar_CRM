# UI & Workflow Checklist (Validation V4)

**Generated:** `2026-10-23 17:46:15 EEST` (shell `date`)  
**Method:** Browser automation (Cursor IDE browser + CDP viewports 375 / 768 / 1280) against `http://127.0.0.1:8000` as `admin@solar-crm.test`, plus DB-linked workflow verification on seeded demo.  
**Scope:** PDF §2 quality expectations, §10 responsive.  
**Rule:** Fix only blocking defects with minimal diffs (no redesign).

**Status legend:** Pass · Partial · Fail · Manual (await your confirmation)

---

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Navigation consistency | Pass | Grouped Filament sidebar; brand link; Skip to content |
| Page titles / H1 | Pass | Document title + H1 match module on checked screens |
| Search / filter / sort / pagination | Pass | Leads/Quotations tables: search, filters, column sort, per-page, Next |
| Validation messages | Pass | Login wrong password shows server message; forms use required labels |
| Empty / loading / error | Partial | Loading via Livewire disabled controls on submit; empty states Filament default (not exhaustively exercised) |
| Accessible labels | Pass | Login, hybrid load rows, settings fields expose accessible names |
| Keyboard / focus | Partial | Focus ring visible on login email; Skip to content present; full keyboard tour not fully automated |
| Responsive 375 / 768 / 1280 | Pass | No document-level overflow; tables use `overflow-x: auto` (`fi-ta-content-ctn`); mobile Expand sidebar |
| Connected workflow links | Pass | Golden path on **Demo Lead 3** after V4 seeder alignment (see below) |

**Blocking defects at any width:** none found.  
**Code fix this validation:** demo seed golden-path alignment only (`DemoScenarioSeeder`).

---

## Screen matrix (responsive + UX)

Checked with CDP `Emulation.setDeviceMetricsOverride` at **375**, **768**, and **1280** unless noted.

| Screen | URL | Title / H1 | Nav | Search/filter | Labels | 375 | 768 | 1280 | Notes |
|--------|-----|------------|-----|---------------|--------|-----|-----|------|-------|
| Login | `/admin/login` | Login / Sign in | N/A | N/A | Pass | Pass | Pass | Pass | Validation error shown for bad credentials |
| Dashboard | `/admin` | Dashboard | Pass | Filters region | Pass | Pass | Pass | Pass | KPI cards + filter form |
| Leads list | `/admin/leads` | Leads | Pass | Search, Filter, sort, pagination | Pass | Pass* | Pass | Pass | *table scrolls horizontally inside card |
| Lead form | `/admin/leads/{id}/edit` | Edit lead | Pass | — | Pass | Pass | Pass | Pass | Spot-checked structure |
| Visit form | `/admin/visits/{id}/edit` | Edit visit | Pass | — | Pass | Pass | Pass | Pass | |
| Hybrid calculator | `/admin/hybrid-calculator` | Hybrid solar calculator | Pass | Lead/Customer/Visit links | Pass | Pass | Pass | Pass | Load table rows labeled (Name/Qty/W/Hours…) |
| Quotation list/editor | `/admin/quotations`, `…/edit` | Quotations / Edit | Pass | Search/filter | Pass | Pass | Pass | Pass | |
| Project detail | `/admin/projects/1/edit` | Edit PRJ-… | Pass | Tabs: Milestones / Materials / Installation | Pass | Pass | Pass | Pass | Timeline via Milestones tab |
| Inventory (products / low stock) | `/admin/products`, `/admin/low-stock-products` | Products / Low stock | Pass | Table search | Pass | Pass | Pass | Pass | |
| Ticket | `/admin/tickets/{id}/edit` | Edit ticket | Pass | — | Pass | Pass | Pass | Pass | |
| Settings tabs | `/admin/manage-settings` | Settings | Pass | Tablist Branding…API | Pass | Pass | Pass | Pass | Many labeled fields |

\* Wide Filament tables intentionally clip/scroll inside `.fi-ta-content-ctn` (`overflow-x: auto`); document `scrollWidth` does not exceed viewport.

---

## Cross-cutting checks

| ID | Check | Result | Evidence |
|----|-------|--------|----------|
| UX-01 | Sidebar groups stable across pages | Pass | Sales / Field / Calculators / Administration consistently present |
| UX-02 | Brand “Solar CRM” in topbar | Pass | Snapshot + screenshot |
| UX-03 | Skip to content link | Pass | First focusable control in a11y tree |
| UX-04 | Global search present | Pass | `Global search` searchbox |
| UX-05 | Login validation (server) | Pass | “These credentials do not match our records.” |
| UX-06 | Required field indicators | Pass | `*` on login, hybrid loads, settings |
| UX-07 | Mobile sidebar collapse | Pass | “Expand sidebar” at 375/768 |
| UX-08 | No horizontal page overflow | Pass | `documentElement.scrollWidth − innerWidth === 0` at 375 on Leads |
| UX-09 | Table internal scroll | Pass | `.fi-ta-content-ctn` scrollWidth ≫ clientWidth, overflow-x auto |
| UX-10 | Hybrid load table usable | Pass | Add load / Move / Delete / Critical switch labeled |
| UX-11 | Settings tab navigation | Pass | Branding selected; Security, Numbering, Calculators, … API tabs present |
| UX-12 | Dark theme contrast | Pass | Orange primary CTA on dark chrome (visual) |

---

## Connected workflow (Lead → … → AMC)

**Golden path (after V4 seed fix):** start at **Demo Lead 3** (`LEAD-2026-0003`).

| Step | Record | Link verification |
|------|--------|-------------------|
| Lead | `LEAD-2026-0003` | Base |
| Follow-up | Lead list / tasks | Demo follow-ups exist on leads 1–2; Lead 3 used for conversion path |
| Visit | `VIS-2026-0003` | `lead_id` = Lead 3 |
| Hybrid calc | `CAL-2026-0001` | `lead_id` = Lead 3; also `visit_id` set in seed |
| Quote | `QUO-2026-0005` accepted | `lead_id` = Lead 3; `calculation_run_id` = 1 |
| Order | `ORD-2026-0001` | `quotation_id` → quote |
| Project | `PRJ-2026-0001` | `order_id` → order; **10** milestones; **3** materials |
| Invoice / payment | `INV-2026-0001` | `order_id` → order (partial payments in seed) |
| Ticket | Ticket #1 | `project_id` = 1; `customer_id` = 1 (same customer as order) |
| AMC | AMC #1 | `project_id` = 1; `customer_id` = 1 |

**UI walk (admin):** Leads → open Lead 3 → Visits (`VIS-2026-0003`) → Hybrid (link Lead 3) → Quotations (`QUO-2026-0005`) → My Orders (`ORD-2026-0001`) → Projects (`PRJ-2026-0001` milestones/materials) → Invoices → Tickets → AMC.

**Pre-fix gap (recorded):** first accepted quote previously used Lead 9 without visit/calc; tickets/AMC already targeted customer 0 / project 0 — **fixed** by pointing the first accepted hybrid at Lead 3 / customer 0.

---

## Defects

| ID | Severity | Finding | Disposition |
|----|----------|---------|-------------|
| UI-V4-01 | Medium (demo) | Seeded accepted-quote path not on same lead as visit/calc | **Fixed** in `DemoScenarioSeeder` (golden path Lead 3) |
| UI-V4-02 | Low | Empty-state / full keyboard tour not fully automated | Partial — Filament defaults; manual optional |
| UI-V4-03 | Info | Wide tables require horizontal scroll on phone | Accepted Filament pattern (not a block) |

No CSS/layout code changes required (no blocking responsive defects).

---

## Screenshots you should capture (evaluator pack)

1. Login @ 375 and 1280  
2. Dashboard KPIs @ 1280  
3. Leads list @ 375 (show horizontal table scroll) and @ 1280  
4. Hybrid calculator load table @ 768  
5. Quotation editor accepted quote @ 1280  
6. Project edit with Milestones tab @ 1280  
7. Settings Branding tab @ 1280  
8. Optional: same Settings tab @ 375 (tab strip scroll)

Save under something like `docs/ai-evidence/screenshots/v4-*.png` if packaging evidence.

---

## Manual confirmation (optional — reply to promote Partials)

1. Tab through login → Sign in with keyboard only.  
2. Open Lead create, clear Name, Save → confirm inline validation.  
3. Confirm project Materials tab shows shortage line on PRJ-2026-0001.  
4. Confirm ticket #1 and AMC #1 appear related when opened from Service menu.

---

## Commands

```bash
php artisan migrate:fresh --seed   # ensures golden path
# App: http://127.0.0.1:8000/admin/login  admin@solar-crm.test / Password1!
# Golden: LEAD-2026-0003 → VIS-2026-0003 → CAL-2026-0001 → QUO-2026-0005 → ORD/PRJ/INV → Ticket/AMC
```
