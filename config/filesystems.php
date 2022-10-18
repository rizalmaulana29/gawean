<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
        
        'upload' => [
            'driver' => 'local',
            'root' => storage_path('app/uploads/online/'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

    ],

    'gcs' => [
	    'driver' => 'gcs',
	    'project_id' => env('GOOGLE_CLOUD_PROJECT_ID', 'itod-304208'),
	    'key_file' => [
		                'type'                  => env('GOOGLE_CLOUD_ACCOUNT_TYPE'),
		                'private_key_id'        => env('GOOGLE_CLOUD_PRIVATE_KEY_ID'),
		                'private_key'           => "-----BEGIN PRIVATE KEY-----\nMIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCjsmcMyKF0/Gfg\n5D+FInPvMN8/EtRfmPdqkS2sp4hO94nNZiMY5noHhCkHhZAlel917egySLkuKh2D\nScAOVGkEOSjZEs5FZqqi95GM1r2qvSgVMhTouwGHonq7UZJ7my1pNdda4LBNQB1g\nBAyJu6XsjKW/j3i+9dJZB5YLZS7houaRbcmDGvsBoBahifYiBAl+NzToJY2TvVzr\nrcSQj3KRGHqlQA6yhLIbZ2+354l2V8Avnl+cbRrrrBq15dMPhcwJTxvLyo9eypaY\n/7l9uNFNxrOptZHrjLx8v566qQXT/CtV55na/fNTbS2at93YbBelLNwBh+zJ7YbW\nEC9GXtsvAgMBAAECggEAPFGfwUkonWfoiyjv8wnn+GpkyOQSV4uRF7aZQvaUtqxm\nHcaWBjXfH2fminv/yp+WD2lz1xuPCODB8T+6wkz+SW0ajSpt0YrsM5LQEjqKtyBu\nZakHpV5LSQRo/x8mv99FU3Bgg38p171ZVakISbRYhpqo9LZUQuFn+iBefKJzLPb2\n5XJVftZF8yvyPQTfE/9Y3Ldg/qePWBkobbdGB8aMByGUBq5jqUJ72oWKCD84hIcw\nfK27Psy3Uo+1vqkI10QLj1BKtKOcUU9ZQVRznn4rLcDT/RI46hc8plPBWn85v9b9\n05GtxCS6zA3eZg9S+sMCLH7aTQxjVvH6VPWHz/ehEQKBgQDl/Ep34UjGoBJnBeSU\nqNSmA2LPTT1Lhh27LL2k6U10YjYNPO2RplW7Nl/Z78m9ZIkAXDSYeK/5Qv+gxtE0\nx4OyO5rJ06aHecjkyrGxX9EBz6j73iqnXhDL0K9NQq/CUB9WhjtRHJPUPuRxqZn5\n93tDpSopbe5rAE/zWj401NgOZwKBgQC2NpYl3rcBTStLjAIzZ0lPJY3JpR7fpizU\nYHoYsCVR1n+twzWj0ZHegzuLapUXTnVMldoKrr+JXTS3GM7ObVsxNNd2OZZJi+Y+\nMkYideUUOAM38yqY1oW88dAk4PgQL2Bm6rXdIDZ2zffAiGTUpPNcxwqQyDcIE1y/\nu3geBna/+QKBgQCY5A/dgjcPVHrnYlODQHmdo8KcbYzCGHbLaALrDu4e4OtuAYMt\nwJZdztgJ2g8TiTJKuwF8Gz9hRdkK2SFbJQe4BUfxxHKAvcV/1AAtGrWnrpV4W0mf\n2jjwRdtEUYDmfL1YmAP2+DiOcQENTuK9+nhHkBVnVV2aZKrB7MxN8vFvIQKBgE7Y\nyhozCI/Im86CFEW4ERHtlzBFglmW59kyskLSniOOpQtE6IYt3mgh83c9tKw0KC/u\nvD5ZJcrECVadpofO7GIbkoy3GKBUqFoLmSu6Rll8b3Abijg+w/phzQbYTp96UMXY\nFMBN+yNntyiaHL+jbSedfaXu7VlSP5U8AxtDAsnJAoGBAOLybaPKxCs1g/kGmcyw\n3Gn4BMxjE5/rK8wRcpEUhxjOfVQYSOvdEvUTDQUx44Du4/lhEhnkMAj875hNGhJr\n5NJc7d4zUv2pWQye9DBbIjWtotMgTNJdMbnXUVSHpRxbEMD6lfpT/fdsZXVTofcS\nSUlR/MUpyRi9Lz6AqXwkia/H\n-----END PRIVATE KEY-----\n",
		                'client_email'          => env('GOOGLE_CLOUD_CLIENT_EMAIL'),
		                'client_id'             => env('GOOGLE_CLOUD_CLIENT_ID'),
		                'auth_uri'              => env('GOOGLE_CLOUD_AUTH_URI'),
		                'token_uri'             => env('GOOGLE_CLOUD_TOKEN_URI'),
		                'auth_provider_x509_cert_url' => env('GOOGLE_CLOUD_AUTH_PROVIDER_CERT_URL'),
		                'client_x509_cert_url'  => env('GOOGLE_CLOUD_CLIENT_CERT_URL'),
		            ],
	    'bucket' => env('GOOGLE_CLOUD_STORAGE_BUCKET', 'rumahaqiqah.co.id'),
	    'path_prefix' => env('GOOGLE_CLOUD_STORAGE_PATH_PREFIX', null), // optional: /default/path/to/apply/in/bucket
	    'storage_api_uri' => env('GOOGLE_CLOUD_STORAGE_API_URI', null), // see: Public URLs below
	    'visibility' => 'public', // optional: public|private
	],

];