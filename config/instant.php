<?php

return [
    'enabled' => env('INSTANT_ENABLED', false),
    'location_max_age_seconds' => 60,
    'location_update_seconds' => 30,
    'radius_after_seconds' => [5 => 60, 10 => 180],
    'arrival_code_length' => 6,
    'arrival_code_valid_seconds' => 900,
    'arrival_code_attempt_limit' => 5,
    'arrival_code_lock_seconds' => 300,
];
