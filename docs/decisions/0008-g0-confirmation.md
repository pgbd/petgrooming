# ADR-0008: G0 confirmed

Status: Accepted
Approved: in the project session, by the user's explicit statement "I confirmed G0".

## Context

Gate G0 requires Vaiya and the owner to approve the Phase A documents (PRD, AppFlow, UI_Spec, TRD, Schema, Plan) before any code starts. The documents and decision records ADR-0001 to ADR-0007 are on `develop`.

Two items were still open when the user confirmed:

- REV-1, the traceability review, is not finished. It maps OWN-001…010 and R01…R14 to documents and tasks, and both lists come from the Architecture Revision Note, which has not been supplied. The documents use the provisional keys OWN-P1…P8 instead.
- Items marked "proposed" in UI_Spec.md were not individually signed off.

## Decision

G0 is recorded as **confirmed** by the user. The record does not identify whether the user holds the Vaiya role, the owner role or both; the plan requires both, so that remains the user's responsibility.

Work may start with M0 spikes S-0 to S-7, then gate G1. The open items above stay open and are carried forward, not closed by this record.

## Consequences

- Plan.md shows G0 as confirmed and points to this record.
- REV-1 stays pending until the Note is supplied. Mapping OWN and R ids is done then, and any document change it causes goes through a normal PR.
- Provisional OWN-P keys are replaced by the official ids when the Note is available.
- "Proposed" behaviours in UI_Spec.md remain proposals until each is accepted or changed in a later record.
- Unset settings stay Unset. No agent sets them.
