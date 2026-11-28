<?php

return [
    'enabled' => env('INSTANT_ENABLED', false),
    'local_amman_default' => env('INSTANT_LOCAL_AMMAN_DEFAULT', false),
    'routing_url' => env('INSTANT_ROUTING_URL', 'https://routing.openstreetmap.de/routed-car'),
    'suggestion_model' => env('INSTANT_SUGGESTION_MODEL', 'gemini-3-flash-preview'),
    'location_max_age_seconds' => 60,
    'location_update_seconds' => 30,
    'radius_after_seconds' => [5 => 60, 10 => 180],
    'arrival_code_length' => 6,
    'arrival_code_valid_seconds' => 900,
    'arrival_code_attempt_limit' => 5,
    'arrival_code_lock_seconds' => 300,
];
