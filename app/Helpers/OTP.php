<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class OTP
{
    //trigger ulang
    static $URL = "https://otp.cinte.id";
    static $authKey = "k2Skd19KsJ4Mnl5smX";

    static function Verify($UUID, $code)
    {
        $url = self::$URL . '/' . $UUID;
        $response = Http::withHeaders([
            'Authorization' => self::$authKey
        ])->asForm()->post($url, [
            'code' => $code
        ]);

        if ($response->failed()) {
            return false;
        }

        $result = $response->json();

        return $result['valid'];
    }
}
