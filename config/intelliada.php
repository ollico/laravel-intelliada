<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Project token
    |--------------------------------------------------------------------------
    |
    | Your Intelliada project token. It stays on your server: the browser only
    | talks to your own domain and the package adds the token when it
    | forwards events. Tracking is off until a token is set.
    |
    */

    'token' => env('INTELLIADA_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Enabled
    |--------------------------------------------------------------------------
    |
    | Turn tracking off for an environment without removing the token, for
    | example INTELLIADA_ENABLED=false in local development.
    |
    */

    'enabled' => (bool) env('INTELLIADA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | First-party path
    |--------------------------------------------------------------------------
    |
    | The tracking script and events are served under this path on your own
    | domain. `intelliada:install` picks a random value so generic
    | ad-blocker rules have nothing to match against.
    |
    */

    'path' => env('INTELLIADA_PATH', 'ia'),

    /*
    |--------------------------------------------------------------------------
    | Ingest endpoint
    |--------------------------------------------------------------------------
    |
    | Where your server forwards events. Only change this if Intelliada
    | support asks you to.
    |
    */

    'endpoint' => env('INTELLIADA_ENDPOINT', 'https://intelliada.com/api/v1/ingest'),

    /*
    |--------------------------------------------------------------------------
    | Forwarding timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for Intelliada when forwarding an event. Kept short so
    | a slow network never holds up your workers.
    |
    */

    'timeout' => (float) env('INTELLIADA_TIMEOUT', 2),

    /*
    |--------------------------------------------------------------------------
    | Route middleware
    |--------------------------------------------------------------------------
    |
    | Applied to the script and event routes. They sit outside the `web`
    | group so no session or CSRF token is involved.
    |
    */

    'middleware' => [
        'throttle:120,1',
    ],

];
