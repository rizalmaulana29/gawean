<?php

namespace App\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\ExpiredException;
use Exception;

class JWT
{
    // a week
    static function Sign($body, $expire=630000)
    {
        $payload = [
            'iss' => "api-rumah-aqiqah",
            'sub' => $body,
            'iat' => time(),
            'exp' => time() + $expire
        ];
        
        return FirebaseJWT::encode($payload, env('JWT_SECRET'));
    }

    static function Decode($token){
        return FirebaseJWT::decode($token,env('JWT_SECRET'),['HS256']);
    }
}
