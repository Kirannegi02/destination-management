<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase services including Phone Authentication.
    | This config is used to provide Firebase settings to client-side apps.
    |
    */

    'api_key' => env('FIREBASE_API_KEY', 'AIzaSyC9PSIebwbR2WpAmh3UjRSCPiKYI6h8JgM'),
    
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'destination-management-system.firebaseapp.com'),
    
    'project_id' => env('FIREBASE_PROJECT_ID', 'destination-management-system'),
    
    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'destination-management-system.firebasestorage.app'),
    
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID', '100829858620'),
    
    'app_id' => env('FIREBASE_APP_ID', '1:100829858620:web:5e0c5fd5374a0592952c39'),
    
    'measurement_id' => env('FIREBASE_MEASUREMENT_ID', 'G-9ZFEG88YQV'),

];

