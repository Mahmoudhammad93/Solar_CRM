# -*- coding: utf-8 -*-
#!/usr/bin/env python3
"""Generate atomic requirements-matrix.csv from the Rootflash assignment PDF (sections 1-13)."""
from __future__ import annotations

import csv
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs" / "requirements-matrix.csv"

HEADERS = [
    "ID",
    "PDF section",
    "text",
    "type",
    "module",
    "implementation",
    "dependencies",
    "acceptance test",
    "priority",
    "estimated hours",
    "simulation allowed",
    "status",
    "evidence",
]

rows: list[dict[str, str]] = []
counters: dict[str, int] = {}


def add(
    section: str,
    text: str,
    *,
    typ: str = "R",
    module: str,
    implementation: str = "",
    dependencies: str = "",
    acceptance: str = "",
    priority: str = "P0",
    hours: str = "1",
    sim: str = "no",
    status: str = "Not implemented",
    evidence: str = "",
    id_override: str | None = None,
) -> None:
    counters[section] = counters.get(section, 0) + 1
    rid = id_override or f"REQ-{section.replace('.', '-')}-{counters[section]:03d}"
    rows.append(
        {
            "ID": rid,
            "PDF section": section,
            "text": text.strip(),
            "type": typ,
            "module": module,
            "implementation": implementation,
            "dependencies": dependencies,
            "acceptance test": acceptance,
            "priority": priority,
            "estimated hours": hours,
            "simulation allowed": sim,
            "status": status,
            "evidence": evidence,
        }
    )


# ---------------------------------------------------------------------------
# §1 AI-only rules and submission checklist
# ---------------------------------------------------------------------------
add("1", "Claude AI must be the only AI coding assistant used to generate/modify/refactor/debug project code", module="Process", priority="P0", hours="0.25", acceptance="Evidence package")
add("1", "No manual coding of application source; use AI to generate/revise then run/test (IDE/terminal/Git/UI testing allowed)", module="Process", typ="R", priority="P0", hours="0.25")
add("1", "Maximum duration 30 hours from assignment receipt; stop at deadline and submit current state", module="Process", priority="P0", hours="0.5", acceptance="project-clock.md")
add("1", "Do not present incomplete or simulated features as fully functional", module="Process", priority="P0", hours="0.25")
add("1", "Keep Claude prompts/responses for architecture, generation, debugging, testing; provide exports/screenshots as evidence", module="Process", priority="P0", hours="1", acceptance="AT-13")
add("1", "Use fictional seed data only; no real customer data, passwords, API tokens, or company secrets in the repository", module="Security", priority="P0", hours="0.5", acceptance="AT-12")
add("1", "Store credentials in environment variables or secure server-side secret mechanism", module="Security", priority="P0", hours="0.5")
add("1", "Candidate must understand submitted code; unexplained AI code may count against assessment", module="Process", priority="P1", hours="0.5")
add("1", "Deliverable: runnable responsive web app with connected core workflows and demo credentials", module="Submission", priority="P0", hours="2", acceptance="AT-12")
add("1", "Deliverable: source repository/archive with migrations/schema, seed data, dependency/lock files, safe env sample", module="Submission", priority="P0", hours="1", acceptance="AT-12")
add("1", "Deliverable: README with setup, stack, architecture, DB setup, demo roles, test steps, deployment notes, known limitations", module="Submission", priority="P0", hours="2", acceptance="AT-12")
add("1", "Deliverable: full-project screen-recording (live app, major modules, workflows, settings, WhatsApp demo, calculators, exports, limitations)", module="Submission", priority="P0", hours="2", acceptance="AT-13", sim="cfg")
add("1", "Deliverable: Claude AI evidence (prompt history/export or screenshots) plus short validation/debugging note", module="Submission", priority="P0", hours="1", acceptance="AT-13")
add("1", "Deliverable: feature status sheet marking Fully working / Simulated / Partial / Not implemented", module="Submission", priority="P0", hours="0.5", acceptance="AT-12")
add("1", "Suggested recording structure: overview/stack (1-2m), lead?quote?project (4-6m), ops/inventory/tickets/permissions (3-5m), settings+limitations (3-5m)", module="Submission", typ="F", priority="P1", hours="0.5")
add("1", "Build original implementation; do not copy proprietary products, private repos, or third-party source code", module="Process", priority="P0", hours="0.25")

# ---------------------------------------------------------------------------
# §2 Product brief and workflow
# ---------------------------------------------------------------------------
add("2", "Centralized CRM connecting sales, field survey, hybrid sizing, quotations, execution, inventory, service, analytics, dynamic settings", module="Architecture", priority="P0", hours="1")
add("2", "PRIMARY WORKFLOW: Lead ? follow-up/qualification ? customer/site visit ? hybrid calculation ? quotation ? accepted quotation ? sales order/project ? materials/team allocation ? installation milestones ? commissioning/handover ? invoice/payment visibility ? service ticket/AMC", module="Workflow", priority="P0", hours="8", acceptance="AT-02..AT-09")
add("2", "Consistent navigation, clear page titles, search/filter, validation messages, empty/loading/error states, responsive forms", module="UI", priority="P0", hours="2")
add("2", "Linked records (not disconnected duplicates): lead/customer links to visits, calculations, quotations, orders/projects, documents, tickets", module="Data model", priority="P0", hours="2", acceptance="AT-07")
add("2", "Unique reference IDs for leads, visits, quotations, orders/projects, products, invoices, tickets, AMC contracts", module="Numbering", priority="P0", hours="2", acceptance="Numbering tests")
add("2", "Dashboard metrics and reports calculated from stored data; no decorative hard-coded KPIs", module="Dashboard", priority="P0", hours="2", acceptance="AT-10")
add("2", "Dynamic business configuration in Settings tab and database; changes affect UI/calculations/templates/exports/workflows without code edits where applicable", module="Settings", priority="P0", hours="4", acceptance="SET-01..SET-09")
add("2", "External services not integrated must be labeled Demo/Simulated and production setup documented", module="Integrations", priority="P0", hours="1", sim="yes", acceptance="SET-05")
add("2", "Role Super Admin: all modules, settings, users, roles/permissions, company config, audit logs, integrations", module="Auth", priority="P0", hours="1", acceptance="AT-01")
add("2", "Role Sales Manager: team leads, assignment, approvals, quotes, sales pipeline, team reports", module="Auth", priority="P0", hours="1", acceptance="AT-01")
add("2", "Role Sales Executive: assigned leads, follow-ups, visits, customers, calculators, quotations, own pipeline", module="Auth", priority="P0", hours="1", acceptance="AT-01")
add("2", "Role Surveyor/Engineer: assigned site visits, survey forms, GPS/site evidence, technical notes", module="Auth", priority="P0", hours="1", acceptance="AT-01")
add("2", "Role Project Manager/Operations: accepted orders, projects, milestones, materials, team allocation, installation completion", module="Auth", priority="P0", hours="1", acceptance="AT-01")
add("2", "Role Inventory/Finance/Service: stock, procurement, invoice/payment visibility, support tickets, AMC", module="Auth", priority="P0", hours="1", acceptance="AT-01")

