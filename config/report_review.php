<?php

return [
    // Never turn on for all employees before organizational assignments have
    // been verified. Defaults off even after code and schema are deployed.
    'enabled' => (bool) env('REPORT_REVIEW_WORKFLOW_ENABLED', false),
    // Maximum number of sequential review steps in the snapshotted chain.
    'max_steps' => max(1, min(5, (int) env('REPORT_REVIEW_MAX_STEPS', 3))),
];
