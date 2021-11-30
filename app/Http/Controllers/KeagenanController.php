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
        echo "Start Time: Test " . Carbon::now();
        echo "<br>";
        $this->starttime = microtime(true);
    }

    public function cronKeagenan(Request $request)
    {

        // $getPayment = Payment::select("ra_payment_dua.id_transaksi","ra_payment_dua.id_agen","ra_payment_dua.id_kantor","ra_payment_dua.nominal_total","ra_payment_dua.tgl_transaksi","ra_payment_dua.tgl_kirim")
        //                 ->selectRaw("(ra_payment_dua.nominal_total * b.angka)/100 nominal_fee_agen")
        //                 ->selectRaw("(ra_payment_dua.nominal_total * c.angka)/100 nominal_fee_kantor")
        //                 ->leftJoin("ra_setting_fee AS b","ra_payment_dua.id_agen","=", "b.id_users")
        //                 ->leftJoin("ra_setting_fee AS c","ra_payment_dua.id_kantor","=", "c.id_users")
        //                 ->where("ra_payment_dua.status","paid")
        //                 ->where("ra_payment_dua.id_agen","!=","")
        //                 ->whereNotNull("ra_payment_dua.id_agen")
        //                 // ->where("ra_payment_dua.varian","Aqiqah")
        //                 ->where("ra_payment_dua.tipe","transaksi")
        //                 ->where("ra_payment_dua.lunas","y")
        //                 ->whereNull("ra_payment_dua.hitung_fee")
        //                 ->where("ra_payment_dua.nominal_total",">",0)
        //                 ->where("b.jenis_fee","=","persentase")
        //                 ->orderBy("ra_payment_dua.tgl_transaksi","ASC")
        //                 ->limit(100)
        //                 ->get();

        $getPayment = DB::select(DB::raw("
                    SELECT
                    t.id_transaksi,
                    t.id_agen,
                    t.nominal_total,
                    t.tgl, 
                    t.tgl_kirim,
                    t.varian,
                    t.nominal_fee,	t.name , t.label,
                    t.created_at from 
                    ( SELECT
                    a.id_transaksi,
                    a.id_agen,
                    a.nominal_total,
                    a.tgl, a.tgl_kirim,
                    a.varian,
                (a.nominal_total * b.angka)/100 nominal_fee,	c.name , concat('Biaya Fee Aqiqah Agen ',c.name,' ','ID ', a.id_transaksi) label,
                    NOW() created_at
                FROM
                    ra_payment_dua AS a 
                    left join ra_setting_fee AS b on a.id_agen = b.id_users
                    left join cms_users AS c ON a.id_agen = c.id
                WHERE
                    a.STATUS = 'paid' 
                    AND a.id_agen != '' 
                    AND a.id_agen != 'null' 
                    AND a.varian = 'Aqiqah' 
                    AND b.varian = 'Aqiqah' 
                    AND a.tipe = 'transaksi' 
                    AND a.lunas = 'y' 
                    AND a.hitung_fee IS NULL
                    AND a.nominal_total > 0
                    AND b.jenis_fee = 'persentase'
                
                UNION 
                    
                    SELECT
                    a.id_transaksi,
                    a.id_kantor id_agen,
                    a.nominal_total,
                    a.tgl, a.tgl_kirim,
                    a.varian,
                (a.nominal_total * b.angka)/100 nominal_fee	, c.name , concat('Biaya Fee Aqiqah SO ',c.name,' ','ID ', a.id_transaksi) label,
                    NOW() created_at
                FROM
                    ra_payment_dua AS a 
                    left join ra_setting_fee AS b on a.id_kantor = b.id_users
                    left join cms_users AS c ON a.id_kantor = c.id
                WHERE
                    a.STATUS = 'paid' 
                    AND a.id_agen != '' 
                    AND a.id_agen != 'null' 
                    AND a.varian = 'Aqiqah' 
                    AND b.varian = 'Aqiqah' 
                    AND a.tipe = 'transaksi' 
                    AND a.lunas = 'y' 
                    AND a.hitung_fee IS NULL
                    AND a.nominal_total > 0
                    AND b.jenis_fee = 'persentase'
                    
                    UNION 
                    
                    SELECT
                    a.id_transaksi,
                    a.id_kantor id_agen,
                    a.nominal_total,
                    a.tgl, a.tgl_kirim,
                    a.varian,
                (a.nominal_total * b.angka)/100 nominal_fee	, c.name,  concat('Biaya Fee Retail_Food SO ',c.name,' ','ID ', a.id_transaksi) label,
                    NOW() created_at 
                FROM
                    ra_payment_dua AS a 
                    left join ra_setting_fee AS b on a.id_kantor = b.id_users
                    left join cms_users AS c ON a.id_kantor = c.id
                WHERE
                    a.STATUS = 'paid' 
                    AND a.id_agen != '' 
                    AND a.id_agen != 'null' 
                    AND a.varian = 'Retail_Food' 
                    AND b.varian = 'Retail_Food' 
                    AND a.tipe = 'transaksi' 
                    AND a.lunas = 'y' 
                    AND a.hitung_fee IS NULL
                    AND a.nominal_total > 0
                    AND b.jenis_fee = 'persentase' ) AS t"));

        echo "Jumlah Transaksi To Updated: " . count($getPayment);
        echo "<br>";
        echo "<br>";

        foreach($getPayment as $key => $value){
            $tgl_transaksi = Carbon::createFromFormat('Y-m-d H:i:s', $value->tgl)->format("Y-m-d");
            echo "Id Transaksi : ".$value->id_transaksi;
            echo "<br>";
            echo "Tgl_transaksi : ".$tgl_transaksi;
            echo "<br>";

            // $savePencairanDetail    = new PencairanDetail;
            // $savePencairanDetail->id_transaksi  = $value->id_transaksi;
            // $savePencairanDetail->id_agen       = $value->id_agen;
            // $savePencairanDetail->nominal_total = $value->nominal_total;
            // $savePencairanDetail->nominal_fee   = $value->nominal_fee_agen;
            // $savePencairanDetail->tgl_transaksi = $tgl_transaksi;
            // $savePencairanDetail->tgl_kirim     = $value->tgl_kirim;
            // $savePencairanDetail->varian        = $value->varian;
            // $savePencairanDetail->label         = $value->label;
            // $savePencairanDetail->nama_agen     = $value->name;
            // $savePencairanDetail->created_at    = $value->created_at;
            // $savePencairanDetail->save();
            
            // $savePencairanDetailKantor    = new PencairanDetail;
            // $savePencairanDetailKantor->id_transaksi  = $value->id_transaksi;
            // $savePencairanDetailKantor->id_agen       = $value->id_kantor;
            // $savePencairanDetailKantor->nominal_total = $value->nominal_total;
            // $savePencairanDetailKantor->nominal_fee   = $value->nominal_fee_kantor;
            // $savePencairanDetailKantor->tgl_transaksi = $tgl_transaksi;
            // $savePencairanDetailKantor->tgl_kirim     = $value->tgl_kirim;
            // $savePencairanDetailKantor->save();
            # b. insert hasil select tadi ke ra_pencairan_detail (id_transaksi, id_agen, nominal_total, nominal_fee) 
        
            // $updatePayment    = Payment::where("id_transaksi",$value->id_transaksi)->first();
            // $updatePayment->hitung_fee   = "y";
            // $updatePayment->save();
            # c. update ra_payment_dua set hitung_fee = y where id_transaksi IN (transaksi yg poin a tadi di atas )
        }
    

        $endtime = microtime(true);
        $timediff = $endtime - $this->starttime;
        echo "End Time " . Carbon::now();
        echo "<br>";
        echo "Elapsed Time : " . $this->secondsToTime($timediff) . " In Microtime : " . ROUND(($timediff), 4);
        echo "<br>";
        die();
    }

    private function secondsToTime($s)
    {
        // pass in the number of seconds elapsed to get hours:minutes:seconds returned
        $h = floor($s / 3600);
        $s -= $h * 3600;
        $m = floor($s / 60);
        $s -= $m * 60;
        return $h . ':' . sprintf('%02d', $m) . ':' . sprintf('%02d', $s);
    }
}
