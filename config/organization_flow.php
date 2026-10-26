<?php

return [
    'enabled' => (bool) env('ORGANIZATION_FLOW_ENABLED', false),
    'otp_minutes' => 5,
    'otp_attempts' => 5,
    'executive_sms_pattern_id' => env('LEAVE_EXECUTIVE_SMS_PATTERN_ID'),
];
