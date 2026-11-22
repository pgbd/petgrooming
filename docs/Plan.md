# Pet Grooming BD — Plan (D6)

Status: **Draft for G0 review.** Built from the development plan (sections 5 to 11) and from [PRD.md](PRD.md), [AppFlow.md](AppFlow.md), [UI_Spec.md](UI_Spec.md), [TRD.md](TRD.md) and [Schema.md](Schema.md).

This is an order, not a schedule. The plan gives sequence and dependencies only; it carries no dates or effort estimates, because real velocity is measured after M1. No code starts before G0.

Task columns: ID, task, depends on, verify step, gates. A dependency always points to an earlier row.

Traceability to OWN-001…010 and R01…R14 is **pending**: both lists come from the Architecture Revision Note, which was not available. The 20 gates are mapped below.

## M0 — Docs, decisions and proof

| ID | Task | Depends on | Verify | Gates |
|---|---|---|---|---|
| A0 | Repo and CI skeleton: docs, CLAUDE.md, config files, lint and tests green on an empty app | — | CI green on a PR; skeleton test passes | — |
| D1 | docs/PRD.md | A0 | Owner baseline recorded; unset values marked Unset | — |
| D2 | docs/AppFlow.md | D1 | Flow and state machines reviewed against the plan | — |
| D3 | docs/UI_Spec.md | D1, D2 | Four screens each have seven states | — |
| D4 | docs/TRD.md | A0 | Rules match CLAUDE.md; nothing unproven pinned | — |
| D5 | docs/Schema.md | D1, D2 | Every entity tagged with a milestone | — |
| D6 | docs/Plan.md | D1, D2, D3, D4, D5 | Every gate covered; contradictions listed | — |
| REV-1 | Traceability review: a reviewer agent maps OWN ids, R ids and gates to documents and tasks | D6 | Matrix has no unmapped item | — |
| G0 | Gate: Vaiya and the owner approve the documents | REV-1 | Written approval; no code before this | — |
| S-0 | Spike: Hermes handoff, Discord to a Claude worker | G0 | One small task runs end to end; blockers recorded | — |
| S-1 | Spike: stack install, versions decided and then pinned; MariaDB versions compared on both machines | G0 | Lockfile committed; no constraint conflict; panel login works in both places | — |
| S-2 | Spike: two parallel confirms on one seeded resource, on real MariaDB | S-1 | Exactly one succeeds; the other gets a clean conflict; deadlock retry works | 1 |
| S-3 | Spike: WITHOUT OVERLAPS on a scratch table | S-1 | Findings written; never the only guard | — |
| S-4 | Spike: Bengali PDF with mPDF on the target printer | S-1 | Shaping, pagination and QR verified; fallback library chosen | — |
| S-5 | Spike: response cache and CSRF with two browser sessions | S-1 | No cookie leakage; bypass plan written | 9 |
| S-6 | Spike: API slice, branches list and an idempotent booking request, OpenAPI 3.1 | S-1 | curl or Postman succeeds; same Action serves Filament; contract checked in CI | 12 |
| S-7 | Spike: restore drill from backup | S-1 | Database, uploads and keys restored; time measured; RPO and RTO owner-signed | 13 |
| PERF-0 | Performance baseline on the real VPS | S-1 | Baseline recorded against the agreed load profile | 11 |
| G1 | Gate: stack, MariaDB version, RPO and RTO owner-signed | S-0, S-2, S-3, S-4, S-5, S-6, S-7, PERF-0 | Spike results written; owner signature | — |

## M1 — Identity, customers, catalog, settings

Exit: permission matrix test passes; gates 4 and 5.

