<?php

namespace App\Http\Controllers;

use App\Order;
use App\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class SendWAController extends Controller
{
    public function sendWhatsapp()
    {
        $referenceNo    = 2147498381;
        $order = Payment::where('id', $referenceNo)->first();
        $nohp = '6281289637529'; //statik HP admin
        // $nohp = $order['hp'];
        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';
        $data = array(
            "phone_no" => $nohp,
            "key"   => $key,
            "message" =>
            "Assalamualaikum Wr Wb" . ' ' . '
                            \\n' . 'Menginformasikan Bapak / Ibu' . $order['nama_customer'] . ',' . '
                            \\n' . 'Hewan Qurban Nomor 1234, sedang / akan disembelih' . '
                            \\n' . 'Untuk dokumentasi dan report akan dikirim selanjutnya setelah proses qurban selesai' . '
                            \\n' . ' Terima kasih'
        );

        // #cara consolelog diphp hehe
        // print_r([
        //     'Check data payment' => $order
        // ]);

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
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            )
        );
        $res = curl_exec($ch);
        curl_close($ch);
    }

    public function sendWhatsappManual(Request $request){
        $id = $request['id'];
        $order = Payment::where('id', $id)->first();
        // $nohp = '6281289637529';

        #dinamisasi get val HP
        $nohp = $order['hp'];
        $nama = $order['nama_customer'];
        $nominal_bayar = $order['nominal_bayar'];
        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';
        
        $data = array(
            "phone_no"=> $nohp,
            "key"   =>$key,
            "message" =>
                            "Assalamu'alaikum Ayah/Bunda".' '.$nama.', 🙏'.'
                            \\n'.'Terima kasih atas pembayaran Ayah/Bunda'.'
                            \\n'.'Dengan detail pembayaran order sebagai berikut:'.'
                            \\n'.' Order ID          : '.$id.'
                            \\n'.' Nama              : '.$nama.'
                            \\n'.' No. Hp            : '.$nohp.'

                            \\n'.' Total Pembayaran   : IDR '.number_format($nominal_bayar).'
                            \\n'.'Pembayaran dilakukan melalui:'.'


                            \\n'.'Untuk check pesanan Ayah/Bunda silahkan klik link berikut :'.'
                            \\n'.'https://order.rumahaqiqah.co.id/tracking-order.php?id='.$id.'
                            \\n'.'Butuh bantuan layanan Customer Care kami, silahkan klik link berikut:'.'
                            \\n'.'wa.me/6281370071330'.'
                            \\n'.'Ingat Order ID Anda saat menghubungi Customer Care.'.'
                            \\n'.'Terima kasih telah memilih rumahaqiqah.co.id'.'
                            \\n'.'Terima Kasih 😊🙏'
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
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string)
            )
        );
        $res = curl_exec($ch);
        curl_close($ch);
    }
}
