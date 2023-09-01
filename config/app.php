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

     'debug' => env('APP_DEBUG', true),

    // INGAT UNTUK DI KOMENTARIN
        'debug' => env('APP_DEBUG', in_array(
                isset($_SERVER['HTTP_X_ORIGINAL_FORWARDED_FOR']) ? $_SERVER['HTTP_X_ORIGINAL_FORWARDED_FOR'] : 
                (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : []),
                [
                        "210.210.165.250",
                        "66.96.225.127", //IP InsMul
                        "61.94.89.125", //IP InsMUl di IC
                        "116.206.14.59", //hp sandi
                        "103.121.18.7",//Insan Mulia
                        "36.79.248.35",//Insan Mulia 5G
                        "114.122.103.95",// Telkomsel Rivan ABI 
                        "180.244.131.132",// IP cinte 20
                        "114.5.208.180", //indosat irvan
                        "180.244.128.175", // IP Nendi
                        "180.253.19.239", //ipilmi
                        "180.244.129.40",
                        "202.80.217.125",
                        "103.135.225.44"
                ]
        ) ? true : false),

        'providers' => [
        Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider::class,
        ],

];