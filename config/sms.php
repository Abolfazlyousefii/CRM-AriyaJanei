<?php

return [
    'default' => env('SMS_DRIVER', 'sandbox'),

    'drivers' => [
        'melipayamak' => [
            'username' => env('MELIPAYAMAK_USERNAME', 'melipayamak'),
            'password' => env('MELIPAYAMAK_PASSWORD', 'melipayamak'),
        ],

        'melipayamak_api' => [
            'token' => env('MELIPAYAMAK_API_TOKEN', 'd5fa9a79caec4d779a66ce6df4dcf6db'),
            'from'  => env('MELIPAYAMAK_API_FROM_NUMBER', '5000xxx'),
        ],

        'sandbox' => [],
    ],
];