<?php

return [
    'enabled' => (bool) env('ORGANIZATION_FLOW_ENABLED', false),
    'otp_minutes' => 5,
    'otp_attempts' => 5,
];
