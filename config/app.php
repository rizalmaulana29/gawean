<?php
    
return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    // 'debug' => env('APP_DEBUG', true),

    // INGAT UNTUK DI KOMENTARIN
        'debug' => env('APP_DEBUG', in_array(
                isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : [],
                [
                        "210.210.165.250",
                        "36.72.4.121", // id hp nendi
                        "116.206.15.32", // id hp Rivan!
                        "36.65.240.185", //IP InsMul
                        "61.94.89.125", //IP InsMUl di IC
                        "116.206.14.12", //hp sandi
                        "36.72.0.142",//Insan Mulia
                        "36.79.248.35",//Insan Mulia 5G
                        "114.122.103.95",// Telkomsel Rivan
                ]
        ) ? true : false),

        'providers' => [
        Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider::class,
        ],

];