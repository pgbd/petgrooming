# ADR-0003: The four separate states

Status: Accepted
Resolves contradiction C3 (see [Plan.md](../Plan.md)).
Approved: in the project session, on the user's explicit confirmation of the proposed decisions. G0 sign-off by Vaiya and the owner still applies and may revise this record.

## Context

The plan requires four separate states but does not name them. The sources describe booking, invoice, payment and refund lifecycles, plus SMS delivery.

## Decision

The four business states are booking, invoice, payment (including customer advance) and refund. Notification is a delivery status carried by the outbox. It is tracked separately and never changes a business state.

## Consequences

AppFlow, Schema and tests keep these four apart. The decision should be checked against the Architecture Revision Note when it is available.
