<?php

return [
    'default' => env('SMS_PROVIDER', 'netgsm'),

    'providers' => [
        'netgsm' => [
            'usercode' => env('NETGSM_USERCODE'),
            'password' => env('NETGSM_PASSWORD'),
            'msgheader' => env('NETGSM_MSGHEADER', 'SEYFIBABA'),
            'endpoint' => env('NETGSM_ENDPOINT', 'https://api.netgsm.com.tr/sms/send/get'),
            'transactional_endpoint' => env(
                'NETGSM_TRANSACTIONAL_ENDPOINT',
                'https://api.netgsm.com.tr/sms/rest/v2/send'
            ),
        ],
    ],

    'otp' => [
        'length' => env('OTP_LENGTH', 6),
        'expire_minutes' => env('OTP_EXPIRE_MINUTES', 5),
        'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
        'cooldown_seconds' => env('OTP_COOLDOWN_SECONDS', 60),
    ],
];
