<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\CmsUser; //File Model
use Illuminate\Support\Facades\Hash;

class AgenController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    { }

    public function index()
    {
        $data = CmsUser::all();
        return response($data);
    }

    public function show($id)
    {
        $data = CmsUser::where('id', $id)->get();
        return response($data);
    }

    public function store(Request $request)
    {
        $data = new CmsUser();
        $data->name = $request->input('nama');
        $data->email = $request->input('emailReseller');
        $data->password = Hash::make($request->input('password'));
        dd($data->password);
        // $data->description = $request->input('noHpReseller');
        $data->id_kantor = $request->input('kotaKantor');
        $data->save();

        return response('Berhasil Tambah Data');
    }

    public function update(Request $request, $id)
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