# ---------------------------------------------------------------------------
# §3 Modules A-D
# ---------------------------------------------------------------------------
# A Dashboard
add("3.A", "Summary cards: new leads, follow-ups due/overdue, scheduled visits, quotations drafted/sent/accepted, active projects, open tickets", module="Dashboard", priority="P0", hours="2", acceptance="AT-10")
add("3.A", "Summary cards: recorded revenue and outstanding amounts when payment data exists", module="Dashboard", priority="P0", hours="1", acceptance="AT-10")
add("3.A", "Charts/lists: lead-stage pipeline, source mix, sales by team member, quote conversion, monthly revenue, project status", module="Dashboard", priority="P0", hours="2", acceptance="AT-10")
add("3.A", "Charts/lists: recent activity, upcoming tasks and notifications", module="Dashboard", priority="P1", hours="1")
add("3.A", "Filters: date range, branch, team member, lead source, status", module="Dashboard", priority="P0", hours="1", acceptance="AT-10")
add("3.A", "KPI cards open underlying filtered records", module="Dashboard", priority="P1", hours="1")
add("3.A", "Activity center: timestamped events, task reminders, overdue highlights, read/unread notifications, action history", module="Dashboard", priority="P1", hours="2")

# B Lead management
add("3.B", "Lead identity fields: auto lead ID, created date/time, lead title, customer type, full name/company, contact person, mobile, alternate phone, email", module="Leads", priority="P0", hours="2", acceptance="AT-02")
add("3.B", "Lead location fields: address, city, district, state, PIN/postal, GPS if available, branch/territory", module="Leads", priority="P0", hours="1", acceptance="AT-02", sim="cfg")
add("3.B", "Lead solar need fields: system type (hybrid, solar water pump, EV charging, other), property segment, roof/site type, avg monthly bill/units, sanctioned load, desired capacity, priority, budget range", module="Leads", priority="P0", hours="1.5", acceptance="AT-02")
add("3.B", "Lead sales tracking: source, campaign/source details, assigned owner, stage, loss reason, probability, expected value, notes, tags", module="Leads", priority="P0", hours="1.5", acceptance="AT-02")
add("3.B", "Lead follow-up/files: next follow-up, reminder, outcome, call/meeting notes, documents/photos, interaction history, created/updated by + timestamps", module="Leads", priority="P0", hours="2", acceptance="AT-02")
add("3.B", "Lead actions: add/edit/view, assign/reassign, search/filter/sort, duplicate check, follow-up scheduling, customer conversion", module="Leads", priority="P0", hours="2", acceptance="AT-02")
add("3.B", "Lead import/export where feasible", module="Leads", typ="F", priority="P2", hours="2", acceptance="AT-02")

# C Customer visit
add("3.C", "Visit core: Visit ID, linked lead/customer, schedule, assigned surveyor, status, site address, GPS, reschedule/cancellation reason, visit history", module="Visits", priority="P0", hours="2", acceptance="AT-03")
add("3.C", "Survey fields: property type, roof type/material, usable area, orientation/tilt, shading/obstructions, structural concerns, photos, electrical room/meter photos, site access, safety notes", module="Visits", priority="P0", hours="2", acceptance="AT-03")
add("3.C", "Utility details: bill/consumption evidence, monthly units, sanctioned load, single/three-phase, utility/DISCOM, meter/connection type, proposed hybrid system, tentative capacity", module="Visits", priority="P0", hours="1.5", acceptance="AT-03")
add("3.C", "Visit outcome: measurements, feasibility, customer needs, objections, feedback, recommended next step, preliminary BOM, downloadable visit report", module="Visits", priority="P0", hours="2", acceptance="AT-03")
add("3.C", "Map/GPS real only when configured; otherwise manual coordinates and demo-labeled map preview", module="Visits", priority="P0", hours="1", sim="yes", acceptance="AT-03")

# D Tasks/calendar
add("3.D", "Tasks linkable to leads, customers, quotes, visits, projects, tickets", module="Tasks", priority="P0", hours="1")
add("3.D", "Task fields: title, description, owner, due date/time, priority, status, reminder, outcome", module="Tasks", priority="P0", hours="1.5")
add("3.D", "Task list/calendar view, overdue and due-today views, completion timestamps, linked-record activity history", module="Tasks", priority="P1", hours="2")

# ---------------------------------------------------------------------------
# §4 Calculators
# ---------------------------------------------------------------------------
add("4", "Calculator must not return arbitrary/fixed results; show inputs, formulas/assumptions, intermediate totals, units, final estimate, printable/exportable report", module="Calculators", priority="P0", hours="2", acceptance="AT-04")
add("4", "Results labeled as indicative estimates, not engineering certification", module="Calculators", priority="P0", hours="0.25", acceptance="AT-04")
add("4", "Configurable formulas and documented assumptions; validate non-negative, units, efficiency ranges, zero denominators; state when detailed engineering validation required", module="Calculators", priority="P0", hours="2", acceptance="Hybrid validation test")

