# Pet Grooming BD — UI Specification (D3)

Status: **Draft for G0 review.** Built from [PRD.md](PRD.md) and [AppFlow.md](AppFlow.md); where those are silent, the behaviour was proposed and then approved in [ADR-0009](decisions/0009-rev1-and-ui-approvals.md), and is marked **(approved, ADR-0009)**. Nothing here sets a value that the PRD lists as Unset.

## Conventions

- Staff screens are Filament pages. Business rules run in Actions, so every screen only calls them; no rule lives in a page callback.
- Every screen defines seven states: Normal, Empty, Loading, Validation, Conflict, Permission and Failure.
- An **Unset** setting never falls back to a default. The screen blocks the action and names the missing setting and where to set it.
- Mutations carry an idempotency key, so a double click or a retry cannot create a second result.
- Copy is Bengali-first. Final wording is owned by the content owner (an open decision in the PRD) and is not specified here.
- Keyboard-only use, visible focus and readable Bengali shaping are required on every screen. Exact typography and spacing are not specified yet.

## Booking confirm

Purpose: turn a saved booking request into one confirmed appointment (AppFlow, Confirm).

Shows: request detail and notes, matched customer and pets (never overwritten by the request), branch, chosen groomer and resource, requested time, hold status, and the version of the record being edited. Actions: Confirm, Reject (approved, ADR-0009), Back.

| State | Behaviour |
|---|---|
| Normal | Request detail with a Confirm action enabled only when groomer, time and resource are chosen and no required setting is Unset |
| Empty | No pending requests: a plain empty list with a link to the public form. No placeholder data |
| Loading | Schedule and availability load separately; Confirm stays disabled until availability has loaded |
| Validation | Missing groomer, time or resource, or an unknown phone format, is shown beside the field. Nothing is saved |
| Conflict | Another user confirmed this slot, or edited the request, first. The screen says so, shows the current state and keeps the user's choices; it never overwrites. A stale version check also lands here |
| Permission | A user outside the branch or role sees the request as unavailable and cannot confirm. No data changes |
| Failure | Server or timeout failure shows a retry that reuses the same idempotency key. If a required setting such as the slot block duration is **Unset**, the screen blocks visibly and names it |

Partial completion: if the appointment is confirmed but the SMS fails, the appointment stays confirmed and the screen shows a visible "SMS not delivered" notice with a retry. The booking is never rolled back for a notification failure.

## Daily close

Purpose: the cashier compares expected collections with what they declare, per method (AppFlow, Payment).

Shows: expected totals from posted collections (cash, bKash, Nagad), reversals, declared totals entered by the cashier, the difference per method, and a reason field for any difference. Actions: Declare, Submit close.

| State | Behaviour |
|---|---|
| Normal | Expected, declared and difference side by side; a reason is required whenever the difference is not zero |
| Empty | No collections for the day: expected shows zero for every method and the close can still be submitted |
| Loading | Expected totals load before declared fields accept input, so a declaration is never compared with partial data |
| Validation | A non-zero difference without a reason, or a non-decimal amount, is rejected beside the field. Amounts are decimals, never floating point |
| Conflict | A collection or reversal posted after the screen loaded changes the expected total. The screen refreshes the expected figure and asks for the declaration again (approved, ADR-0009) |
| Permission | Only authorised staff in the branch can close. Others see the figures read-only or not at all |
| Failure | A failed submit keeps the entered values and offers retry with the same idempotency key. A close is never half-recorded |

## Refund Invoice

Purpose: pay a customer back after a cancellation or a correction (AppFlow, Refund).

Shows: the source invoice and payments, refundable amount, customer advance balance, reason, state and history. Lifecycle: draft → pending payout → completed, failed or void. Actions: Save draft, Submit for payout, Mark completed, Mark failed, Void.

| State | Behaviour |
|---|---|
| Normal | Draft with an active reason chosen from the dropdown. While pending, the amount is reserved and shown as reserved |
| Empty | No refunds yet: an empty list. A new refund can only start from an invoice that has refundable value |
| Loading | Refundable amount and advance balance load before the amount field is editable |
| Validation | A missing reason, an archived reason, or an amount above the refundable value is rejected. Only an active reason can be chosen |
| Conflict | The invoice or advance changed under the user (version mismatch) or the same advance was redeemed concurrently. The screen reloads the figures and does not post. Payout is posted exactly once |
| Permission | Only authorised admin or staff can create or advance a refund. Others cannot see payout controls |
| Failure | A failed payout moves to failed and stays visible with the reason; it can be voided or retried per the refund state rules. Nothing is posted twice |

Reasons: older documents keep showing their reason label even after the reason is archived. The reason catalog content is **Unset**.

## Admin Settings

Purpose: admins maintain the typed, versioned settings that every other screen depends on (PRD, Settings).

Shows: each setting with its type, current value or **Unset**, version, who changed it and when, plus the refund reason catalog with active and archived entries. Actions: Edit, Save, Archive reason.

| State | Behaviour |
|---|---|
| Normal | A list grouped by area. Unset settings are flagged and list the operations they block |
| Empty | A fresh install shows every setting as Unset. That is the expected starting point, not an error |
| Loading | Values load before Edit is enabled so a stale value cannot be saved over a newer one |
| Validation | Wrong type or out-of-range input is rejected beside the field. A required reason for the change is enforced where the plan requires approval (approved, ADR-0009) |
| Conflict | A newer version exists: the screen shows the difference and requires an explicit re-apply. Saves are never silent overwrites |
| Permission | Admins only. Staff can read effective values where an operation needs them but cannot edit |
| Failure | A failed save keeps the form and shows the error. A changed setting never alters a confirmed booking or an issued document |

## Open items

- Role-to-screen access depends on how the six accounts map onto functions (PRD section 4).
- Copy, typography, spacing and colour belong to the design-system step, which is not yet written.
- The three behaviours marked (approved, ADR-0009) were proposals and are now approved; nothing remains proposed.
