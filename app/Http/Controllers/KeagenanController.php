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
        echo "Start Time: ".Carbon::now();
        echo "<br>";
        $this->starttime = microtime(true);
    }

    public function cronKeagenan(Request $request){

        $getPayment = Payment::select("ra_payment_dua.id_transaksi","ra_payment_dua.id_agen","ra_payment_dua.id_kantor","ra_payment_dua.nominal_total")
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
                        ->orderBy("ra_payment_dua.tgl_transaksi","ASC")
                        ->limit(100)
                        ->get();

        echo "Jumlah Transaksi To Updated: ".$getPayment->count();
        echo "<br>";
        echo "<br>";
        foreach($getPayment as $key => $value){

            echo "Id Transaksi : ".$value->id_transaksi;
            echo "<br>";
            
            $savePencairanDetail    = new PencairanDetail;
            $savePencairanDetail->id_transaksi  = $value->id_transaksi;
            $savePencairanDetail->id_agen       = $value->id_agen;
            $savePencairanDetail->nominal_total = $value->nominal_total;
            $savePencairanDetail->nominal_fee   = $value->nominal_fee_agen;
            $savePencairanDetail->save();
            
            $savePencairanDetailKantor    = new PencairanDetail;
            $savePencairanDetailKantor->id_transaksi  = $value->id_transaksi;
            $savePencairanDetailKantor->id_agen       = $value->id_kantor;
            $savePencairanDetailKantor->nominal_total = $value->nominal_total;
            $savePencairanDetailKantor->nominal_fee   = $value->nominal_fee_kantor;
            $savePencairanDetailKantor->save();
            # b. insert hasil select tadi ke ra_pencairan_detail (id_transaksi, id_agen, nominal_total, nominal_fee) 
        
            $updatePayment    = Payment::where("id_transaksi",$value->id_transaksi)->first();
            $updatePayment->hitung_fee   = "y";
            $updatePayment->save();
            # c. update ra_payment_dua set hitung_fee = y where id_transaksi IN (transaksi yg poin a tadi di atas )
        }

        $endtime = microtime(true);
        $timediff = $endtime - $this->starttime;
        echo "End Time ".Carbon::now();
        echo "<br>";
        echo "Elapsed Time : ". $this->secondsToTime($timediff) ." In Microtime : " .ROUND(($timediff), 4);
        echo "<br>";
        die();
    }
    // pass in the number of seconds elapsed to get hours:minutes:seconds returned
    private function secondsToTime($s)
    {
        $h = floor($s / 3600);
        $s -= $h * 3600;
        $m = floor($s / 60);
        $s -= $m * 60;
        return $h.':'.sprintf('%02d', $m).':'.sprintf('%02d', $s);
    }

}
