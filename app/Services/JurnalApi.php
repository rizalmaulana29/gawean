<?php

namespace App\Services;

use Exception;

class JurnalApi {
    private $hmacUsername;
    private $hmacSecret;
    private $apiUrl;

    public function __construct($environment = 'production') {
        $this->hmacUsername = 'K53pOtKTo5ga9Vij'; // Client ID sebagai hmacUsername
        $this->hmacSecret = 'Bsr3sJDPY2OUnRBzdVnoJRenW5Xovdqv'; // Client Secret sebagai hmacSecret

        // Set environment (default ke sandbox)
        $this->apiUrl = $environment === 'production' 
                        ? 'https://sandbox-api.mekari.com'
                        : 'https://api.mekari.com';
    }

    private function generateSignature($method, $path) {
        $dateString = gmdate('D, d M Y H:i:s') . ' GMT'; // Format tanggal GMT
        $requestLine = $method . ' ' . $path . ' HTTP/1.1'; // Membangun request line
        $dataToSign = 'date: ' . $dateString . "\n" . $requestLine; // Data yang akan ditandatangani
        $signature = base64_encode(hash_hmac('sha256', $dataToSign, $this->hmacSecret, true)); // Membuat signature

        return [
            'signature' => $signature,
            'date' => $dateString
        ];
    }

    public function request($method, $path) {
        // Generate signature untuk permintaan
        $signatureData = $this->generateSignature($method, $path);

        // Menyusun header Authorization
        $hmacHeader = 'hmac username="' . $this->hmacUsername . '", algorithm="hmac-sha256", headers="date request-line", signature="' . $signatureData['signature'] . '"';

        // Inisialisasi cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $path); // Set URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Set agar mengembalikan hasil sebagai string
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $hmacHeader,
            'Date: ' . $signatureData['date'],
            'Accept: application/json'
        ]);

        // Eksekusi cURL
        $response = curl_exec($ch);
        
        // Cek apakah ada error
        if (curl_errno($ch)) {
            throw new Exception('cURL Error: ' . curl_error($ch)); // Menangkap error jika ada
        } else {
            return $response; // Mengembalikan hasil response
        }
        
        // Menutup cURL
        curl_close($ch);
    }
}
