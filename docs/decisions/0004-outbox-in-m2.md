# ADR-0004: Outbox table is created in M2

Status: Accepted
Resolves contradiction C4 (see [Plan.md](../Plan.md)).
Approved: in the project session, on the user's explicit confirmation of the proposed decisions. G0 sign-off by Vaiya and the owner still applies and may revise this record.

## Context

The milestone table places the outbox in M5. Confirming a booking in M2 must write its critical SMS in the same transaction, so the table must exist by then.

## Decision

The outbox table is created in M2 and ConfirmAppointment writes its SMS row. The relay, retry and stale-reminder work stay in M5.

## Consequences

Schema moves outbox_messages to M2. The backlog gains task BK-8, and the relay task depends on it. A provider failure still leaves the booking confirmed.
