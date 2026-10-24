# Company leave workflow and report visibility

Feature switch: `ORGANIZATION_FLOW_ENABLED=true`. Configure the singleton company workflow settings through Admin > Organization > Company leaders. No personal names, phone numbers, or production identifiers are hardcoded in application code.

## Confirmed behavior

- Reports are submitted without approval. Every active ancestor in the organizational chain can view the report and add a separately attributed comment; each comment notifies its author. Existing single feedback fields remain intact.
- A configured executive is the root and the configured internal manager reports to that executive. Unit leadership falls back to the internal manager; staff still traverse their unit supervisor and manager for report visibility. Organizational leadership is verified against configured people and department membership, not merely a title string.
- While the company flow is enabled, configured department leadership takes precedence over old direct-manager overrides for assigned members. Unassigned legacy accounts are not silently reassigned or archived.
- New leaves snapshot a sequence: same-department substitute, one unit approver (supervisor, otherwise manager), internal manager, executive. Unit heads skip the unit approval stage. The internal manager's own leave goes directly to the executive. Executive self-approval is disallowed; an independent policy must be configured before that executive can submit leave.
- A substitute must be a different active, unblocked member of the same actual department. A common direct manager does not establish membership. Department members must select a substitute.
- Ordinary daily leave requires at least 48 hours before its start date/time, measured in the application's Tehran timezone. Hourly and emergency requests do not have this advance-notice requirement. Requests cannot start before today; same-day emergency requests may describe an earlier time today.
- Emergency daily and hourly requests share a cap of three submissions per Jalali month of their start date. Rejected and cancelled requests count. Author rows serialize registration before counting. Submitted requests cannot be edited; cancellation uses soft deletion so the quota and audit trail cannot be erased.
- Existing leave requests retain their previous approval mechanism and history. Only new requests use the staged workflow. Organizational changes do not silently rewrite existing stages.

## Executive SMS approval

Only the configured internal manager can request or enter an executive code, only after personally approving the internal stage, and only while that request waits for the configured executive. The executive receives the request identifier, employee, date and a four-digit code. Codes are hashed, expire in five minutes, allow five guesses, and are tied to the specific leave, step, requester and executive. Reissuing waits at least one minute; additional cache-based and route-level rate limits apply. A code is usable only after the SMS provider returns a positive receipt. Failed sends do not approve or expose a usable code. Sandbox sends are refused outside testing.

Verification locks the leave and challenge together, consumes the code and completes the executive stage atomically. The immutable event records the executive as principal and the internal manager as the actual actor, with method `sms_code`. The UI shows both. Direct account approval continues to work independently and prevents subsequent code replay.

## Deployment

The active host is behind the public integrated source. Apply the previously tested report/leave access prerequisites together with this feature. Apply only the explicitly named pending organizational migrations, before enabling the switch; do not batch-run unrelated pending company migrations. Install company leader settings separately from schema migrations. Check existing source changes before applying a patch. Keep report review approvals disabled; the company flow also prevents starting them if an older review flag is accidentally on.

Test against an isolated database with synthetic accounts and a fake SMS driver; do not send test codes or create fake leave requests on the production personnel accounts. Validate root/department rendering at 320, 390, 768 and 1366px and verify request, each stage, report comments and denial paths through real browser sessions.
