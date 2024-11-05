<?php

namespace App\Helpers;

use Exception;
use GuzzleHttp\Client;

class JurnalApi {
    private $hmacUsername;
    private $hmacSecret;
    private $apiUrl;

    public function __construct($environment = 'production') {
        $this->hmacUsername = 'K53pOtKTo5ga9Vij';
        $this->hmacSecret ='Bsr3sJDPY2OUnRBzdVnoJRenW5Xovdqv';
        $this->apiUrl = $environment === 'production' 
                        ? 'https://api.mekari.com'
                        : 'https://sandbox-api.mekari.com';
    }

    public function request($method, $path) {
        $signatureData = $this->generateSignature($method, $path);
        $hmacHeader = 'hmac username="' . $this->hmacUsername . '", algorithm="hmac-sha256", headers="date request-line", signature="' . $signatureData['signature'] . '"';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Tambahkan opsi ini untuk menonaktifkan verifikasi SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $hmacHeader,
            'Date: ' . $signatureData['date'],
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        
        if(curl_errno($ch)) {
            throw new \Exception('cURL Error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        return $response;
    }

    private function generateSignature($method, $path) {
        $dateString = gmdate('D, d M Y H:i:s') . ' GMT';
        $requestLine = $method . ' ' . $path . ' HTTP/1.1';
        $dataToSign = 'date: ' . $dateString . "\n" . $requestLine;
        $signature = base64_encode(hash_hmac('sha256', $dataToSign, $this->hmacSecret, true));

        return [
            'signature' => $signature,
            'date' => $dateString
        ];
    }
}