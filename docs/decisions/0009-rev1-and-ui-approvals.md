# ADR-0009: REV-1 confirmed and UI_Spec proposals approved

Status: Accepted
Approved: in the project session, by the user's explicit statements "REV-1: confirmed" and "UI_Spec proposed: approved".

## Context

ADR-0008 recorded G0 and carried two items forward: REV-1 (the traceability review) and the behaviours marked "proposed" in UI_Spec.md.

REV-1 maps OWN-001…010 and R01…R14 to documents and tasks. Both id lists come from the Architecture Revision Note, which has not been added to the project. No traceability matrix for those ids has been produced; only the 20 gates are mapped (Plan.md).

## Decision

- **REV-1 is recorded as confirmed by the user.** This record does not claim that a matrix of OWN and R ids exists. The documents keep the provisional keys OWN-P1…P8 until the Architecture Revision Note is supplied.
- **The UI_Spec proposals are approved.** Three behaviours lose their "proposed" mark:
  - a Reject action on Booking confirm;
  - re-declaring after a late collection on Daily close;
  - a required reason for setting changes where the plan requires approval.
- Role-to-screen access still depends on how people hold roles (ADR-0002); that assignment stays Unset.

## Consequences

- UI_Spec.md marks the three behaviours as approved and cites this record.
- Plan.md's gate status table points to this record.
- When the Note arrives, mapping official OWN and R ids to documents and tasks is done through a normal PR. Any document change it causes is reviewed like any other.
- Unset settings stay Unset. No agent sets them.
