<?php
namespace App\Helpers;


class APILegacy {
    static $donolEncryptionKey = "3378637a376d4864323663663335646d78373244644d4e33646c374e64323147";
    static $donolIV = "337863337a6232376c6d486463323663"; 

    // Jika Encrypt data sesuaikan timezone dengan server yang decryptnya Ex: CS2: Wib
    static function DonolEncrypt($message){
        return base64_encode(openssl_encrypt($message, "aes-256-cbc", hex2bin(self::$donolEncryptionKey), OPENSSL_RAW_DATA, hex2bin(self::$donolIV)));
    }

    static function DonolDecrypt($message){
        return openssl_decrypt(base64_decode($message), "aes-256-cbc", hex2bin(self::$donolEncryptionKey), OPENSSL_RAW_DATA, hex2bin(self::$donolIV));
    }
}