# Hybrid
add("4.A", "Hybrid load table: appliance name, quantity, rated watts, hours/day, usage pattern, critical/non-critical, optional surge factor; compute connected load, daily energy Wh/kWh, peak simultaneous load", module="HybridCalculator", priority="P0", hours="3", acceptance="AT-04")
add("4.A", "Hybrid solar/utility inputs: monthly units and/or bill, tariff/unit, usable roof area, proposed PV capacity, panel wattage, peak sun hours/generation assumption, performance ratio/loss factor, self-consumption, grid availability, export/net-metering where applicable", module="HybridCalculator", priority="P0", hours="2", acceptance="AT-04", sim="cfg")
add("4.A", "Hybrid battery/inverter inputs: backup duration target, system voltage, battery chemistry, nominal capacity, usable DoD, battery/inverter efficiency, inverter continuous/surge rating, charge-controller/inverter limits, optional generator input", module="HybridCalculator", priority="P0", hours="2", acceptance="AT-04")
add("4.A", "Hybrid financial inputs: panel/inverter/battery/BOS prices, GST, installation, transport, structure/civil, wiring/protection, engineering, other charges, O&M, editable subsidies/export rates where applicable", module="HybridCalculator", priority="P0", hours="2", acceptance="AT-04", sim="cfg")
add("4.A", "Hybrid outputs: recommended PV kWp and panel count, roof-area-constrained size if enabled, daily/annual energy, battery nominal/usable kWh/Ah, inverter rating, estimated backup time, solar contribution, grid/generator assumptions, self-consumed/exported units if configured, cost breakdown, total estimate, PDF report", module="HybridCalculator", priority="P0", hours="3", acceptance="AT-04")
add("4.A", "Display each selected input, unit and assumption beside results; do not hide battery/inverter rule path", module="HybridCalculator", priority="P0", hours="1", acceptance="AT-04")
add("4.A", "If real tariff/net-metering/subsidy used: show state/utility, source/reference, effective date; else mark Sample/Demo and editable in Settings", module="HybridCalculator", priority="P0", hours="1", sim="cfg", acceptance="SET-04")

# Investment
add("4.B", "Investment calculator inputs: upfront cost, grant/subsidy, optional loan amount/interest/tenure, estimated generation, self-consumption, export price, utility tariff, degradation, tariff escalation, annual maintenance, other recurring costs", module="InvestmentCalculator", priority="P0", hours="2", acceptance="AT-05", sim="cfg")
add("4.B", "Investment calculator outputs: net initial investment, monthly/annual savings/revenue, annual operating cost, net cash flow, payback, ROI for selected period, assumptions report", module="InvestmentCalculator", priority="P0", hours="2", acceptance="AT-05")
add("4.B", "Investment calculator NPV/IRR (bonus)", module="InvestmentCalculator", typ="B", priority="P2", hours="1.5", acceptance="AT-05")

# EV
add("4.C", "EV charging income inputs: charger type/power kW, number of points, sessions/day, avg kWh/session, operating days/month, selling price, electricity purchase cost, utilization, rent/staff/maintenance/other monthly costs, optional capital cost", module="EVCalculator", priority="P0", hours="2", acceptance="AT-05")
add("4.C", "EV charging outputs: energy sold, gross monthly/annual revenue, electricity cost, opex, estimated net profit, utilization assumptions, break-even and payback when capital provided", module="EVCalculator", priority="P0", hours="2", acceptance="AT-05")

# Calculator acceptance tests from PDF table
add("4", "Acceptance: changing appliance qty/hours, backup target, panel wattage, peak sun hours, battery DoD or efficiency changes hybrid outputs logically", module="HybridCalculator", priority="P0", hours="1", acceptance="AT-04", id_override="REQ-4-HYBRID-SIZING")
add("4", "Acceptance: changing tariff, generation/loss, self-consumption or export rate changes projected savings and payback", module="Calculators", priority="P0", hours="1", acceptance="AT-05", id_override="REQ-4-GEN-SAVINGS")
add("4", "Acceptance: changing product prices, GST, installation or transport updates line items and total; PDF matches on-screen results", module="HybridCalculator", priority="P0", hours="1", acceptance="AT-04", id_override="REQ-4-COST-EST")
add("4", "Acceptance: reject negative inputs, implausible efficiencies, invalid denominators, non-numeric inputs with understandable errors", module="Calculators", priority="P0", hours="1", acceptance="Validation tests", id_override="REQ-4-VALIDATION")

# ---------------------------------------------------------------------------
# §5 Quotations, catalogue, documents
# ---------------------------------------------------------------------------
add("5.A", "Quotation common fields: auto quotation number, revision, created/valid-until, customer/lead/site, owner, system type/capacity, status (Draft/Review/Sent/Accepted/Rejected/Expired), internal notes, customer terms, payment schedule", module="Quotations", priority="P0", hours="2", acceptance="AT-06")
add("5.A", "Quotation itemized lines: product/service, SKU, description/spec, qty, unit, unit price, discount, taxable value, GST rate/tax, line total, stock availability; support installation/civil/transport/accessories/AMC/other charges", module="Quotations", priority="P0", hours="3", acceptance="AT-06")
add("5.A", "Quotation totals: subtotal, discount, taxable value, configured CGST/SGST or IGST, round-off if used, grand total, advance, balance due; tax rates configurable (not hard-coded advice)", module="Quotations", priority="P0", hours="2", acceptance="AT-06")
add("5.A", "Hybrid proposal content: panel/inverter/battery/structure/protection/accessories, load and backup assumptions, scope, exclusions, commissioning, warranty, maintenance, validity", module="Quotations", priority="P0", hours="2", acceptance="AT-06")
add("5.A", "Solar water-pump proposal: pump model/capacity, head/discharge/water requirement, controller, panels, structure, accessories, installation, configured subsidy assumptions", module="Quotations", priority="P0", hours="2", acceptance="AT-06", sim="cfg")
add("5.A", "Quote output/share: branded PDF, print view, QR to verification/detail, digital signature area, activity trail, Email/WhatsApp share action", module="Quotations", priority="P0", hours="3", acceptance="AT-06", sim="cfg")
add("5.A", "Do not claim message sent unless provider confirms; demo actions must be labeled", module="Quotations", priority="P0", hours="0.5", sim="yes", acceptance="SET-05")
add("5.B", "Product master fields: ID/SKU, category, brand/model/title, description, technical specs, wattage/capacity, unit, purchase cost, sale price, GST, warranty, vendor, status, image, serial/batch tracking, available stock, reorder threshold", module="Catalogue", priority="P0", hours="2", acceptance="AT-08")
add("5.B", "Product categories cover panels, hybrid inverters, batteries, pumps, chargers, structures, wiring/protection, accessories, services where relevant", module="Catalogue", priority="P0", hours="1", acceptance="AT-08")
add("5.B", "Catalogue search, category/brand filters, sort, detail view, create/edit/archive, stock visibility; prices/stock from stored data", module="Catalogue", priority="P0", hours="1.5", acceptance="AT-08")
add("5.C", "Document attachments: bills, site photos, signed proposals, invoices, installation checklists, warranty docs, completion reports, customer/site records", module="Documents", priority="P0", hours="2", acceptance="AT-03")
add("5.C", "Store filename/type/date/uploader metadata, secure access rules, downloadable links; restrict sensitive docs by role", module="Documents", priority="P0", hours="2")
add("5.C", "Generate PDF quotes/proposals/reports with unique references and predictable filenames", module="Documents", priority="P0", hours="1.5", acceptance="AT-06")

