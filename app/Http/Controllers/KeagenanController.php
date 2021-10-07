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
        // $this->middleware('auth');
        Nicepay::$isProduction = env('NICEPAY_IS_PRODUCTION', 'true');

        date_default_timezone_set("Asia/Jakarta");
    }

    public function cronKeagenan(Request $request){
        
        $getPayment = Payment::select("ra_payment_dua.id_transaksi","ra_payment_dua.id_agen","ra_payment_dua.nominal_total")
                        ->leftJoin("ra_setting_fee AS b","ON", "ra_payment_dua.id_agen = b.id_users")
                        ->selectRaw("(ra_payment_dua.nominal_total * b.angka)/100 nominal_fee")
                        ->where("ra_payment_dua.status","paid")
                        ->where("ra_payment_dua.id_agen","!=","")
                        ->whereNotNull("ra_payment_dua.id_agen")
                        ->where("ra_payment_dua.varian","Aqiqah")
                        ->where("ra_payment_dua.tipe","transaksi")
                        ->where("ra_payment_dua.lunas","y")
                        ->whereNull("ra_payment_dua.hitung_fee")
                        ->where("ra_payment_dua.nominal_total",">",0)
                        ->where("ra_payment_dua.jenis_fee",">","persentase")
                        ->orderBy("ra_payment_dua.tgl_transaksi","DESC")
                        ->get();
        
        dd($getPayment);

        // $savePencairanDetail    = new PencairanDetail;
        // $savePencairanDetail->id_order = $referenceNo;
        // $savePencairanDetail->txid     = $tXid;
        // $savePencairanDetail->request  = addslashes($detailTrans);
        // $savePencairanDetail->response = addslashes($transaksiAPI);
        // $savePencairanDetail->status   = addslashes($msgTrx);
        // $savePencairanDetail->action   = "Inquiry";
        // $savePencairanDetail->id_entitas = $transaksi['id_entitas'];
        // $savePencairanDetail->source_data = "fe";
        // $savePencairanDetail->save();
        // b. insert hasil select tadi ke ra_pencairan_detail (id_transaksi, id_agen, nominal_total, nominal_fee) 

        // c. update ra_payment_dua set hitung_fee = y where id_transaksi IN (transaksi yg poin a tadi di atas )
        //     }

}
