<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Free Member Active Collaboration Limit
    |--------------------------------------------------------------------------
    | Set to:
    |   0  => Unlimited
    |   null => Unlimited
    |   any positive integer => max active posts
    */
    'free_active_limit' => env('COLLAB_FREE_ACTIVE_LIMIT', 2),
];