| ID | Task | Depends on | Verify | Gates |
|---|---|---|---|---|
| IDN-1 | Seed the two branches and six individual accounts | G1 | Seeder test; no shared logins | — |
| IDN-2 | Staff MFA | IDN-1 | Feature test; login without MFA refused | — |
| IDN-3 | Roles and permission matrix | IDN-2 | Matrix test across branches and roles | 4 |
| IDN-4 | Cross-branch history read versus branch write policy | IDN-3 | Policy tests; unauthorised write and export refused | 4 |
| CUS-1 | Customers and verified contacts | IDN-1 | Unit and feature tests | — |
| CUS-2 | Public submission never overwrites or reveals an existing customer | CUS-1 | Test with an existing phone number | 5 |
| CUS-3 | Pets, multiple per customer | CUS-1 | Feature test | — |
| CUS-4 | Ownership policy: one customer cannot see another's pet, booking or invoice | CUS-3 | Policy test; public endpoints leak nothing | 3 |
| CAT-1 | Service and package catalog | IDN-1 | Catalog matches the content document | — |
| CAT-2 | Price rule precedence and quote snapshot | CAT-1 | Snapshot unchanged after a price change | 6 |
| SET-1 | Typed, versioned settings; Unset blocks the operation | IDN-1 | Unset blocks visibly; value change leaves old documents alone | — |
| SET-2 | Refund reason catalog with active and archived entries | SET-1 | Archived label still shown on old documents | 18 |

## M2 — Scheduling and booking

Exit: gates 1, 2 and 20; gates 8 and 19 partly.

| ID | Task | Depends on | Verify | Gates |
|---|---|---|---|---|
| BK-1 | Booking requests and the RequestBooking action | CUS-2, SET-1 | A request reserves nothing | — |
| BK-2 | Resources, resource-day lock and ConfirmAppointment | BK-1, S-2 | Two-session test on real MariaDB: at most one succeeds | 1 |
| BK-3 | Hold lifetime versus confirmed reservation | BK-2 | Hold expiry never touches a confirmed reservation; approver needed to change a price | 20 |
| BK-4 | Idempotency table for booking and payment mutations | BK-1 | Retry returns the stored response; same key with a different payload returns 422 | 2 |
| BK-5 | Reschedule event, version check, state separation | BK-2 | Old reminder cancelled; a setting change leaves confirmed bookings alone | 8, 19 |
| BK-6 | Home service booking | BK-2 | Blocks visibly while coverage, fee and failed-visit settings are Unset | — |
| BK-7 | Completion and incident recording | BK-2 | Feature tests; assigned-appointment access only | — |

## M3 — Finance

Exit: gates 6, 7, 15 to 18 and 19; accountant review.

| ID | Task | Depends on | Verify | Gates |
|---|---|---|---|---|
| FIN-1 | Invoice issue with snapshot and locked-sequence numbering | BK-7, CAT-2, BK-4 | Snapshot survives later changes; numbers come from the locked sequence row, not AUTO_INCREMENT | 6, 19 |
| FIN-2 | Manual collection: cash, bKash, Nagad, split, partial, receipt, reversal | FIN-1 | Daily totals match across partial, split, cancel, correction and refund | 7 |
| FIN-3 | Customer advance ledger and allocation | FIN-2 | Advance and application example holds; concurrent redemption and refund cannot spend one advance twice | 15, 16, 17 |
| FIN-4 | Refund Invoice lifecycle | FIN-3, SET-2 | Active reason required; archived reason visible on old documents; payout posted once | 18 |
| FIN-5 | Reports with an accounting basis on each | FIN-2, FIN-4 | Invoiced sales, collections, refunds, outstanding and advance reported separately | — |
| FIN-6 | Daily cashier close | FIN-2 | Expected versus declared, with a reason for any difference | 7 |
| FIN-7 | Accountant review of VAT, Mushak, numbering and retention | FIN-1, FIN-6 | Written accountant sign-off | — |

## M4 — Public site, CMS and SEO

Exit: gates 9 and 10.

| ID | Task | Depends on | Verify | Gates |
|---|---|---|---|---|
| SITE-1 | Blade theme and public pages | CAT-1 | Pages render from the content document | — |
| SITE-2 | Redirect, sitemap and structured data | SITE-1 | Redirect and sitemap checked; private pages excluded | 10 |
| SITE-3 | Cache allowlist | SITE-1, S-5 | Two-session test; no private content cached | 9 |

## M5 — API and notifications

Exit: gates 2, 3, 8 and 12.

| ID | Task | Depends on | Verify | Gates |
|---|---|---|---|---|
| API-1 | Public `/api/v1` slice with OpenAPI checked in CI | S-6, BK-4 | A non-browser client gets the same result | 2, 12 |
| API-2 | Outbox relay, SMS retry and stale reminder | BK-5 | Provider failure leaves the booking; reschedule cancels the old reminder | 8 |
| API-3 | OTP and Sanctum design for later private endpoints | API-1 | Design reviewed; ownership tests run before any private endpoint opens | 3 |

