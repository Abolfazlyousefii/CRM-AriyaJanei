# Department-first personnel management

## Confirmed organization rules (2026-10-23)

- Company management is a company-wide group, with any number of people. Use the existing `ceo`, `executive_manager`, and `internal_manager` organizational positions. These are independent of software access roles.
- Each department has zero or one supervisor, and zero or one department manager. Both are optional and must be different members of that department.
- Do not model multiple upper managers per department. Company managers are maintained in their own section of the personnel board.
- Each user currently has one primary department. No multi-department membership was requested or introduced.
- Listing company managers does not automatically make every manager an approver. Explicit direct-manager relationships and the established department fallback continue to determine routing. Historical report and leave assignments remain intact.
- The company-manager form explicitly selects either a root position (clearing the previous direct manager) or reporting to another company manager. Self-management and cyclic chains remain rejected.
- Existing legacy `parent_manager_id` values remain readable by the routing service; the department editor does not overwrite them or assign new ones. Manage reporting relationships explicitly through the person's placement editor.

## Workflow

Click a department card to open its members and responsibilities. Search existing users, select individual members or all currently filtered unassigned users, then choose optional leaders from the selected active members. The form previews incoming and outgoing names before confirmation. Selecting a person from another department transfers that person's primary membership.

The optional "follow unit structure" checkbox is off initially. When explicitly selected it clears direct-manager overrides for selected members, allowing department routing to apply. Unselected people and software roles are never changed. Ordinary unit titles are synchronized with the chosen unit leadership, while company management titles are preserved.

Membership and leadership save in one transaction. Moving a leader of another unit is rejected until that unit's leadership is changed. Invalid membership, duplicate leaders, cycles, or stale membership snapshots reject the whole operation. All endpoints remain Admin-only and CSRF-protected.

No real people are assigned based on names mentioned in conversation. Real chart setup is performed by the administrator in the UI.
