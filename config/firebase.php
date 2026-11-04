<?php

/*
 * Firebase Authentication (phone OTP). Only the web config values are used in the browser, and they are public by design.
 * Token verification needs just the project ID; no service-account key is stored on the server.
 */
return [
    'api_key' => env('FIREBASE_API_KEY'),
    'auth_domain' => env('FIREBASE_AUTH_DOMAIN'),
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'app_id' => env('FIREBASE_APP_ID'),
    'messaging_sender_id' => env('FIREBASE_MESSAGING_SENDER_ID'),

    // Google's public certificates that sign Firebase ID tokens.
    'certs_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com',
    'clock_skew_seconds' => 60,
];