# ---------------------------------------------------------------------------
# §6 Orders, projects, inventory
# ---------------------------------------------------------------------------
add("6.A", "Accepted quotation creates linked customer order/project without retyping core customer, line-item, amount or site details; preserve source quote and show relationship both ways", module="Orders", priority="P0", hours="2", acceptance="AT-07")
add("6.A", "Order fields: order ID/date, quote reference, customer/site, project type/capacity, contract value, confirmed scope, payment schedule, delivery target, documents, status, cancellation reason, owner", module="Orders", priority="P0", hours="2", acceptance="AT-07")
add("6.A", "Project fields: project ID, PM, assigned sales/engineering/installation team, planned/actual dates, priority, milestone, % complete, risks/blockers, materials required/allocated, customer update history", module="Projects", priority="P0", hours="2", acceptance="AT-07")
add("6.A", "Suggested milestones: Order Confirmed ? Documents Pending ? Site/Technical Approval ? Material Planning ? Material Ready ? Installation Scheduled ? Installation In Progress ? Inspection/Testing ? Commissioned/Handed Over ? Closed; support hold/exception with reason", module="Projects", priority="P0", hours="2", acceptance="AT-07")
add("6.A", "Installation record: crew, schedule, checklist, serial numbers where tracked, install photos, safety notes, testing/commissioning readings, customer sign-off, completion report, handover documents", module="Projects", priority="P0", hours="2", acceptance="AT-07", sim="cfg")
add("6.A", "Show timeline, milestone timestamps, stuck/overdue projects, completion reports, progress updates", module="Projects", priority="P0", hours="1.5", acceptance="AT-07")
add("6.B", "My Orders: separate Pending/Active/Completed/Cancelled views; search by order/customer/quote; status/date filters; detail, timeline, linked quote/project, related document downloads", module="Orders", priority="P0", hours="2", acceptance="AT-07")
add("6.B", "If customer portal exists, customers only view own projects; else internal-only and document boundary", module="Orders", typ="F", priority="P1", hours="1", acceptance="AT-07")
add("6.C", "Track available, reserved, allocated, issued, returned, damaged quantities; deduct/reserve only via explicit transactions; prevent negative stock unless protected admin setting permits", module="Inventory", priority="P0", hours="3", acceptance="AT-08")
add("6.C", "Link BOM/material requirements to projects/orders; show required vs available vs allocated and shortage warnings", module="Inventory", priority="P0", hours="2", acceptance="AT-08")
add("6.C", "Stock movement log: date, warehouse/branch, reference, quantity, user, reason; supplier/vendor name, contact, address, tax ID if applicable, status, notes", module="Inventory", priority="P0", hours="2", acceptance="AT-08")
add("6.C", "Low-stock alerts; material/product/order/project reports; reconciliation to stock transactions", module="Inventory", priority="P0", hours="1.5", acceptance="AT-08")
add("6.C", "Purchase requests/orders and goods-received workflow (bonus)", module="Inventory", typ="B", priority="P2", hours="3")

