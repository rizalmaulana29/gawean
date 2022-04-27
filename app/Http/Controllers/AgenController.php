<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CmsUser; //File Model
use App\Kontak;
use Carbon\Carbon;
use App\Mail\AgenMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AgenController extends Controller
{

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "nama" => "required",
            "email" => "required",
            "hp" => "required",
            "password" => "required",
            "kotaKantor" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'invalidInput'], 400);
        }
        $token_email_verify = substr(base64_encode(sha1(mt_rand())), 0, 36);

        $createCMS  = $this->userCms($request, $token_email_verify);
        if (!$createCMS["status"]) {
            return response()->json($createCMS);
        }

        $to_address = $request['email'];
        $nama       = $request['nama'];

        $link_email_verify = "https://api.rumahaqiqah.co.id/api/email/verify?payloads=" . $token_email_verify;

        $sendWa = $this->sendWa($nama, $to_address, $request['password'], $request['hp'], $link_email_verify);

        $hasil = Mail::send(
            (new AgenMail($to_address, $nama, $request['password'], $link_email_verify))->build()
        );

        return response()->json([
            "status" => true,
            "message" => 'Berhasil Tambah Data'
        ]);
    }

    private function userCMS($request, $token_email_verify)
    {
        $checkUser = CmsUser::where('email', $request['email'])
            ->first();
        if (!$checkUser) {
            $insertUser       = new CmsUser();
            $insertUser->name = $request['nama'];
            $insertUser->email = $request['email'];
            $insertUser->password = Hash::make($request['password']);
            $insertUser->id_kantor = $request['kotaKantor'];
            $insertUser->id_cms_privileges = 4;
            $insertUser->created_at = Carbon::now();
            $insertUser->status = 'inActive';
            $insertUser->token_email_verification = $token_email_verify;
            $insertUser->id_parent_agen = $request['source'] ?? null ? $request['source'] : null;
            $insertUser->save();

            if (!$insertUser) {
                $response = [
                    "status" => false,
                    "message" => 'Gagal Menambahkan Data ke CMS User'
                ];
            } else {
                $agen               = new Kontak();
                $agen->id_agen      = $insertUser->id;
                $agen->nama_kontak  = $request['nama'];
                $agen->email        = $request['email'];
                $agen->hp           = $request['hp'];
                $agen->status       = 'Agen';
                $agen->id_kantor    = $request['kotaKantor'];
                $agen->tgl_reg      = Carbon::now();

                $agen->save();

                $response = [
                    "status" => true,
                    "message" => 'Berhasil Menambahkan Data ke CMS User'
                ];
            }
        } else {
            $response = [
                "status" => false,
                "message" => 'User dgn email tsb sudah terdaftar'
            ];
        }

        return $response;
    }

    public function verifyEmail(Request $request)
    {
        $root_url   = "https://beta.kawandagang.id/agen/login";
        $url        = $root_url . "?verified=fail";

        $validator = Validator::make($request->all(), [
            "payloads" => "required",
        ]);
        
        if ($validator->fails()) {
            return redirect()->to($url);
            // return response()->json(['status' => false, 'message' => 'invalidInput'], 400);
        }

        $payloads = $request->input("payloads") ?? null ? $request->input("payloads") : null;
        if (!$payloads) return redirect()->to($url);
        //return response()->json(["status"=>false, "message"=>"invalidInput"], 400);

        $verify_email = CmsUser::where("token_email_verification", $payloads)->first();
        if (!$verify_email) return redirect()->to($url);
        // return response()->json(["status"=>false, "message"=>"invalidInput"], 400);

        $url = $root_url . "?verified=done";
        $verified = $verify_email->email_verified_at;
        if ($verified) return response()->json(["status" => false, "message" => "Your Email Has Been Verified"], 400);

        $now = Carbon::now()->toDateTimeString();
        $verify_email->update([
            "email_verified_at" => $now,
            "status" => "Active"
        ]);

        $url = $root_url . "?verified=y";
        return redirect()->to($url);
    }

    public function forgotPassword(Request $request, $id)
    {
        $data = CmsUser::where('id', $id)->first();
        $data->name = $request->input('nama');
        $data->email = $request->input('emailReseller');
        $data->password = $request->input('password');
        // $data->description = $request->input('noHpReseller');
        $data->id_kantor = $request->input('kotaKantor');
        $data->save();

        return response('Berhasil Merubah Data');
    }

    public function destroy($id)
    {
        $data = CmsUser::where('id', $id)->first();
        $data->delete();

        return response('Berhasil Menghapus Data');
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

    public function sendWa($nama, $to_address, $password, $hp, $link_email_verify)
    {
        if (substr($hp, 0, 1) == 0) {
            $nohp = $this->numhp0to62($hp);
        } else {
            $nohp = $hp;
        }

        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';


        $data = array(
            "phone_no" => $nohp,
            "key"   => $key,
            "message" =>
            "Assalamu'alaikum Bapak/Ibu" . ' ' . $nama . ', 🙏' . '
                                    \\n' . 'Selamat bergabung di Kawan Dagang ' . '
                                    \\n' . ' Silahkan untuk melakukan AKTIVASI USER terlebih dahulu di email yang sudah didaftarkan, sebelum Anda bisa login.
                                    \\n' . ' Berikut Akses Login anda:' . '
                                    \\n' . ' URL      : https://beta.kawandagang.id/admin/login
                                    \\n' . ' Nama     : ' . $nama . '
                                    \\n' . ' Email    : ' . $to_address . '
                                    \\n' . ' Password : ' . $password . '
                                    \\n' . ' No. Hp   : ' . $hp . '
                                    \\n' . '
                                    \\n' . '  benefit dari Kawan Dagang diantaranya sebagai  berikut:
                                    \\n' . '1. Mendapatkan harga super hemat hingga 30% dibandingkan harga pasaran
                                    \\n' . '2. Dilatih, dibimbing dan didampingi oleh coaches serta mentor keren untuk berjualan
                                    \\n' . '3. Gratis materi promosi
                                    \\n' . '4. Kemudahan mendapatkan produk yang tersebar di Pulau Jawa dan Sumatera
                                    \\n' . '5. Berhak mengikuti Reward (Umroh, Trip to Turkey, serta hadiah menarik lainnya)
                                    
                                    \\n' . 'Ajak juga keluarga, rekan, sahabat serta teman kamu untuk ikut bergabung bersama Perwira Agro Academy untuk mendapatkan manfaatnya.
                                    \\n' . 'Kawan Dagang
                                    
                                    \\n' . 'Saatnya Kamu Jadi Miliarder
                                    \\n' . '
                                    \\n' . 'Butuh bantuan layanan Customer Care kami, silahkan klik link berikut:' . '
                                    \\n' . 'wa.me/628112317711' . '
                                    \\n' . 'Terima Kasih 😊🙏'
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

    public function sendWaBackUp($nama, $to_address, $password, $hp, $link_email_verify)
    {
        if (substr($hp, 0, 1) == 0) {
            $nohp = $this->numhp0to62($hp);
        } else {
            $nohp = $hp;
        }

        $key = 'c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url = 'http://116.203.92.59/api/async_send_message';


        $data = array(
            "phone_no" => $nohp,
            "key"   => $key,
            "message" =>
            "Assalamu'alaikum Bapak/Ibu" . ' ' . $nama . ', 🙏' . '
                                    \\n' . 'Selamat bergabung di Perwira Agro Academy ' . '
                                    \\n' . ' Berikut Akses Login anda:' . '
                                    \\n' . ' URL      : https://backend.rumahaqiqah.co.id/admin/login
                                    \\n' . ' Nama     : ' . $nama . '
                                    \\n' . ' Email    : ' . $to_address . '
                                    \\n' . ' Password : ' . $password . '
                                    \\n' . ' No. Hp   : ' . $hp . '
                                    \\n' . '
                                    \\n' . '  benefit dari PAA diantaranya sebagai  berikut:

                                    \\n' . '1. Mendapatkan harga super hemat hingga 30% dibandingkan harga pasaran
                                    
                                    \\n' . '2. Dilatih, dibimbing dan didampingi oleh coaches serta mentor keren untuk berjualan
                                    
                                    \\n' . '3. Gratis materi promosi
                                    
                                    \\n' . '4. Kemudahan mendapatkan produk yang tersebar di Pulau Jawa dan Sumatera
                                    
                                    \\n' . '5. Berhak mengikuti Perwira Agro Reward (Umroh, Trip to Turkey, serta hadiah menarik lainnya)
                                    
                                    \\n' . 'Ajak juga keluarga, rekan, sahabat serta teman kamu untuk ikut bergabung bersama Perwira Agro Academy untuk mendapatkan manfaatnya.
                                    
                                    \\n' . 'Perwira Agro Academy
                                    
                                    \\n' . 'Saatnya Kamu Jadi Miliarder
                                    \\n' . '
                                    \\n' . 'Butuh bantuan layanan Customer Care kami, silahkan klik link berikut:' . '
                                    \\n' . 'wa.me/628112317711' . '
                                    \\n' . 'Terima Kasih 😊🙏'
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
