# Organizational routing acceptance fixes

## Authorization

`ReportPolicy` checks viewing, editing, deleting, feedback and submission separately.
Managers can read and review their organizational team but cannot rewrite or delete
another author's work. Admin retains supervision and legacy maintenance access.
Software roles and permissions control team visibility; an organizational title
alone does not grant it. A snapshotted reviewer can read the assigned report even
after personnel move or when the reviewer has another software role.

Fixed user-ID exceptions were removed. The old phone-based daily submission
exception is replaced by the explicit `reports.submit-multiple` permission.
Draft submission uses POST. Existing review snapshots remain protected with the
feature flag off. Revision writes and review decisions lock the report consistently.

The organization graph is resolved in memory for team scope. Cycles cannot grant
authorization. Archived authors remain identifiable in historical reports.

## Requests and review recovery

New leave requests with a valid chain save their responsible person in
`assigned_approver_id` and mark `approval_route=organization`. Later chart moves
do not silently reroute them. Historical requests and requests with no valid chain
retain the existing selected-approver process. Self-approval is rejected.

The additive migration `2026_10_22_200001_add_organizational_leave_routing`
adds two nullable columns and does not backfill or rewrite existing requests.
Accounting and Admin retain supervision; other users see authorized team,
assigned, own or substitute requests.

Admin can reassign an open organizational request with a required reason. Report
changes are logged as review events, and leave changes use the activity log.
Inactive reviewers cannot receive resubmissions silently; the review inbox shows
requests needing explicit assignment. The UI displays report steps and event history.

Voice feedback now persists its file path and audits only scalar metadata rather
than upload objects or audio bodies. The global loading overlay is fully hidden
after loading, including when Bootstrap display utilities apply.

## Verification and rollout

`OrganizationalRoutingAcceptanceTest` exercises unrelated actors, organizational
fallback, old ID exceptions, software-role separation, snapshots, correction,
reviewer reassignment, self-approval, audio feedback and management chart scope.
The existing organization, report, personnel, leave and field-visit tests remain
part of the required CI checks on PHP 8.2 and 8.3.

Browser verification uses synthetic users in `crm_ariya_ui_testing`, including
return/edit/resubmit, sequential approvals, report reassignment, leave submission,
leave reassignment and final approval. Light and dark layouts are checked at
1366, 768, 390 and 320 pixels. No synthetic staff or organizational assignments
are created in production.

The full suite has previously documented failures in authentication, profile and
customer satisfaction tests; these are tracked separately from required module checks.
Production activation awaits real organizational assignments. Repair the actual
chart before enabling team authorization and pilot review; a cyclic path is deliberately
denied instead of being treated as broad managerial access. Apply only the understood
workflow and leave migrations, not unrelated pending production migrations.
