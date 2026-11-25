<?php

declare(strict_types=1);

return [
    'days' => 30,
    'cookies' => [
        'first' => 'botgate_acquisition_first_v1',
        'last' => 'botgate_acquisition_last_v1',
        'client' => 'botgate_acquisition_client_v1',
    ],
    // Byte limits keep encrypted cookies and the combined request header small.
    'parameters' => [
        'yclid' => 96,
        'utm_source' => 64,
        'utm_medium' => 32,
        'utm_campaign' => 96,
        'utm_content' => 128,
        'utm_term' => 256,
        'group_id' => 32,
        'phrase_id' => 32,
        'source_type' => 16,
        'placement' => 128,
        'device_type' => 16,
        'region_id' => 32,
    ],
];
