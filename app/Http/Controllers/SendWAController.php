<?php

use App\Http\Controllers\Controller;

class sendWANotification extends Controller
{

    public function sendWhatsapp()
    {
        $nama = 'Iqbal';
        $nohp = '081289637529';
        $key='c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url='http://116.203.92.59/api/async_send_message';
        $data = array(
            "phone_no" => $nohp,
            "key"   => $key,
            "message" =>
            "Assalamualaikum Wr Wb" . ' ' . '
                            \\n' . 'Menginformasikan Bapak / Ibu' . $nama . ',' . '
                            \\n' . 'Hewan Qurban Nomor 1234, sedang / akan disembelih' . '
                            \\n' . 'Untuk dokumentasi dan report akan dikirim selanjutnya setelah proses qurban selesai' . '
                            \\n' . ' Terima kasih'
        );
        $data_string = json_encode($data);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 360);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
        );
        $res=curl_exec($ch);
        curl_close($ch);

    }
   }
