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

class AgenController extends Controller
{
   
    public function signup(Request $request)
    {
        $agen              = new Kontak();
        $agen->nama_kontak = $request['nama'];
        $agen->email       = $request['email'];
        $agen->hp          = $request['hp'];
        $agen->status      = 'Agen';
        $agen->id_kantor   = $request['kotaKantor'];
        $agen->tgl_reg     = Carbon::now();

        $agen->save();

        $updateCMS  = $this->userCms($request);

        $to_address = $request['email'];
        $nama       = $request['nama'];

        $sendWa = $this->sendWa($nama, $to_address,$request['password'], $request['hp']);

        $hasil = Mail::send(
            (new AgenMail($to_address,$nama, $request['password']))->build()
        );

        return response('Berhasil Tambah Data');
    }

    private function userCMS($request){
        $checkUser = CmsUser::where('name', $request['nama'])
                            ->where('id_cms_privileges',4)
                            ->where('id_kantor',$request['kotaKantor'])
                            ->first();
        if ($checkUser || $checkUser != null) {
            $password   = Hash::make($request['password']);
            $updateUser = CmsUser::where('name', $request['nama'])
                            ->where('id_cms_privileges',4)
                            ->where('id_kantor',$request['kotaKantor'])
                            ->update(['password' => $password, 'status'=>'Active']);

            if (!$updateUser) {
                $response = ["status" =>False,
                             "message"=> 'Gagal Mengupdate Data ke CMS User'];
            } else {
                $response = ["status" =>true,
                             "message"=> 'Berhasil Mengupdate Data ke CMS User'];
            }

        } else {
            $insertUser       = new CmsUser();
            $insertUser->name = $request['nama'];
            $insertUser->email = $request['email'];
            $insertUser->password = Hash::make($request['password']);
            $insertUser->id_kantor = $request['kotaKantor'];
            $insertUser->id_cms_privileges = 4;
            $insertUser->created_at = Carbon::now();
            $insertUser->status = 'Active';
            $insertUser->save();

            if (!$insertUser) {
                $response = ["status" =>False,
                             "message"=> 'Gagal Menambahkan Data ke CMS User'];
            } else {
                $response = ["status" =>true,
                             "message"=> 'Berhasil Menambahkan Data ke CMS User'];
            }
            
        }

        return $response;
        
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

    public function sendWa($nama, $to_address, $password, $hp){
        if (substr($hp,0,1) == 0) {
        $nohp = str_replace('0','+62',$hp);
        }

        else {
            $nohp = $hp;
        }

        $key='c9555ab1745ebbe2521611d931cbfd2bf9f39437404f9b26';
        $url='http://116.203.92.59/api/async_send_message';

        
            $data = array(
                    "phone_no"=> $nohp,
                    "key"   =>$key,
                    "message" =>
                                    "Assalamu'alaikum Bapak/Ibu".' '.$nama.', 🙏'.'
                                    \\n'.'Selamat bergabung di Perwira Agro Academy '.'
                                    \\n'.' Berikut Akses Login:'.'
                                    \\n'.' URL               : https://backend.rumahaqiqah.co.id/admin/login
                                    \\n'.' Nama              : '.$nama.'
                                    \\n'.' Email             : '.$to_address.'
                                    \\n'.' Password             : '.$password.'
                                    \\n'.' No. Hp            : '.$hp.'
                                    \\n'.'  
                                    \\n'.'
                                    \\n'.'Butuh bantuan layanan Customer Care kami, silahkan klik link berikut:'.'
                                    \\n'.'wa.me/628112317711'.'
                                    \\n'.'Ingat Order ID Anda saat menghubungi Customer Care.'.'
                                    \\n'.'Terima kasih telah memilih rumahqurban.id'.'
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
        );
        $res=curl_exec($ch);
        curl_close($ch);
    }
}