# Pet Grooming BD — Product Requirements (D1)

Status: **Draft for G0 review.** Nothing here is approved until Vaiya and the owner sign off (gate G0).

## 1. Purpose

A web platform for Pet Grooming BD: a public Bengali-first website with booking requests, and a staff back office for scheduling, billing and reporting. Public site is server-rendered Blade; staff work in Filament; a versioned REST API (`/api/v1`) serves future clients.

## 2. Provenance and limits of this draft

- Sources: the owner's answers recorded in the development plan (section "Authoritative specification"), the plan's sections 1, 4, 7, 10 and 11, and the website content document.
- The **Architecture Revision Note** (sections 1, 5, 6, 12) was not available when this draft was written. The plan says OWN-001…010 must be copied from it verbatim.
- Until the Note is mapped, owner answers carry **provisional keys `OWN-P1…P8`**. Mapping to the official OWN-001…010 is **pending**, and OWN-009/010 are not known from the sources.
- No value in this document was invented. Anything the owner left to an admin setting is marked **Unset**.

## 3. Owner-confirmed baseline

| Key | Topic | Owner answer (as recorded) |
|---|---|---|
| OWN-P1 | Branches at launch | 2 branches |
| OWN-P2 | System users | 2 admins and 4 staff |
| OWN-P3 | When a booking request is confirmed | When an admin or staff member confirms it |
| OWN-P4 | How long a slot stays blocked | Dynamic setting in the admin panel |
| OWN-P5 | Who approves the final price | Dynamic setting in the admin panel |
| OWN-P6 | Advance, cancellation, refund | Extra amount at billing is added to the customer's account as advance. Admin or staff can cancel and refund the customer using a Refund Invoice. The cause is chosen from a dynamic dropdown. Rules are a dynamic admin setting |
| OWN-P7 | Who sees a customer's cross-branch history | Admin and staff |
| OWN-P8 | Home service coverage, travel fee, failed-visit policy | Dynamic setting in the admin panel |

## 4. Actors

| Actor | Needs |
|---|---|
| Pet parent (public) | Find services, send a booking request, get a call back. No login in the MVP |
| Admin (2) | All staff capabilities, plus settings, reason catalog and approvals |
| Staff (4) | Confirm requests, schedule, check-in, complete, invoice, collect, cancel and refund |

The plan's user journeys also name receptionist, groomer, cashier and manager. **Contradiction to resolve:** the owner states 2 admins and 4 staff, so how those four functional roles map onto six accounts is **Unset** (owner decision, before M1).

## 5. Scope

**In the MVP**

- Public site: services and prices, packages, home service, about, contact and booking, tips, policy pages, SEO and structured data.
- Booking request → confirmation → check-in → completion → invoice → payment or advance → cancel or refund. Booking, payment, invoice and refund states are separate.
- Customer identity is global; branch access is separate. A public request never overwrites an existing customer profile.
- Manual collection (cash, bKash, Nagad), split and partial payments, customer advance ledger, Refund Invoice, daily cashier close.
- Optimistic version check so two staff members cannot silently overwrite each other.
- Typed, versioned admin settings with a reason catalog.

**Out of the MVP:** customer login and portal, Flutter app, online payment gateway, inventory, membership, a second theme, NBR e-invoicing, point-in-time recovery.

## 6. Business rules

| ID | Rule |
|---|---|
| BR-BOOK-001 | A booking request does not reserve a slot until confirmation succeeds |
| BR-BOOK-002 | Two confirmed appointments cannot overlap for the same exclusive resource |
| BR-BILL-001 | Issued invoice amounts cannot be silently edited; corrections are linked documents |
| BR-BILL-002 | Financial records are never deleted |
| BR-ACCESS-001 | A groomer may access only assigned appointments and permitted care information |
| BR-ACCESS-002 | Cross-branch customer history is read-only unless a write permission exists |
| BR-SET-001 | An Unset setting blocks the operation visibly; fees, penalties and policies are never invented |
| BR-SET-002 | Changing a setting never alters a confirmed booking or an issued document |

## 7. Settings

All are admin-editable, typed and versioned. None has a value yet.

| Setting | Value |
|---|---|
| Slot block duration | Unset |
| Slot buffer | Unset |
| Final price approver | Unset |
| Home service coverage zones | Unset |
| Home service travel fee | Unset |
| Home service failed-visit outcome | Unset |
| Cancellation and no-show fee | Unset |
| Refund limit | Unset |
| Cancellation, refund and advance rules (OWN-P6) | Unset |
| Refund reason catalog | Unset |
| Cross-branch advance use, and automatic versus selected credit application | Unset |

## 8. Public site

- Canonical domain `petgroomingbd.com`; `petgrooming.bd` redirects to it with a 301 in production.
- Dev: `test.dev.petgrooming.bd` (public) and `admin.dev.petgrooming.bd` (admin), password-protected and `noindex`, with no real customer data.
- Page content, prices, policies and SEO metadata follow the website content document, which owns them.

## 9. Open decisions

| Decision | Decider | Needed before |
|---|---|---|
| Slot, buffer and hold values; final-price approver | Owner | M2 start |
| Coverage zone, travel fee, failed-visit outcome | Owner | M2 end |
| Cancellation or no-show fee; refund limit | Owner | M3 start |
| Branch-write rule; cross-branch advance use; automatic versus selected credit | Owner | M1 end / M3 start |
| VAT class, Mushak, EFD, gap-free numbering, record retention | Accountant | M3 start |
| Shared phone or household policy; multi-pet and multi-groomer rules | Owner | M1 start |
| Launch language, content owner, printer model | Owner | M4 start / M0 |
| Existing data and URL migration | Owner | M4 start |
| RPO/RTO, budget, separate production VPS | Owner + Vaiya | M0 end |
| Traffic and booking volume estimate; portal or Flutter first | Owner | before M6 |

## 10. Traceability

The 20 acceptance gates are defined in the development plan, section 7, and are not restated here. Mapping OWN-001…010, R01…R14 and gates 1–20 to documents and tasks is the D6 review step and needs the Architecture Revision Note.
