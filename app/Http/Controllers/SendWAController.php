<?php

namespace App\Http\Controllers;

use App\Order;
use App\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SendWAController extends Controller
{
    public function sendWhatsapp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "id_ra_payment" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'invalidInput', 'statusname' => 'no id'], 400);
        }
        $referenceNo    = $request->input("id_ra_payment"); #2147498381
        $order = Payment::where('id', $referenceNo)->first();

        if(!$order){
            return response()->json(['status' => false, 'message' => 'invalidInput!', 'statusname' => 'no order'], 400);
        }
        
        if(!$order->hp){
            return response()->json(['status' => false, 'message' => 'invalidInput!', 'statusname' => 'no wa'], 400);
        }

        // $nohp = $order->hp;
        // $nohp = '6281289637529'; //statik HP admin
        $nohp = '6282120760818'; //statik HP admin
        // var_dump($nohp);
        
        // dd($order->nama_customer);
        // return response()->json(['status' => true, 'message' => "Success Send Notif Sembelih $nohp", 'statusname' => 'sent']);
        
        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';
        $data = array(
            "phone_no" => $nohp,
            "key"   => $key,
            "message" =>
            "Assalamualaikum Wr Wb" . ' ' . '
                            \\n' . 'Menginformasikan Bapak / Ibu' . $order->nama_customer . ',' . '
                            \\n' . 'Hewan Qurban Nomor'.$order->id_transaksi.' , sedang / akan disembelih' . '
                            \\n' . 'Untuk dokumentasi dan report akan dikirim selanjutnya setelah proses qurban selesai' . '
                            \\n' . 'Terima kasih'
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
        $err = curl_error($ch);
        curl_close($ch);

        if($err){
            return response()->json(['status' => false, 'message' => $err, 'statusname' => 'fail']);
        }else{
            return response()->json(['status' => true, 'message' => "Success Send Notif Sembelih ", 'statusname' => 'sent']);
        }
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
