<?php
namespace App\Http\Controllers;

use App\Pendapatan;
use App\Kantor;
use App\CmsUser;
use App\Anak;
use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Request;

class PendapatanController extends Controller {
    public function getpendapatan(Request $request){
        $limit = $request->input('limit',20);
        $id = $request->input('id');

        $queryPendapatan = Pendapatan::selectRaw("ra_pendapatan_dua.id, ra_pendapatan_dua.id_order, ra_pendapatan_dua.tgl_transaksi, ra_pendapatan_dua.id_kantor, ra_kantor.kantor, ra_pendapatan_dua.keterangan, ra_pendapatan_dua.id_agen, cms_users.name, ra_pendapatan_dua.note, ra_pendapatan_dua.id_anak, ra_pendapatan_dua.id_produk_parent")
        ->leftJoin('ra_kantor','ra_pendapatan_dua.id_kantor','=','ra_kantor.id')
        ->leftJoin('cms_users', 'ra_pendapatan_dua.id_agen','=','cms_users.id')
        ->whereNull('ra_pendapatan_dua.id_anak')
        ->where('ra_pendapatan_dua.id_produk_parent','=',20)
        ->when($id, function ($query) use ($id) {
            return $query->where('ra_pendapatan_dua.id', $id);
        })
        ->orderBy('ra_pendapatan_dua.tgl_transaksi', 'desc')
        ->simplePaginate($limit);

        $response = [
            'data_pendapatan' => $queryPendapatan 
        ];

        return response()->json($response);
    }

    public function getAnak(Request $request){
        $limit = $request->input('limit',20);
        $id = $request->input('id');

        $queryAnak = Anak::selectRaw("ra_anak.id, ra_anak.nama_anak, ra_anak.id_order, ra_anak.ayah, ra_anak.ibu")
        ->when($id, function ($query) use ($id) {
            return $query->where('ra_anak.id', $id);
        });

        if ($request->input('keyword')) {
            $queryAnak = $queryAnak->where(function ($query) use ($request) {
                $query->where('ra_anak.nama_anak', 'LIKE', '%' . $request->input('keyword') . '%')
                ->orWhere('ra_anak.ayah', 'LIKE', '%' . $request->input('keyword') . '%')
                ->orWhere('ra_anak.ibu', 'LIKE', '%' . $request->input('keyword') . '%');
            });
        }

        $queryAnak = $queryAnak->orderBy('id', 'desc')->paginate($limit);

    

        $response = [
        'data_anak' => $queryAnak,
        ];
        
        return response()->json($response);

    }

    public function updateIdAnak(Request $request){

        $anakQuery= Anak::where('id',$request->input('id_anak'))->first();
        if (!$anakQuery){
            return "Data Not Found";
        }

        $pendapatanQuery= Pendapatan::where('id',$request->input('id_pendapatan'))->first();

        if (!$pendapatanQuery){
            return "Data Not Found";
        }
        $pendapatanQuery->update(['id_anak'=>$request->input('id_anak')]);

        $pendapatanQuery->save();

        $response =[
            'message' => 'Success 200 OK. Data id_anak update successful'
        ];
        return response()->json($response);
    }
    

}