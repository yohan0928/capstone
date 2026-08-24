<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Geocoding Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: open-meteo, nominatim, bigdatacloud, google
    |
    | - open-meteo: Completely free, no API key required, good for most use cases
    | - nominatim: Free, no API key required, but has rate limits (1 req/sec)
    | - bigdatacloud: Free tier (50,000 queries/month), requires API key
    | - google: Most accurate, requires API key, paid after free tier
    |
    */
    'provider' => env('GEOCODING_PROVIDER', 'open-meteo'),

    /*
    |--------------------------------------------------------------------------
    | API Key (Required for bigdatacloud and google)
    |--------------------------------------------------------------------------
    */
    'api_key' => env('GEOCODING_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache geocoding results to reduce API calls and improve performance
    |
    */
    'cache' => [
        'enabled' => true,
        'duration' => 604800, // 7 days in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | If geocoding fails, should we fallback to another provider?
    |
    */
    'fallback' => [
        'enabled' => true,
        'provider' => 'nominatim',
    ],
];