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

        $hasil = Mail::send(
            (new AgenMail($to_address,$nama))->build()
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
                            ->update(['password' => $password, 'status'=>'inActive']);

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
            $insertUser->status = 'inActive';
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
}