<?php

use Illuminate\Support\Facades\Facade;

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    'debug' => env("BOT_DEBUG"),

    'token' => env('TELEGRAM_BOT_TOKEN'),

    'developer_id' => env('TELEGRAM_BOT_DEVELOPER_ID'),

    'bot_name' => env("TELEGRAM_BOT_NAME"),

    'TELEGRAM_BOT_CHANNEL' => env("TELEGRAM_BOT_CHANNEL"),

    'PRICE_DELIVERY_PER_KM' => env("PRICE_DELIVERY_PER_KM", 1300),

];
