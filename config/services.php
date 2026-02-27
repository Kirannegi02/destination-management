<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Distance API: used to calculate km between pick and drop locations (transport quotes).
    | - GOOGLE_MAPS_API_KEY: optional; when set, Google Distance Matrix is used.
    | - When not set, falls back to OpenStreetMap (Nominatim + OSRM), then Admin Location Distances, then test distance.
    | - TRANSPORT_FALLBACK_DISTANCE_KM: used when no API and no DB row (e.g. 100 for testing without any key).
    */
    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'transport' => [
        'fallback_distance_km' => (float) env('TRANSPORT_FALLBACK_DISTANCE_KM', 100),
    ],

];
