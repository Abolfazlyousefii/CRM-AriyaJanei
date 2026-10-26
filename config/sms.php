<?php

return [
    'default' => env('SMS_DRIVER', 'sandbox'),

    'drivers' => [
        'melipayamak' => [
            'username' => env('MELIPAYAMAK_USERNAME', 'melipayamak'),
            'password' => env('MELIPAYAMAK_PASSWORD', 'melipayamak'),
        ],

        'melipayamak_api' => [
            'token' => env('MELIPAYAMAK_API_TOKEN'),
            'from'  => env('MELIPAYAMAK_API_FROM_NUMBER'),
        ],

        'sandbox' => [],
    ],
];
