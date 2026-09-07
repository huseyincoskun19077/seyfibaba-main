<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Used for FCM HTTP v1 API: projects/{project_id}/messages:send
    |
    */
    'project_id' => env('FIREBASE_PROJECT_ID', 'seyfibabapp'),

    /*
    |--------------------------------------------------------------------------
    | Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Absolute path to the Firebase service account JSON file downloaded from
    | Firebase Console → Project settings → Service accounts.
    |
    */
    'credentials' => (($path = env('FIREBASE_CREDENTIALS')) && trim((string) $path) !== '')
        ? $path
        : storage_path('app/firebase/service-account.json'),

];
