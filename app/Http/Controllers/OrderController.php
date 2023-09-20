<?php

namespace App\Http\Controllers;

use App\Payment;
use App\PaymentMethod;
use App\Kantor;
use App\AdminEntitas;
use App\CmsUser;

use Illuminate\Http\Request;

class OrderController extends Controller{
    public function order (Request $request){
        
        $page = $request->input('page'); // Halaman saat ini dari permintaan
        $perPage = 20; // Jumlah item per halaman

        // Hitung offset berdasarkan halaman saat ini
        $offset = ($page - 1) * $perPage;

        $id_kantor = $request->input('id_kantor');
        $tgl_trx_awal = $request->input('tgl_trx_awal');
        $tgl_trx_akhir = $request->input('tgl_trx_akhir');
        $nama_customer=  $request->input('nama_customer');

        $queryOrder = Payment::selectRaw("ra_payment_dua.id, ra_payment_dua.id_transaksi, ra_payment_dua.varian, ra_payment_dua.jenis, ra_payment_dua.tgl, ra_payment_dua.tgl_transaksi, ra_payment_method.payment_method, ra_payment_dua.nama_customer,  ra_payment_dua.hp, ra_payment_dua.email, ra_payment_dua.alamat, ra_kantor.kantor, ra_payment_dua.id_kantor, ra_payment_dua.nominal, ra_payment_dua.nominal_diskon, ra_payment_dua.nominal_total, ra_payment_dua.nominal_bayar, ra_payment_dua.sisa_pembayaran, ra_payment_dua.status, admin_entitas.entitas as pt, cms_users.name as agen, ra_payment_dua.tgl_kirim, ra_sumber_informasi.sumber_informasi")
        ->leftJoin('ra_payment_method', 'ra_payment_dua.id_payment_method','=','ra_payment_method.id')
        ->leftJoin('ra_kantor','ra_payment_dua.id_kantor','=','ra_kantor.id')
        ->leftJoin('admin_entitas','ra_payment_dua.id_pt','=','admin_entitas.id')
        ->leftJoin('cms_users','ra_payment_dua.id_agen','=','cms_users.id')
        ->leftJoin('ra_sumber_informasi','ra_payment_dua.sumber_informasi','=','ra_sumber_informasi.id')
        ->orderBy('ra_payment_dua.tgl','desc');

        if ($request->input('keyword')) {
            $queryOrder = $queryOrder->where('ra_kantor.kantor', 'LIKE', '%' . $request->input('keyword').'%')
            ->orWhere ('ra_payment_dua.nama_customer', 'LIKE', '%' . $request->input('keyword').'%');
        }

        if($tgl_trx_awal && $tgl_trx_akhir){
            $queryOrder -> whereBetween('ra_payment_dua.tgl',[$tgl_trx_awal, $tgl_trx_akhir]);
        }

        if($id_kantor){
            $queryOrder =  $queryOrder->where('ra_payment_dua.id_kantor', '=', $request->input('id_kantor'));
        }
        // if ($tgl_trx_range) {
        //     $tgl_range = explode(',', $tgl_trx_range);
        //     $tgl_trx_awal = $tgl_range[0];
        //     $tgl_trx_akhir = $tgl_range[1];

        //     $query->whereBetween('tgl', [$tgl_trx_awal, $tgl_trx_akhir]);
        // }
        // if ($nama_customer){
        //     $query->where('nama_customer', $nama_customer);
        // }
        $totalorder = $queryOrder->count(); 

        $order = $queryOrder->skip($offset)->take($perPage)->get();
        
        return response()->json([
            'data' => $order,
            'total' => $totalorder // Menambahkan total data hasil pencarian
        ]);
    }
}