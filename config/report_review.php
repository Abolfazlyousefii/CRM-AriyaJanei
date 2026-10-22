<?php

return [
    // Never turn on for all employees before organizational assignments have
    // been verified. Defaults off even after code and schema are deployed.
    'enabled' => (bool) env('REPORT_REVIEW_WORKFLOW_ENABLED', false),
    // Must explicitly list pilot authors or enable all_users after a full rollout review.
    'pilot_user_ids' => array_values(array_filter(array_map('intval', explode(',', (string) env('REPORT_REVIEW_PILOT_USER_IDS', ''))))),
    'all_users' => (bool) env('REPORT_REVIEW_ALL_USERS', false),
    // Maximum number of sequential review steps in the snapshotted chain.
    'max_steps' => max(1, min(5, (int) env('REPORT_REVIEW_MAX_STEPS', 3))),
];
