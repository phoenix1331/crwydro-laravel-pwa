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
