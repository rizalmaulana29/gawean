<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\CmsUser; //File Model
use Carbon\Carbon;
use App\Mail\AgenMail;
use Illuminate\Support\Facades\Hash;

class AgenController extends Controller
{
   
    public function signup(Request $request)
    {
        $agen = new CmsUser();
        $agen->name = $request['nama'];
        $agen->email = $request['emailReseller'];
        $agen->password = Hash::make($request['password']);
        $agen->id_kantor = $request['kotaKantor'];
        $agen->id_cms_privilege = '4';
        $agen->created_at = Carbon::now();
        $agen->id_cms_privilege = '4';

        $agen->save();

        $hasil = Mail::send(
            (new AgenMail($to_address, $transdata, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$number,$title))->build()
        );

        return response('Berhasil Tambah Data');
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