<?php

return ['microsoft' => [
    'client_id' => env('MICROSOFT_CLIENT_ID'), 'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
    'tenant' => env('MICROSOFT_TENANT_ID', 'common'), 'redirect_uri' => env('MICROSOFT_REDIRECT_URI'),
    'enabled' => env('MICROSOFT_ENABLED', false),
]];
