<?php

return [
    'email' => env('NIMBUSPOST_EMAIL'),
    'password' => env('NIMBUSPOST_PASSWORD'),
    'pickup' => [
        'warehouse_name' => env('NIMBUSPOST_WAREHOUSE_NAME'),
        'name' => env('NIMBUSPOST_NAME'),
        'address' => env('NIMBUSPOST_ADDRESS'),
        'address_2' => env('NIMBUSPOST_ADDRESS_2'),
        'city' => env('NIMBUSPOST_CITY'),
        'state' => env('NIMBUSPOST_STATE'),
        'pincode' => env('NIMBUSPOST_PINCODE'),
        'phone' => env('NIMBUSPOST_PHONE'),
    ],

];
