<?php

namespace App\Http\Controllers;
// use App\Harga;
// use App\Kantor;
// use App\Produk;
use App\Payment;
use App\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CodeController extends Controller
{
    // public function generateCode(Request $request){

    // $order = new Order;
    // $order->id_order = date("ymd") . $request->input('id_kantor') . str_pad(1, 4, '0', STR_PAD_LEFT) ;
    // $order->id_kantor = $request->input('id_kantor');
    // $order->tgl_transaksi = Carbon::now()->addHour(7);
    // $order->save();
    // $code = $order->id_order;
    // // dd($code);
    // return response()->json(["status" => "success",
    //                          "code" => $code
    //                         ],200);
  // }

  public function unicCode (Request $request){
    $this->validate($request, [
            'token' => 'required'
        ]);

        if (!$request->input('nominal')) {
            $nominal = 0;
        }
        else {
            $nominal = $request->input('nominal');
        }

        if ($request->input('token') !='8a3bd0da012a2d74838d03f719dede96') {
            return response()->json(['Status' => 'Error Access Denied']);
        }

        // $rekening = str_replace(".","",$request->input('rekening'));
        // if (CorezTransaksiScrap::where('temp_rek_tujuan', '=', $rekening)->doesntExist()){
        //     return response()->json(['Status' => 'Rekening Tujuan Tidak dikenali']);
        // }

        // Generate unik number
        $kode_unik = $this->generateNumber($nominal);
        $kode_unik = sprintf("%03d", $kode_unik);
        return response($kode_unik);
    }

    private function generateNumber($nominal){

        $kode = mt_rand(001, 999);
        $nominalTransfer = $nominal + $kode;

        if ($this->kodeExist($kode, $nominalTransfer)){
            return $this->generateNumber($nominal);
        }

        return $kode;
    }

    private function kodeExist($kode, $nominalTransfer){
        $query =  Payment::where([
            ['update', '>=', Carbon::today()],
            ['kode' , '=', $kode],
        ]);

        if ($nominalTransfer != $kode){
            $query = $query->where('nominal_transfer', $nominalTransfer);
        }

        $status = $query->exists();

        return $status;
    }
}
