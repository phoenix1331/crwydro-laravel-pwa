<?php

declare(strict_types=1);

return [

    /*
    | Disk for uploaded entry photos: local in development, s3 in production.
    */
    'photos_disk' => env('PHOTOS_DISK', 'local'),

    /*
    | Largest accepted photo upload in bytes. Clients resize to 1600px JPEG
    | before storing, so anything near this limit is unexpected.
    */
    'photo_max_bytes' => (int) env('PHOTO_MAX_BYTES', 8 * 1024 * 1024),

    'photos_per_entry' => 4,

    /*
    | API contract version sent on every response, and the oldest client
    | version still accepted. Raise api_version for any change a cached client
    | must know about; raise min_client_api when old clients must stop writing.
    */
    'api_version' => 1,
    'min_client_api' => (int) env('MIN_CLIENT_API', 1),

    /*
    | Times are stored in UTC; server-rendered views show them in UK time.
    */
    'display_timezone' => 'Europe/London',

    /*
    | Featured walks an entry can be attached to. GeoJSON lives in
    | public/routes/{id}.geojson (OpenStreetMap data, ODbL).
    */
    'routes' => [
        'yr-wyddfa-llanberis',
        'cwm-idwal',
        'cadair-idris-minffordd',
        'pen-y-fan-pont-ar-daf',
        'arfordir-penfro',
        'clawdd-offa',
        'llwybr-glyndwr',
    ],

];
