<?php

return [
    // Must match a valid value from circle_member_status_enum in PostgreSQL.
    'member_active_status' => env('CIRCLE_MEMBER_ACTIVE_STATUS', 'approved'),
];