# ---------------------------------------------------------------------------
# §7 Tickets, reports, admin
# ---------------------------------------------------------------------------
add("7.A", "Ticket fields: Ticket ID, customer, linked project/order/product, category, issue, priority/source, opened date, assignee/team, status, target resolution date, resolution notes, parts/costs, feedback, timestamps", module="Tickets", priority="P0", hours="2", acceptance="AT-09")
add("7.A", "Ticket states: Open, Assigned, In Progress, Waiting for Customer/Parts, Resolved, Closed; assignment, comments, status history, search/filter, resolution report, closure", module="Tickets", priority="P0", hours="2", acceptance="AT-09")
add("7.A", "AMC record: contract number, customer/site, start/end, coverage, visit frequency, SLA, amount, next service date, status, renewal reminder; basic record acceptable if recurring automation incomplete", module="AMC", priority="P0", hours="1.5", acceptance="AT-09")
add("7.B", "Lead report: ID, source, owner, stage, creation date, follow-up, expected value, win/loss; date/source/status filters", module="Reports", priority="P0", hours="1", acceptance="AT-10")
add("7.B", "Customer report: profile, city/state, related leads, visits, quotes, projects/orders, tickets", module="Reports", priority="P0", hours="1", acceptance="AT-10")
add("7.B", "Quotation report: quote number, customer, date, subtotal/tax/total, status, owner, validity, conversion", module="Reports", priority="P0", hours="1", acceptance="AT-10")
add("7.B", "Sales/revenue report: period, team member, won deals, conversion, recorded revenue/payment; do not infer revenue without financial records", module="Reports", priority="P0", hours="1.5", acceptance="AT-10")
add("7.B", "Order/project report: status, system type/capacity, manager, milestone, planned vs actual, delays, completion", module="Reports", priority="P0", hours="1", acceptance="AT-10")
add("7.B", "Product/tax report: stock/low-stock and tax summary from stored quote/invoice records and configured rates", module="Reports", priority="P0", hours="1", acceptance="AT-10")
add("7.B", "Ticket/AMC report: open/overdue/resolved, time-to-resolution if available, coverage, upcoming renewals", module="Reports", priority="P0", hours="1", acceptance="AT-10")
add("7.B", "Monthly business report: leads, quote conversion, orders/projects, sales value, recorded payments/outstanding, service workload from real data", module="Reports", priority="P0", hours="1.5", acceptance="AT-10")
add("7.B", "Reports support date-range/status/owner filters, sorting, totals, page size; CSV/Excel-compatible exports and PDF; exported rows/totals match filtered view", module="Reports", priority="P0", hours="2", acceptance="AT-10")
add("7.C", "User management: name, email/login, phone, role, branch/team, active/inactive, last login if available; create/invite/reset/deactivate", module="Admin", priority="P0", hours="2", acceptance="AT-01")
add("7.C", "Role/permission management: restrict view/create/edit/delete/export for sensitive modules; demonstrate ?2 roles with different server-enforced permissions", module="Admin", priority="P0", hours="2", acceptance="AT-01")
add("7.C", "Audit logs for important actions: user, action, entity/reference, timestamp, change summary; never log passwords or raw secrets", module="Admin", priority="P0", hours="1.5", acceptance="SET-08")
add("7.C", "Backup/export plan and REST API approach; prefer one authenticated sample endpoint; full automated restore may be documented if outside time box", module="Admin", typ="F", priority="P1", hours="2", sim="cfg")
add("7.C", "Security: server-side checks, secure password hashing, validation, safe uploads, secrets in env/secure storage, no public access to private records", module="Security", priority="P0", hours="2", acceptance="AT-01")

# ---------------------------------------------------------------------------
# §8 Settings A-D
# ---------------------------------------------------------------------------
add("8", "ONE dedicated Settings tab/page; all listed business configuration editable by authorized admins, persist in DB, take effect without source changes; not hard-coded/decorative", module="Settings", priority="P0", hours="2", acceptance="SET-09")
add("8", "Settings organized into sub-tabs/cards with Save, Cancel, validation, confirmation for sensitive changes, success/error messages, permissions, audit trail", module="Settings", priority="P0", hours="2", acceptance="SET-08")
add("8", "Unimplemented settings clearly marked unavailable rather than shown as working", module="Settings", priority="P0", hours="0.5")

add("8.A", "Editable app/website display name, organization/company name, logo, favicon, login-page logo, sidebar/header branding, footer text, theme/accent color if supported", module="Settings-Branding", priority="P0", hours="2", acceptance="SET-01")
add("8.A", "Company details: legal name, display name, address, phone, support email, website, GSTIN/tax IDs, invoice address, other contacts used in PDFs", module="Settings-Branding", priority="P0", hours="1", acceptance="SET-01")
add("8.A", "Logo/name changes appear in navigation, login where applicable, quotation PDFs, report headers, generated documents; clarify this is branding not DNS", module="Settings-Branding", priority="P0", hours="1", acceptance="SET-01")
add("8.A", "Secure logo upload with type/size validation, preview, replace, remove/reset-to-default", module="Settings-Branding", priority="P0", hours="1", acceptance="SET-01")

add("8.B", "Signed-in user change password with current, new, confirmation; show policy, mismatch/weak errors, success", module="Settings-Security", priority="P0", hours="1", acceptance="SET-02")
add("8.B", "Admin-managed password reset/invitation, user activation/deactivation, session expiry/idle timeout and password policy where feasible", module="Settings-Security", typ="F", priority="P1", hours="2", acceptance="SET-02")
add("8.B", "Never display/recover plaintext passwords; hash securely; do not log credentials; no secrets in browser code or Git", module="Settings-Security", priority="P0", hours="0.5", acceptance="SET-02")
add("8.B", "Optional: 2FA, login lockout, session/device list, security notification settings", module="Settings-Security", typ="F", priority="P2", hours="3")

add("8.C", "Configure roles, module access and actions (view/create/edit/delete/export/approve/settings); protect settings by permission not only hidden nav", module="Settings-RBAC", priority="P0", hours="2", acceptance="SET-07")
add("8.C", "Configure branch/warehouse list, territory/region, assignment rules, team names, access scope", module="Settings-RBAC", priority="P0", hours="1.5", acceptance="SET-03")
add("8.C", "Configure company/fiscal-year details and numbering sequences/prefixes for leads, quotes, orders, invoices, projects, tickets, AMC", module="Settings-Numbering", priority="P0", hours="2")

add("8.D", "Add/edit/deactivate lead sources, lead stages, customer types, property types, product categories, units, priorities, ticket types/statuses, loss reasons, task types, document types, notification categories", module="Settings-Lists", priority="P0", hours="3", acceptance="SET-03")
add("8.D", "Where feasible: admin-defined custom fields (text/number/date/dropdown/checkbox) with label, required, default, validation, visibility, module placement", module="Settings-Lists", typ="F", priority="P2", hours="4")
add("8.D", "Configure quotation statuses, project milestones, ticket workflow, AMC statuses and allowed transitions; new values appear in forms/filters/reports", module="Settings-Workflow", priority="P0", hours="2", acceptance="SET-03")
add("8.D", "Configure table columns and defaults where feasible; deactivating config must not break existing records (preserve old values or migration/deactivation behavior)", module="Settings-Lists", typ="F", priority="P1", hours="2")

