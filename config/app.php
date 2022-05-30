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
                        "116.206.15.32", // id hp Rivan!
                        "66.96.225.127", //IP InsMul
                        "61.94.89.125", //IP InsMUl di IC
                        "116.206.14.59", //hp sandi
                        "34.117.59.81",//Insan Mulia
                        "36.79.248.35",//Insan Mulia 5G
                        "114.122.103.95",// Telkomsel Rivan
                        "180.244.128.220",// IP cinte 20
                        "114.5.208.180", //indosat irvan
                ]
        ) ? true : false),

        'providers' => [
        Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider::class,
        ],

];