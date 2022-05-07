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
                       /* "210.210.165.250",
                        "36.72.4.121", // id hp nendi
                        "116.206.15.32", // id hp Rivan!
                        "66.96.225.127", //IP InsMul
                        "61.94.89.125", //IP InsMUl di IC
                        "116.206.14.59", //hp sandi
                        "34.117.59.81",//Insan Mulia
                        "36.79.248.35",//Insan Mulia 5G
                        "114.122.103.95",// Telkomsel Rivan
                        "180.244.134.149",// IP cinte 20 
                        "180.244.133.122", //masih cinte20
                        "103.121.18.32",//myrep
                        "114.5.208.180", //indosat irvan*/
                        "210.210.165.250",
                        "36.79.248.253", // id hp nendi
                        "116.206.14.21", // id hp Rivan!
                        "36.65.240.185", //IP InsMul
                        "61.94.89.125", //IP InsMUl di IC
                        "116.206.14.27", //hp sandi
                        "36.79.193.228",//Insan Mulia
                        "180.244.133.215".//Cinte20
                        "114.122.103.95",// Telkomsel Rivan
                        "103.135.227.58",
                        "66.96.225.113",
                        "103.121.18.42", //ip kang Irvan
                        "103.135.227.58", //ip kang Irvan
                        "180.244.139.207", //ip kang Irvan
                        "103.121.18.3", //ip kang Irvan
                        "36.79.162.77",
                        "180.244.130.15",
                        "103.121.18.61",
                        "114.124.195.75",
                        "103.175.48.54",
                ]
        ) ? true : true),

        'providers' => [
        Superbalist\LaravelGoogleCloudStorage\GoogleCloudStorageServiceProvider::class,
        ],

];