# ---------------------------------------------------------------------------
# §9 Settings E-I
# ---------------------------------------------------------------------------
add("9.E", "Hybrid calculator defaults editable: peak sun hours, PR/losses, panel wattage, battery chemistry, usable DoD, battery/inverter efficiency, design margin, voltage options, export assumptions, currency/unit preferences", module="Settings-Calculator", priority="P0", hours="2", acceptance="SET-04")
add("9.E", "Editable rates/assumptions: electricity tariffs, export/net-metering prices, O&M defaults, component prices, installation/transport defaults, tax/GST, discount limits, optional subsidy rules", module="Settings-Calculator", priority="P0", hours="2", acceptance="SET-04", sim="cfg")
add("9.E", "Each config keeps value, unit, effective date, status, source/reference, updated-by; real policy assumptions capture state/utility and source/date else Sample/Demo", module="Settings-Calculator", priority="P0", hours="1.5", acceptance="SET-04", sim="cfg")
add("9.E", "Calculator setting changes affect new calculations/reports; previously saved quotes/calculations retain assumption snapshot or clearly show version used", module="Settings-Calculator", priority="P0", hours="2", acceptance="Snapshot immutability test")

add("9.F", "Product/inventory settings: category, brand, warranty defaults, units, tax rate, price defaults, serial/batch toggle, reorder threshold, warehouse/branch, reservation behavior, negative-stock policy", module="Settings-Inventory", priority="P0", hours="2", acceptance="AT-08")
add("9.F", "Configure allowed product types and component attributes for panels, hybrid inverters, batteries, pumps, EV chargers, structures, accessories", module="Settings-Inventory", priority="P0", hours="1.5")

add("9.G", "Configure numbering prefixes and sequence behavior for quote/invoice/order/project/ticket/AMC; validity days; payment terms; warranty/maintenance text; default notes/terms", module="Settings-Templates", priority="P0", hours="2")
add("9.G", "Editable branded templates for quotation PDF, hybrid calculator report, site-visit report, installation/commissioning report, invoice/receipt, ticket resolution report, customer communications", module="Settings-Templates", priority="P0", hours="3", acceptance="AT-06")
add("9.G", "Enable/disable QR code, signature block, footer/contact block, logo display, PDF/CSV/Excel export options where supported", module="Settings-Templates", priority="P0", hours="1", sim="cfg")

add("9.H", "WhatsApp settings page: provider/mode (Demo or configured), business phone/sender, business/account IDs, approved template names; use provider/API version required by selected integration", module="Settings-WhatsApp", priority="P0", hours="2", sim="yes", acceptance="SET-05")
add("9.H", "Secure server-side credential configuration, test-connection, connection status, webhook/callback if implemented; clear message when credentials missing; never expose permanent tokens in front-end or ordinary logs", module="Settings-WhatsApp", priority="P0", hours="2", sim="cfg", acceptance="SET-06")
add("9.H", "Editable message templates with safe placeholders, preview, validation for: lead assignment, follow-up reminder, site visit confirmation, quotation ready/shared, quote accepted, project milestone, invoice/payment, ticket status, AMC renewal", module="Settings-WhatsApp", priority="P0", hours="2", sim="yes", acceptance="SET-05")
add("9.H", "WhatsApp actions: share quotation PDF/link, share calculator/report link, send approved template, open customer conversation; if not configured run labelled demo mode and do not claim delivery", module="Settings-WhatsApp", priority="P0", hours="2", sim="yes", acceptance="SET-05")
add("9.H", "Store message history: linked record, recipient, template, time, initiating user, status (queued/sent/delivered/failed when provider supports), provider response/error, retry where appropriate", module="Settings-WhatsApp", priority="P0", hours="1.5", sim="yes", acceptance="SET-05")
add("9.H", "Respect customer consent/opt-in and opt-out; validate phone numbers with country code; safe errors for invalid numbers, missing variables, API errors", module="Settings-WhatsApp", priority="P0", hours="1.5", sim="cfg")

add("9.I", "Configurable SMTP/provider mode and sender display name/address; server-side secret storage; test email action only if implemented", module="Settings-Email", priority="P0", hours="1.5", sim="yes", acceptance="SET-06")
add("9.I", "Editable email templates for quotes, visits, project updates, ticket updates, invoices, AMC reminders", module="Settings-Email", priority="P0", hours="1.5", sim="yes")
add("9.I", "Optional SMS/push channels may be documented if not implemented", module="Settings-Email", typ="F", priority="P2", hours="0.5", sim="yes")
add("9.I", "Configure reminders, lead follow-up intervals, task due reminders, notification channels, working hours, ticket/project SLA settings", module="Settings-Notifications", priority="P0", hours="2")

# ---------------------------------------------------------------------------
# §10 Settings J + SET tests + other features
# ---------------------------------------------------------------------------
add("10.J", "Locale, timezone, date/time format, currency symbol/precision, measurement units, rounding behavior, default country calling code", module="Settings-Regional", priority="P0", hours="1.5")
add("10.J", "Tax rules/GST rates, company fiscal year, financial report parameters editable by authorized users, validated, audit logged", module="Settings-Finance", priority="P0", hours="1.5", acceptance="SET-08")
add("10.J", "REST API configuration: enabled/disabled, API client/key metadata and rotation/revocation if implemented; never reveal complete secret after saving", module="Settings-API", typ="F", priority="P1", hours="2", sim="cfg")
add("10.J", "Map/GPS, signature, payment, backup and external-data integration status show Configured / Not configured / Demo; secrets server-side", module="Settings-Integrations", priority="P0", hours="1", sim="yes")
add("10.J", "Configure data-export formats, backup reminder/schedule, retention preferences, audit-log filter settings where feasible; dangerous restore/delete need confirmation and authorization", module="Settings-System", typ="F", priority="P1", hours="1.5", sim="cfg")

