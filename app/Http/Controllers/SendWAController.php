<?php

namespace App\Http\Controllers;

use App\Order;
use App\Payment;
use App\Kantor;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Paymeth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

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
        $id_kantor = $request['id_kantor'];
        $order = Payment::where('id', $id)->first();
        // $nohp = '6281289637529';

        if (!$order) {
            return response()->json(['status' => false, 'message' => "No Data Found"], 404);
        }

        $paymeth = Paymeth::where("id", $order->id_payment_method)->first();
        $paymeth = $paymeth ? $paymeth->keterangan : "Payment Belum ditemukan";
        #dinamisasi get val HP
        $id_order = $order['id_transaksi'];
        $nohp = $order['hp'];
        $nama = $order['nama_customer'];
        $nominal_bayar = $order['nominal_bayar'];
        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';

        if ($order['varian'] == "Aqiqah") {
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

        $this->sendWhatsappKaryawan($id_kantor, $id, $id_order);
    }

    public function sendWhatsappKaryawan($id_kantor, $id, $id_order)
    {
        // kantor nya harus ada
        if ($id_kantor) {
            $kantor = Kantor::where('id', $id_kantor)->first();
            $order = Payment::where('id', $id)->first();
            // no hp nya harus ada
            if ($kantor->tlp) {
                $url_wa = 'http://116.203.191.58/api/send_message';
                $header = [
                    'Content-Type: application/json',
                ];
                $data = [
                    "phone_no" => $kantor->tlp,
                    "key" => "c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26",
                    "message" => "Assalamu'alaikum Cabang " . ' ' . $kantor->kantor . ', yang berbahagia 🙏' . '
            \\n' . 'Saat ini Cabang ' . ' ' . $kantor->kantor . ' mendapatkan Pesanan Aqiqah Baru dengan info : ' . '
            \\n' . ' ID ORDER   : ' . $id_order . '
            \\n' . ' NAMA CUST  : ' . $order['nama_customer'] . '
            \\n' . ' TGL KIRIM  : ' . $order['tgl_kirim'] . '
            \\n' . ' JAM SAMPAI : ' . $order['waktu_kirim'] . '
            \\n' . ' Segera lakukan Konfirmasi ke Konsumen untuk memastikan pesanan sudah sesuai atau update pesanan' . '
            \\n' . ' wa.me/'.$order['hp'].'
            \\n' . 'Terima Kasih 😊🙏' . '
            \\n' . 'Waasalamualaikum ',
                ];

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url_wa);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    $result = 'Curl error: ' . curl_error($ch);
                }

                curl_close($ch);

                $url_wa_file = 'http://116.203.191.58/api/send_file_url';
                $data_file = array(
                    "phone_no" => $kantor->tlp,
                    "key" => "c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26",
                    "url" => "https://backend.rumahaqiqah.co.id/admin/detail/" . $id,
                );

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url_wa_file);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_file));
                curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    $result = 'Curl error: ' . curl_error($ch);
                }

                curl_close($ch);
            }
        }
    }

    public function sendWhatsappVOC()
    {

        $today = Carbon::now();


        $twoDaysAfter = $today->subDays(1);


        $order = Payment::whereDate('tgl_kirim', $twoDaysAfter)
            ->whereNull('send_voc')
            ->where('tipe', 'transaksi')
            ->where('varian', 'Aqiqah')
            ->where('lunas', 'y')
            ->first();


        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found'], 404);
        }

        $id_order = $order->id_transaksi;
        $nohp = $order['hp'];
        //$nohp = '6281462206437';
        $nama = $order->nama_customer;
        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.191.58/api/async_send_message';

        $data = array(
            "phone_no" => $nohp,
            "key" => $key,
            "message" =>
            "Assalamu'alaikum Ayah/Bunda " . ' ' . $nama . ', yang berbahagia 🙏' . '
            \\n' . 'Terima kasih sudah mempercayakan aqiqah kepada Rumah Aqiqah.' . '
            \\n' . 'Agar kami dapat terus meningkatkan pelayanan, mohon kesediaannya meluangkan waktu untuk mengisi survey dengan klik link berikut: https://sistemorder2-asp-fe-dev2.cnt.id/survey?id_order=' . $id_order . '
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

        $responseFromCurl = curl_exec($ch);

        if ($responseFromCurl === false) {
            return response()->json(['status' => false, 'message' => 'Curl error: ' . curl_error($ch)], 500);
        }

        curl_close($ch);
        $order->update(['send_voc' => 1]);

        // Kembalikan respons cURL secara langsung
        return response($responseFromCurl, 200);
    }
}
