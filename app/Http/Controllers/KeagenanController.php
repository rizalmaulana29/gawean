<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

// use App\Nicepaylog;
// use App\Order;
use App\Payment;
use App\PencairanDetail;
// use App\Paymeth;
// use App\AdminEntitas;
// use App\Kontak;
// use App\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// use Illuminate\Support\Facades\Mail;
// use App\Mail\Invoice;
// use App\Mail\Notification;

class KeagenanController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set("Asia/Jakarta");
    }

    public function cronKeagenan(Request $request){
        $getPayment = Payment::select("ra_payment_dua.id_transaksi","ra_payment_dua.id_agen","ra_payment_dua.nominal_total")
                        ->selectRaw("(ra_payment_dua.nominal_total * b.angka)/100 nominal_fee_agen")
                        ->selectRaw("(ra_payment_dua.nominal_total * c.angka)/100 nominal_fee_kantor")
                        ->leftJoin("ra_setting_fee AS b","ra_payment_dua.id_agen","=", "b.id_users")
                        ->leftJoin("ra_setting_fee AS c","ra_payment_dua.id_kantor","=", "c.id_users")
                        ->where("ra_payment_dua.status","paid")
                        ->where("ra_payment_dua.id_agen","!=","")
                        ->whereNotNull("ra_payment_dua.id_agen")
                        ->where("ra_payment_dua.varian","Aqiqah")
                        ->where("ra_payment_dua.tipe","transaksi")
                        ->where("ra_payment_dua.lunas","y")
                        ->whereNull("ra_payment_dua.hitung_fee")
                        ->where("ra_payment_dua.nominal_total",">",0)
                        ->where("b.jenis_fee","=","persentase")
                        ->orderBy("ra_payment_dua.tgl_transaksi","DESC")
                        ->get();
        $i = 0;
        foreach($getPayment as $key => $value){
            $hello[$key]["id_transaksi"]    = $value->id_transaksi;
            $hello[$key]["id_agen"]         = $value->id_agen;
            $hello[$key]["nominal_total"]   = $value->nominal_total;
            $hello[$key]["nominal_fee_agen"]     = $value->nominal_fee_agen;
            $hello[$key]["nominal_fee_kantor"]     = $value->nominal_fee_kantor;
            $i++;
            
            // $savePencairanDetail    = new PencairanDetail;
            // $savePencairanDetail->id_transaksi  = $value->id_transaksi;
            // $savePencairanDetail->id_agen       = $value->id_agen;
            // $savePencairanDetail->nominal_total = $value->nominal_total;
            // $savePencairanDetail->nominal_fee   = $value->nominal_fee_agen;
            // $savePencairanDetail->save();
            // b. insert hasil select tadi ke ra_pencairan_detail (id_transaksi, id_agen, nominal_total, nominal_fee) 
        
            // $updatePayment    = Payment::where("id_transaksi",$value->id_transaksi);
            // $updatePayment->hitung_fee   = "y";
            // $updatePayment->save();
            // c. update ra_payment_dua set hitung_fee = y where id_transaksi IN (transaksi yg poin a tadi di atas )
        }
        var_dump($getPayment->count());
        dd($hello);
    }

}