# SET-01..09
add("10", "SET-01 Change app/website display name and logo ? branding changes in nav/login and generated quotation/report PDFs; refresh does not revert", module="Settings", priority="P0", hours="0.5", acceptance="SET-01", id_override="SET-01")
add("10", "SET-02 Change own password ? current validated; new/confirm mismatch rejected; new works and old fails", module="Settings", priority="P0", hours="0.5", acceptance="SET-02", id_override="SET-02")
add("10", "SET-03 Change dropdown/workflow value ? new lead source/stage in forms/filters; changed project/ticket workflow reflected on screens", module="Settings", priority="P0", hours="0.5", acceptance="SET-03", id_override="SET-03")
add("10", "SET-04 Change hybrid defaults ? changing PSH, battery DoD, loss factor or panel wattage changes results and shows selected assumptions", module="Settings", priority="P0", hours="0.5", acceptance="SET-04", id_override="SET-04")
add("10", "SET-05 Configure WhatsApp in Demo mode ? template preview works, demo visibly marked simulated, no false sent/delivered claim", module="Settings-WhatsApp", priority="P0", hours="0.5", sim="yes", acceptance="SET-05", id_override="SET-05")
add("10", "SET-06 Missing WhatsApp credentials ? useful setup message; never expose or request credentials in public/client-side field", module="Settings-WhatsApp", priority="P0", hours="0.5", sim="cfg", acceptance="SET-06", id_override="SET-06")
add("10", "SET-07 Role restrictions ? non-admin cannot save protected settings by UI or direct HTTP/API", module="Settings", priority="P0", hours="0.5", acceptance="SET-07", id_override="SET-07")
add("10", "SET-08 Audit history ? settings changes record user, timestamp, category, safe summary; secrets/passwords not logged", module="Settings", priority="P0", hours="0.5", acceptance="SET-08", id_override="SET-08")
add("10", "SET-09 Persistence ? saved settings remain after logout/login and server restart using DB-backed configuration", module="Settings", priority="P0", hours="0.5", acceptance="SET-09", id_override="SET-09")

add("10", "Responsive desktop/tablet/mobile design, accessible labels, search/filter/sort/pagination, sensible error states", module="UI", priority="P0", hours="2")
add("10", "PDF and CSV/Excel-compatible exports, quote QR code, signature capture/display area, document management, GPS/site photos, notifications, audit trail, REST API integration point or documented API design", module="Cross-cutting", priority="P0", hours="3", sim="cfg")
add("10", "Email/WhatsApp live delivery, live maps, external subsidy APIs, electronic signatures, payment gateway, production backups may be simulated only when labelled clearly and described in README", module="Integrations", priority="P0", hours="1", sim="yes")