## M6 — Hardening and launch

Exit: G3 and G4, with evidence for all 20 gates.

| ID | Task | Depends on | Verify | Gates |
|---|---|---|---|---|
| HARD-1 | Load test against the agreed profile | API-1, SITE-3 | Results compared with the M0 baseline | 11 |
| HARD-2 | Security pass | API-3, FIN-7 | Ownership, cross-branch and export tests re-run | 3, 4, 5 |
| HARD-3 | Repeat the restore drill | S-7 | Clean restore returns database and media | 13 |
| HARD-4 | Receptionist pilot, end to end with daily close | FIN-6 | Pilot scenario run by staff | 14 |
| HARD-5 | Training and data and URL migration | HARD-4 | Migration rehearsal passes | — |
| G3 | Gate: security and performance accepted | HARD-1, HARD-2, HARD-3 | Evidence attached | — |
| G4 | Gate: launch readiness, all gates evidenced | G3, HARD-5 | Evidence for gates 1 to 20 | 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20 |

## Gates

Gate 3 note: the MVP has no customer login. M1 proves only that ownership policy holds and public endpoints leak nothing; the full test is mandatory in M5 before any private endpoint opens.

| Gate | What must be true | First proof | Re-verify |
|---|---|---|---|
| 1 | Two confirmations cannot take the same slot | M2 (spike in M0) | M6 |
| 2 | A retry yields one booking and one receipt | M2 | M3, M5 |
| 3 | Another customer's pet, booking and invoice are not visible | M1 policy, M5 private endpoints | M6 |
| 4 | Six accounts read cross-branch history; no unauthorised write or export | M1 | M6 |
| 5 | An existing phone number cannot overwrite or leak history through a public request | M1 | M6 |
| 6 | Quote and invoice snapshots stay correct after changes | M1 price, M3 invoice | M6 |
| 7 | Partial, split, cancel, correction and refund reconcile to the daily total | M3 | M6 |
| 8 | Reschedule cancels the old reminder; provider failure leaves the booking | M2, M5 | M6 |
| 9 | Cached and private content never mix across two sessions | M4 | M6 |
| 10 | Public pages have correct HTML and metadata, redirect and sitemap; private pages excluded | M4 | M6 |
| 11 | Performance measured against the load profile | M0 baseline | M6 |
| 12 | A non-browser client gets the same result | M0 (S-6), M5 | M6 |
| 13 | A clean restore returns database and media | M0 (S-7) | M6 |
| 14 | A receptionist pilot completes end to end with daily close | M6 | — |
| 15 | Advance example: overpayment becomes advance | M3 | M6 |
| 16 | Advance example: later bill applies the advance | M3 | M6 |
| 17 | Concurrent redemption and refund never spend one advance twice | M3 | M6 |
| 18 | Refund requires an active reason; archived reasons stay visible on old documents | M3 | M6 |
| 19 | A setting change never alters a confirmed booking or an issued document | M2 booking, M3 document | M6 |
| 20 | Hold expiry never touches a confirmed reservation; a price change needs an approved approver | M2 | M6 |

## Contradictions

Found while drafting. None is resolved silently; each needs an owner or reviewer decision.

| ID | Topic and conflicting statements | Status |
|---|---|---|
| C1 | Local runtime: confirmed baseline says Laragon on Windows, while the earlier AI-workflow advice says WSL2 with Docker Compose | Unresolved |
| C2 | Accounts: the owner says 2 admins and 4 staff, while the journeys name receptionist, groomer, cashier and manager | Unresolved |
| C3 | The plan says four separate states without naming them; the sources name five | Unresolved |
| C4 | The milestone table puts the outbox in M5, but confirming a booking in M2 needs to write its SMS row | Unresolved |
| C5 | The plan's branch model is dev, agent and main; the repository also has a develop branch created at the owner's request | Unresolved |
| C6 | Local MariaDB is older than the preferred 11.4 LTS | Unresolved |
| C7 | The earlier advice names files such as product-spec and data-model; Phase A names PRD, AppFlow, UI_Spec, TRD, Schema and Plan | Unresolved |

## Open decisions

The owner, accountant and Vaiya decisions, with the milestone each must precede, are listed in section 9 of [PRD.md](PRD.md). Unset settings block the operation visibly rather than defaulting; no agent decides them.
