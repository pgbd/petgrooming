# Pet Grooming BD — Application Flow (D2)

Status: **Draft for G0 review.** Settings referenced here are defined in [PRD.md](PRD.md); every one is currently **Unset**, so any step that needs one must block visibly rather than guess (BR-SET-001).

Source: development plan sections 4, 6, 7, 9 and 11. The Architecture Revision Note (its sections 5 and 7) was not available, so where the plan is silent this document says so instead of filling the gap.

## 1. End-to-end flow

Public request → Confirm → Check-in → Complete → Invoice → Payment or advance → Cancel or Refund

| Stage | Actor | What happens | Guarded by |
|---|---|---|---|
| Public request | Pet parent | Sends a booking request from the public site | A request does not reserve a slot (BR-BOOK-001). It never overwrites an existing customer profile |
| Confirm | Admin or staff | Confirms the request into an appointment reservation | Transaction plus row lock on one concrete parent row; at most one of two simultaneous confirmations succeeds (BR-BOOK-002) |
| Check-in | Staff | Marks the pet as arrived | Branch policy; the same version check as every edit |
| Complete | Staff or groomer | Records completion and any incident | Assigned-appointment access only (BR-ACCESS-001) |
| Invoice | Staff | Issues an invoice with line, customer, price and tax snapshot | Snapshot is immutable; the number comes from a locked sequence row (BR-BILL-001) |
| Payment | Staff | Collects cash, bKash or Nagad; split and partial payments allowed | Every payment mutation carries an idempotency key |
| Cancel | Admin or staff | Cancels a booking or document | Linked correction, never deletion (BR-BILL-002) |
| Refund | Admin or staff | Pays the customer back through a Refund Invoice | Reason chosen from the active reason catalog |

## 2. Separate state machines

The plan requires booking, payment and delivery outcomes to stay independent: a payment problem never rewrites the booking, and an SMS failure never rewrites either. Per ADR-0003 the four business states are booking, invoice, payment (including advance) and refund. Notification is a delivery status carried by the outbox. The choice should be checked against the Note when it is available.

### Booking state

- A request is saved but holds nothing. A hold has a lifetime, and expiry never touches a confirmed reservation. The duration is **Unset**.
- Confirm creates exactly one reservation. Failure to confirm leaves the request untouched.
- A reschedule is its own event. The old reminder is cancelled and a new one is created.
- Two staff members editing the same booking are protected by a version check; the second save gets a conflict, not an overwrite.
- Booking transition names beyond request, hold, confirmed, checked-in, completed, cancelled and rescheduled are **not defined** in the sources.

### Invoice state

- Issued amounts are never edited. Correction is a linked document; void is a separate path.
- Changing a setting later never alters an issued document (BR-SET-002).

### Payment state

- Collections: cash, bKash, Nagad; split, partial, receipt ID, reversal; a daily cashier close compares expected with declared totals and records the reason for any difference.
- Customer advance: one receipt posts the invoice allocation and the advance posting in the same transaction. Available credit is derived from posted entries and active reservations, reversals are linked, and locks are always taken in the same order.

### Refund state

Refund Invoice lifecycle: draft → pending payout → completed, failed or void.

- An active reason ID and its label snapshot are mandatory.
- Pending reserves the amount, and the payout is posted exactly once.
- Archived reasons stay visible on older documents.

### Notification state (delivery status)

- Critical SMS is written as an outbox row in the same transaction as the business change. After-commit dispatch alone is not delivery.
- A provider failure leaves the booking as it is: it stays confirmed, and the failure is visible. Retries and stale-reminder handling are part of the notification flow.

## 3. Worked example: advance and refund

All amounts are in the plan's own example (gates 15 and 16).

1. Bill is 2,000 and the customer pays 2,500. The invoice is settled and **500** is posted as customer advance.
2. A later bill is 1,200. The 500 advance is applied and the customer pays **700** in cash.

Whether credit is applied automatically or selected by staff, and whether advance can be used across branches, are **Unset** owner decisions (PRD section 9).

## 4. Cross-cutting rules

| Concern | Rule |
|---|---|
| Retries | Booking and payment mutations store a key, a request hash and the response. A repeat with the same key and a different payload returns 422. Replays re-check authorization |
| Concurrency | Row lock on a concrete parent row, half-open interval overlap, a bounded number of retries |
| Branch access | Customer identity is global. Cross-branch history is read-only unless a write permission exists |
| Public requests | A request with an existing phone number cannot overwrite or reveal history |
| Caching | Admin, login, booking and API responses bypass any HTML cache |
| API | The same Action classes serve Filament and `/api/v1` |

## 5. Gaps to close before G0

- Booking and appointment transition table, beyond the stages above.
- Home-service flow details, which depend on the Unset coverage, fee and failed-visit settings.
- Which person holds which roles among receptionist, groomer, cashier and manager (ADR-0002 makes them roles; the assignment is Unset in PRD section 4).