# ---------------------------------------------------------------------------
# §11 AT-01..13 + technical expectations
# ---------------------------------------------------------------------------
add("11", "AT-01 Log in with demo credentials; demonstrate ?2 roles with different server-enforced permissions", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-01", id_override="AT-01")
add("11", "AT-02 Create lead, assign, add follow-up/customer note, find via search/filter", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-02", id_override="AT-02")
add("11", "AT-03 Schedule and complete site visit with survey fields, photo/document support, visit history/report", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-03", id_override="AT-03")
add("11", "AT-04 Run hybrid calculator; show changed inputs, units, assumptions, formula/intermediates, changing outputs; export report", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-04", id_override="AT-04")
add("11", "AT-05 Run solar investment and EV income calculators; verify input changes affect results logically", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-05", id_override="AT-05")
add("11", "AT-06 Create itemized hybrid and solar water-pump quotations, calculate configured taxes/totals, generate PDF, change quote status", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-06", id_override="AT-06")
add("11", "AT-07 Accept quotation and convert to linked order/project without losing key data; update milestones and show progress", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-07", id_override="AT-07")
add("11", "AT-08 View catalogue/stock, link required materials to a project, show stock shortage/low-stock information", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-08", id_override="AT-08")
add("11", "AT-09 Create, assign, update and close ticket linked to customer/project; show AMC record or renewal workflow", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-09", id_override="AT-09")
add("11", "AT-10 Dashboard KPIs and core reports reflect stored records; export data and verify filters/totals", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-10", id_override="AT-10")
add("11", "AT-11 Demonstrate dynamic settings: app name/logo, password change, new dropdown/workflow, calculator assumptions, WhatsApp Demo/configured mode", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-11", id_override="AT-11")
add("11", "AT-12 README setup works; seed data/demo login exist; no secrets committed; known limitations listed", module="Acceptance", priority="P0", hours="0.5", acceptance="AT-12", id_override="AT-12")
add("11", "AT-13 Submit full-project recording and Claude-only evidence within the 30-hour limit", module="Acceptance", priority="P0", hours="1", acceptance="AT-13", id_override="AT-13")

add("11", "Related screens update consistently from one stored source of truth; avoid duplicate copies of same business setting across unrelated forms", module="Architecture", priority="P0", hours="1")
add("11", "Show loading/success/failure feedback; validate input client and server where relevant; handle empty states", module="UI", priority="P0", hours="1")
add("11", "Integrations expose real status only when real provider/API response supports it; local success toast is not proof of WhatsApp/email delivery", module="Integrations", priority="P0", hours="0.5", sim="yes")
add("11", "Store snapshot of quote/calculation values so editing current settings does not silently alter previously accepted commercial documents", module="Snapshots", priority="P0", hours="2", acceptance="Snapshot immutability test")

# ---------------------------------------------------------------------------
# §12 Submission + interview-driven expectations
# ---------------------------------------------------------------------------
add("12", "Submit working URL or local run steps, demo credentials, seed data, core flows accessible", module="Submission", priority="P0", hours="1", acceptance="AT-12")
add("12", "Submit source repo/archive, migrations/schema, env sample without secrets, dependency/lock files", module="Submission", priority="P0", hours="0.5", acceptance="AT-12")
add("12", "Submit README covering setup, stack, architecture, modules, database, demo accounts/roles, tests, deployment, limitations, future work", module="Submission", priority="P0", hours="2", acceptance="AT-12")
add("12", "Submit one complete walkthrough recording preferably 12-20 minutes of live running application with narration", module="Submission", priority="P0", hours="2", acceptance="AT-13")
add("12", "Submit Claude evidence: prompt log/export or screenshots showing Claude-only coding; explain how generated code was verified", module="Submission", priority="P0", hours="1", acceptance="AT-13")
add("12", "Submit status sheet marking every feature Fully working / Simulated / Partial / Not implemented and list bugs/dependencies", module="Submission", priority="P0", hours="0.5", acceptance="AT-12")

add("12", "Interview: explain architecture, data model/relationships, authentication, roles and permission enforcement", module="Interview", priority="P1", hours="0.5")
add("12", "Interview: walk lead?survey?hybrid calc?quote?order/project?materials?installation?ticket/service; explain linked record save/validation", module="Interview", priority="P1", hours="0.5")
add("12", "Interview: explain hybrid sizing formula assumptions and units; predict results when interviewer changes appliance usage, backup target, PV generation, battery DoD, or costs", module="Interview", priority="P0", hours="1", acceptance="AT-04")
add("12", "Interview: explain architecture choice, prompting approach, how incorrect generated code was identified/debugged/tested", module="Interview", priority="P1", hours="0.5")
add("12", "Interview: demonstrate dynamic settings and how changes propagate to forms, calculations, documents, reports without manual code changes", module="Interview", priority="P0", hours="0.5", acceptance="AT-11")
add("12", "Interview: explain WhatsApp configuration, approved templates, consent/opt-out, demo vs real sending, delivery-status limitations, secret handling", module="Interview", priority="P0", hours="0.5", sim="yes", acceptance="SET-05")
add("12", "Interview: answer questions on validation, error handling, security, REST APIs, indexing, performance, auditability, backups, deployment, known limitations", module="Interview", priority="P1", hours="0.5")
add("12", "Interview readiness: make a small live change using Claude AI, explain the diff, show the test result", module="Interview", priority="P1", hours="0.5")

# ---------------------------------------------------------------------------
# §13 Assumptions
# ---------------------------------------------------------------------------
add("13", "Separate off-grid and on-grid calculator specs are replaced by one hybrid solar calculator; investment and EV calculators remain in scope", module="Calculators", priority="P0", hours="0.25")
add("13", "Public feature references are for research only; must not copy proprietary code or branding", module="Process", priority="P0", hours="0.25")
add("13", "Subsidy, net metering, tariffs, GST treatment, sizing and financial outputs depend on jurisdiction/utility/product/project; sample values labeled Demo/Sample", module="Calculators", priority="P0", hours="0.5", sim="cfg")
add("13", "Real policy values must include source, applicable state/utility and effective date, and remain configurable", module="Settings-Calculator", priority="P0", hours="1", sim="cfg")
add("13", "Changing global settings must not silently rewrite historical quotes/reports already issued; preserve snapshot/version or show version used", module="Snapshots", priority="P0", hours="1", acceptance="Snapshot immutability test")
add("13", "A complete working core flow is more valuable than many decorative screens; disclose unfinished/simulated features honestly", module="Process", priority="P0", hours="0.25")

# Master-prompt architecture expectations not always explicit as bullets but required by assignment workflow
add("MP", "Laravel + Filament + MySQL single application with one consistent responsive UI", module="Architecture", priority="P0", hours="2", id_override="REQ-MP-STACK")
add("MP", "Policies for authorization + query scoping (record-level) on resources/pages/actions/downloads/exports/API; hiding nav insufficient", module="Auth", priority="P0", hours="3", acceptance="Authorization tests", id_override="REQ-MP-AUTH-SCOPE")
add("MP", "NumberingService using sequences table, row locking in transaction, unique DB indexes, prefixes/format from Settings; concurrent-safe", module="Numbering", priority="P0", hours="2", acceptance="Numbering concurrency test", id_override="REQ-MP-NUMBERING")
add("MP", "Typed SettingsService with safe cache invalidation on save, versioning/effective-date metadata where required, audit with secrets redacted", module="Settings", priority="P0", hours="2", acceptance="SET-08/SET-09", id_override="REQ-MP-SETTINGS-SVC")
add("MP", "Secrets (SMTP, WhatsApp tokens, API keys) in environment/server-side only; UI shows Configured/Not configured only", module="Security", priority="P0", hours="1", acceptance="SET-06", id_override="REQ-MP-SECRETS-UI")
add("MP", "Calculation runs and quotation revisions store full JSON snapshots; issued snapshots never rewritten; edits to sent quotation create new revision", module="Snapshots", priority="P0", hours="2", acceptance="Snapshot immutability test", id_override="REQ-MP-SNAPSHOTS")
add("MP", "Calculations server-side only with explicit units and shown intermediates; document additional assumptions in docs/calculation-spec.md where PDF silent", module="Calculators", priority="P0", hours="1", id_override="REQ-MP-CALC-SPEC")
add("MP", "Private disk storage, randomized stored names, MIME/extension/size validation by content, authorized download controller, role-restricted document types", module="Documents", priority="P0", hours="2", id_override="REQ-MP-FILES")
add("MP", "Quote QR verification page may expose only minimal configurable fields via unguessable token", module="Quotations", priority="P0", hours="1", acceptance="AT-06", id_override="REQ-MP-QR")
add("MP", "External services behind interfaces with Demo drivers; never send real messages or call paid services by default", module="Integrations", priority="P0", hours="2", sim="yes", id_override="REQ-MP-DEMO-DRIVERS")
add("MP", "Conversion quotation?project must be idempotent (no duplicate projects)", module="Orders", priority="P0", hours="1", acceptance="Conversion idempotency test", id_override="REQ-MP-CONVERT-IDEM")
add("MP", "Required automated tests: calculation hand-computed, authorization, numbering concurrency (MySQL), snapshot immutability, conversion idempotency, stock/negative-stock, settings-permission", module="Tests", priority="P0", hours="4", id_override="REQ-MP-TESTS")

# Write CSV
OUT.parent.mkdir(parents=True, exist_ok=True)
with OUT.open("w", newline="", encoding="utf-8") as f:
    w = csv.DictWriter(f, fieldnames=HEADERS, quoting=csv.QUOTE_MINIMAL)
    w.writeheader()
    w.writerows(rows)

# Section counts
from collections import Counter

sec = Counter(r["PDF section"] for r in rows)
print(f"TOTAL_ROWS={len(rows)}")
print(f"CSV_PATH={OUT}")
for k in sorted(sec.keys(), key=lambda x: (x.replace("MP", "99"), x)):
    print(f"SECTION\t{k}\t{sec[k]}")
