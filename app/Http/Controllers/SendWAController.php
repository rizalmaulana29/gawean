<?php

namespace App\Http\Controllers;

use App\Order;
use App\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Paymeth;
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

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'invalidInput!', 'statusname' => 'no order'], 400);
        }

        if (!$order->hp) {
            return response()->json(['status' => false, 'message' => 'invalidInput!', 'statusname' => 'no wa'], 400);
        }

        $nohp = $order->hp;
        $nohp  = $this->numhp0to62($nohp);
        // $nohp = '6282120760818'; //statik HP admin
        // $nohp = '6285163040803'; //statik HP admin
        // return response()->json(['status' => true, 'message' => "Success Send Notif Sembelih $nohp", 'statusname' => 'sent']);

        $url = 'http://116.203.92.59/api/async_send_message';

        if ($request->input("notif") == "report") {
            $data = $this->messageFormat($nohp, $order, $request->input("notif"));
        } else {
            $data = $this->messageFormat($nohp, $order);
        }

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

        if ($err) {
            return response()->json(['status' => false, 'message' => "$err $nohp", 'statusname' => 'fail']);
        } else {
            return response()->json(['status' => true, 'message' => "Success Send Notif Sembelih $nohp", 'statusname' => 'sent']);
        }
    }

    private function numhp0to62($nohp)
    {
        if (!preg_match('/[^+0-9]/', trim($nohp))) {
            // cek apakah no hp karakter 1-3 adalah +62
            $nohp = str_replace("+", "", $nohp);
            if (substr(trim($nohp), 0, 1) == 0) {
                $nohp = substr_replace($nohp, "62", 0, 1);
            }
        }
        return $nohp;
    }

    private function messageFormat($nohp, $order, $type = "notifSembelih")
    {
        $data = array(
            "id" => $order->id
        );

        $payloads = json_encode($data);
        $payloads = openssl_encrypt($payloads, "aes128", "Bis5M1ll4h4ll4hu99", 0, "4lL4hu4k84rkA81R");

        $linkDownloadReport     = "https://api.rumahaqiqah.co.id/api/download/qurban/report/" . urlencode($payloads);
        $linkDownloadSertifikat = "https://api.rumahaqiqah.co.id/api/download/qurban/certificate/" . urlencode($payloads);

        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        if ($type == "notifSembelih") {
            $data = array(
                "phone_no" => $nohp,
                "key"   => $key,
                "message" =>
                "Assalamualaikum Wr Wb" . '
                \\n' . 'Menginformasikan Bapak / Ibu ' . $order->nama_customer . ',' . '
                \\n' . 'Hewan Qurban Dengan ID Order ' . $order->id_transaksi . ' , Telah Selesai di Sembelih' . '
                \\n' . 'Berikut Sertifikat Qurban Bapak / Ibu ' . '
                \\n' . $linkDownloadSertifikat . '
                \\n' . 'Terima kasih' . '
                \\n' . 'Rumah Qurban'
            );
        } else {
            $data = array(
                "phone_no" => $nohp,
                "key"   => $key,
                "message" =>
                "Assalamualaikum Wr Wb" . '
                \\n' . 'Menginformasikan Bapak / Ibu ' . $order->nama_customer . ',' . '
                \\n' . 'Hewan Qurban Dengan ID Order ' . $order->id_transaksi . ' , Proses Qurban sudah selesai dilaksanakan' . '
                \\n' . 'Berikut Dokumentasi dan Report Hewan Qurban Bapak / Ibu:' . '
                \\n' . $linkDownloadReport . '
                \\n' . 'Terima kasih' . '
                \\n' . 'Rumah Qurban'
            );
        }

        return $data;
    }

    public function sendWhatsappManual(Request $request)
    {
        $id = $request['id'];
        $order = Payment::where('id', $id)->first();
        // $nohp = '6281289637529';

        if(!$order){
            return response()->json(['status' => false, 'message' => "No Data Found"],404);
        }

        $paymeth = Paymeth::where("id",$order->id_payment_method)->first();
        $paymeth = $paymeth ? $paymeth->keterangan : "Payment Belum ditemukan";
        #dinamisasi get val HP
        $id_order = $order['id_transaksi'];
        $nohp = $order['hp'];
        $nama = $order['nama_customer'];
        $nominal_bayar = $order['nominal_bayar'];
        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';
        
        if($order['varian'] == "Aqiqah"){
            $data = array(
                "phone_no" => $nohp,
                "key"   => $key,
                "message" =>
                "Assalamu'alaikum Ayah/Bunda" . ' ' . $nama . ', 🙏' . '
                                \\n' . 'Terima kasih atas pembayaran Ayah/Bunda' . '
                                \\n' . 'Dengan detail pembayaran order sebagai berikut:' . '
                                \\n' . ' Order ID          : ' . $id_order . '
                                \\n' . ' Nama              : ' . $nama . '
                                \\n' . ' No. Hp            : ' . $nohp . '

                                \\n' . ' Total Pembayaran   : IDR ' . number_format($nominal_bayar) . '
                                \\n' . 'Pembayaran dilakukan melalui:' . '
                                \\n' . $paymeth . '

                                \\n' . 'Untuk check pesanan Ayah/Bunda silahkan klik link berikut :' . '
                                \\n' . 'https://order.rumahaqiqah.co.id/tracking-order.php?id=' . $id_order . '
                                \\n' . 'Butuh bantuan layanan Customer Care kami, silahkan klik link berikut:' . '
                                \\n' . 'wa.me/6282218757703' . '
                                \\n' . 'Ingat Order ID Anda saat menghubungi Customer Care.' . '
                                \\n' . 'Terima kasih telah memilih rumahaqiqah.co.id' . '
                                \\n' . 'Terima Kasih 😊🙏'
            );
        } else {
            $data = array(
                "phone_no" => $nohp,
                "key"   => $key,
                "message" =>
                "Assalamu'alaikum Bapak/Ibu" . ' ' . $nama . ', 🙏' . '
                                \\n' . 'Terima kasih atas pembayaran Bapak/Ibu' . '
                                \\n' . 'Dengan detail pembayaran order sebagai berikut:' . '
                                \\n' . ' Order ID          : ' . $id_order . '
                                \\n' . ' Nama              : ' . $nama . '
                                \\n' . ' No. Hp            : ' . $nohp . '

                                
                                \\n' . 'Pembayaran dilakukan melalui:' . '
                                \\n' . $paymeth . '

                                \\n' . 'Butuh bantuan layanan Customer Care kami, silahkan klik link berikut:' . '
                                \\n' . 'wa.me/6282218757703' . '
                                \\n' . 'Ingat Order ID Anda saat menghubungi Customer Care.' . '
                                \\n' . 'Terima kasih telah memilih Platform Kami.' . '
                                \\n' . 'Terima Kasih 😊🙏'
            );
        }

         /**  \\n'.' Total Pembayaran   : IDR '.number_format($payment['nominal_bayar']).'*/

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

    public function sendWhatsappVOC()
{
    /*
    $today = Carbon::now();

    
    $twoDaysAfter = $today->addDays(2);

    
    $orders = Payment::whereDate('tgl_kirim', $twoDaysAfter)
                     ->whereNull('send_voc')->first();*/
     $id = '2003030022851';
     $order = Payment::where('id', $id)->first();
    /*
    if ($orders->isEmpty()) {
        return response()->json(['status' => false, 'message' => "No Data Found or send_voc is not empty"], 404);
    }*/

    if ($order) {
        $id_order = $order->id_transaksi;
        $nohp = '6281462206437';
        $nama = $order->nama_customer;
        
        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';

        $data = array(
            "phone_no" => $nohp,
            "key" => $key,
            "message" =>
            "Assalamu'alaikum Ayah/Bunda " . ' ' . $nama . ', yang berbahagia 🙏' . '
            \\n' . 'Terima kasih sudah mempercayakan aqiqah kepada Rumah Aqiqah.' . '
            \\n' . 'Agar kami dapat terus meningkatkan pelayanan, mohon kesediaannya meluangkan waktu untuk mengisi survey dengan klik link berikut: http://rumahaqiqah.co.id/survey?id_order=' . $id_order . '
            \\n' . ' Semoga partisipasi Ayah/Bunda menjadi amal kebaikan dan dibalas oleh Allah SWT. Aamiin.' . '
            \\n' . 'Terima Kasih 😊🙏' . '
            \\n' . 'Waasalamualaikum ' 
           
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

        // Set send_voc to 1 after cURL request
        $order->update(['send_voc' => 1]);
    }

    return response()->json(['status' => true, 'message' => "Data Sent and updated send_voc to 1 for eligible orders"], 200);
}
}
