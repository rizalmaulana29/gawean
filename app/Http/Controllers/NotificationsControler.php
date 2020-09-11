<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Thirdparty\Nicepay\Nicepay;
use App\Nicepaylog;
use App\Order;
use App\Payment;
use App\Paymeth;
use App\AdminEntitas;
use App\Kontak;
use App\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;
use App\Mail\Invoice;
use App\Mail\Notification;

class NotificationsController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
        Nicepay::$isProduction = env('NICEPAY_IS_PRODUCTION', 'true');

        date_default_timezone_set("Asia/Jakarta");
    }

    public function cronNPCheker(Request $request){
        if($request->header('Authorization') != "hUI8fd0u2j3X9z2qja7d98"){
            return response()->json([
              'status'=>false,
              "message" => "Missmatch Token"
            ],422);
        }
        
        #START
        echo date("Y-m-d H:i:s.U")."<br>START PAYMENT CHECKER<br><br>";
        $dateNow = Carbon::now()->toDateTimeString();
        $dateOld = "2020-08-25 00:00:00";
      
        #Nicepay
        $paymeth    = Paymeth::where('parent_id','<=','5')->pluck('id')->toArray();
        #Not Nicepay
        // $paymeth    = Paymeth::where('parent_id','>=','5')->pluck('id')->toArray();
        
        #Get DATA Transaksi yg belum kirim Email
        $listTransaksi = Payment::select('ra_payment_dua.id_transaksi','ra_payment_dua.tgl_transaksi','ra_payment_dua.email','ra_payment_dua.id','ra_payment_dua.id_parent','ra_payment_dua.nominal_bayar','np.txid','np.id_entitas')
            ->join('ra_nicepaylog as np', 'np.id_order', '=', 'ra_payment_dua.id_transaksi')
            ->where('np.action','Registration')
            ->where('ra_payment_dua.status','=','checkout')
            ->where('ra_payment_dua.tgl_transaksi','<', $dateNow)
            ->where(function($q) {
                $q->where('np.txid', '!=', '');
            })
            ->whereIn('ra_payment_dua.id_payment_method',$paymeth)
            ->orderBy('ra_payment_dua.tgl_transaksi','ASC')
            ->limit(150)
            ->get();

        // dd($listTransaksi);

        $tot = count($listTransaksi);
        echo "Total Trans : ".$tot."<br><br>";
        if($tot > 0){
            foreach($listTransaksi as $keyTransaksi => $dataTransaksi){
                if(!$dataTransaksi->txid){continue;}
                echo "ID  : ".$dataTransaksi->id."";
                echo "<br>";
                echo "ID Transaksi : ".$dataTransaksi->id_transaksi;
                echo "<br>";
                echo "TXid : ".$dataTransaksi->txid;
                echo "<br>";
                echo "Amount : ".$dataTransaksi->nominal_bayar;
                echo "<br>";

                $result = $this->CheckInquiry($dataTransaksi);
                echo $result;
                echo "<br><br>";
            }
        }
        echo "<br>ENDs";

    }

    private function CheckInquiry($transaksi){
        $timestamp      = date("YmdHis");
        $referenceNo    = $transaksi['id_transaksi'];
        $tXid           = $transaksi['txid'];
        $amt            = $transaksi['nominal_bayar'];

        
        $payment    = Payment::where('id_transaksi',$referenceNo)->first();
        
        $merData    = AdminEntitas::where('id_entitas',$transaksi['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];

        $nicepay = new Nicepay;

        $merchantToken  = $nicepay->merchantToken($timestamp,$iMid,$referenceNo,$amt,$merKey);

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>$tXid,
                "iMid"          =>$iMid,
                "referenceNo"   =>$referenceNo,
                "amt"           =>$amt,
                "merchantToken" =>$merchantToken
            );
        $detailTrans =json_encode($detailTrans);
        
        $transaksiAPI   = $nicepay->nicepayApi("nicepay/direct/v2/inquiry",$detailTrans); 
        $response       = json_decode($transaksiAPI);
        $msgTrx         = $response->resultMsg;
        $msg    = "";

        $status	        = (isset($response->status))?$response->status:"";
        $status = ($status == 0)?"paid":(
            ($status == 1)?"failed":(
                ($status == 2)?"void":(
                    ($status == 3)?"unpaid":(
                        ($status == 4)?"expired":(
                            ($status == 5)?"readyToPaid":(
                                ($status == 9)?"Initialization / Reversal":"What method?"
                            )
                        )
                    )
                )
            )
        );

        if($status == "paid" || $status == "failed" || $status == "expired"){
            $nicepayLog    = new Nicepaylog;
            $nicepayLog->id_order = $referenceNo;
            $nicepayLog->txid     = $tXid;
            $nicepayLog->request  = addslashes($detailTrans);
            $nicepayLog->response = addslashes($transaksiAPI);
            $nicepayLog->status   = addslashes($msgTrx);
            $nicepayLog->action   = "Inquiry";
            $nicepayLog->id_entitas = $transaksi['id_entitas'];
            $nicepayLog->source_data = "fe";
            $nicepayLog->save();

            if($payment->id_parent){
                $paymentParent  = Payment::where('id',$payment->id_parent)->first();
                $lunasState     = "y";
                $paymentParent->lunas = $lunasState;
                $paymentParent->nominal_bayar = $paymentParent->nominal_total;
                $paymentParent->save();   
                
                $payment->status = $status;
                $payment->save();
                $msg .= "Status Pembayaran : ".$status."<br><b>ID PARENT : ".$payment->id_parent."</b> <br>";
                return $msg;
            }
            else{
                $payment->status = $status;
                $payment->save();
                $msg .= "Status Pembayaran : ".$status."<br><b>NULL PARENT</b> <br>";
                return $msg;
            }
        }else{
            return "Status : Belum dibayar";
        }
        
    }

    public function dbProcess(Request $request){
        $this->validate($request, [
            'payMethod' => 'required',
            'referenceNo' => 'required',
            'status' => 'required',
            'payMethod' => 'required',
            'merchantToken' => 'required',
            'tXid' => 'required'
        ]);
        
        
        $req = $request->all();
        $nicepay = new Nicepay;
        
        $code = "";
        $code_bayar = "";

        $amt            = (isset($req['amt']))?$req['amt']:"";
        $billingNm      = (isset($req['billingNm']))?$req['billingNm']:"";
        $currency	    = (isset($req['currency']))?$req['currency']:"";
        $goodsNm	    = (isset($req['goodsNm']))?$req['goodsNm']:"";
        $matchCl	    = (isset($req['matchCl']))?$req['matchCl']:"";
        $merchantToken	= (isset($req['merchantToken']))?$req['merchantToken']:"";
        $payMethod	    = (isset($req['payMethod']))?$req['payMethod']:"";
        $referenceNo	= (isset($req['referenceNo']))?$req['referenceNo']:"";
        $status	        = (isset($req['status']))?$req['status']:"";
        $tXid	        = (isset($req['tXid']))?$req['tXid']:"";
        $transDt	    = (isset($req['transDt']))?$req['transDt']:"";
        $transTm	    = (isset($req['transTm']))?$req['transTm']:"";


        $payment    = Payment::where('id_transaksi',$referenceNo)->first();
        $paymeth    = Paymeth::find($payment['id_payment_method']);

        $merData    = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];

        $merchantTokenComparator = $nicepay->getMerTokNotif($iMid,$tXid,$amt,$merKey);
        
        if($merchantTokenComparator != $merchantToken){
            return response()->json([
                'status'=>false,
                "message" => "Missmatch Merchant Token!!!",
            ],422);
        }

        if($payMethod == "01"){
            $authNo         = (isset($req['authNo']))?$req['authNo']:"";
            $IssueBankCd    = (isset($req['IssueBankCd']))?$req['IssueBankCd']:"";
            $IssueBankNm    = (isset($req['IssueBankNm']))?$req['IssueBankNm']:"";
            $acquBankCd     = (isset($req['acquBankCd']))?$req['acquBankCd']:"";
            $acquBankNm     = (isset($req['acquBankNm']))?$req['acquBankNm']:"";
            $cardNo         = (isset($req['cardNo']))?$req['cardNo']:"";
            $cardExpYymm    = (isset($req['cardExpYymm']))?$req['cardExpYymm']:"";
            $instmntMon     = (isset($req['instmntMon']))?$req['instmntMon']:"";
            $instmntType    = (isset($req['instmntType']))?$req['instmntType']:"";
            $preauthToken   = (isset($req['preauthToken']))?$req['preauthToken']:"";
            $recurringToken = (isset($req['recurringToken']))?$req['recurringToken']:"";
            $ccTransType    = (isset($req['ccTransType']))?$req['ccTransType']:"";
            $vat            = (isset($req['vat']))?$req['vat']:"";
            $fee	        = (isset($req['fee']))?$req['fee']:"";
            $notaxAmt       = (isset($req['notaxAmt']))?$req['notaxAmt']:"";
        }
        else if($payMethod == "02"){
            $code       = (isset($req['bankCd']))?$req['bankCd']:"";
            $code_bayar = (isset($req['vacctNo']))?$req['vacctNo']:"";
            $ValidDt    = (isset($req['vacctValidDt']))?$req['vacctValidDt']:"";
            $ValidTm    = (isset($req['vacctValidTm']))?$req['vacctValidTm']:"";
        }
        else if($payMethod == "03"){
            $code           = (isset($req['mitraCd']))?$req['mitraCd']:"";
            $code_bayar     = (isset($req['payNo']))?$req['payNo']:"";
            $ValidDt        = (isset($req['payValidDt']))?$req['payValidDt']:"";
            $ValidTm        = (isset($req['payValidTm']))?$req['payValidTm']:"";
            // $receiptCode    = (isset($req['receiptCode']))?$req['amt']:"";
            // $mRefNo         = (isset($req['mRefNo']))?$req['amt']:"";
            // $depositDt      = (isset($req['depositDt']))?$req['amt']:"";
            // $depositTm      = (isset($req['depositTm']))?$req['amt']:"";
        }
        $source_data = Nicepaylog::where('id_order',$referenceNo)->where('action','Registration')->value('source_data');
        $source_data = ($source_data)?$source_data:"be";
        $nicepayLog    = new Nicepaylog;

        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->payment_method = $payMethod;
        $nicepayLog->code     = $code;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->virtual_account_no = $code_bayar;
        $nicepayLog->update   = Carbon::now();
        $nicepayLog->request  = "";
        $nicepayLog->response = addslashes(json_encode($req));
        $nicepayLog->status   = addslashes($status);
        $nicepayLog->action   = "Notification";
        $nicepayLog->id_entitas = $paymeth['id_entitas'];
        $nicepayLog->source_data = $source_data;
        $nicepayLog->save();

        $status = ($status == 0)?"paid":(
                        ($status == 1)?"failed":(
                            ($status == 2)?"void":(
                                ($status == 3)?"unpaid":(
                                    ($status == 4)?"expired":(
                                        ($status == 5)?"readyToPaid":(
                                            ($status == 9)?"Initialization / Reversal":"What method?"
                                        )
                                    )
                                )
                            )
                        )
                    );

        // $payment = Payment::where('id_transaksi', $referenceNo)->first();
        if($status == "paid"){
            $orderdata  = Order::where('id_order',$referenceNo)->get();
            // $kontak     = Kontak::where('id', $payment['id_kontak'])->first();
            
            $to_address = trim($payment['email']);
            $nama       = $payment['nama_customer'];
            $alamat     = $payment['alamat'];
            $email      = trim($payment['email']); 
            $hp         = $payment['hp'];
            $parent_id  = $paymeth['parent_id'];
            
            if ($parent_id == 2 ) {
                $title  = "Virtual Account :";
                $number = (isset($req['vacctNo']))?$req['vacctNo']:"";
            } elseif ($parent_id == 3) {
                $title  = "Kode Pembayaran :";
                $number = (isset($req['payNo']))?$req['payNo']:"";
            } else{
                $title = "No.Rekening :";
                $number = DB::table('ra_bank_rek')->where('id_payment_method',$request->input('id_payment'))->where('id_kantor',$request->input('id_kantor'))->value('id_rekening');
            }

            $hasil = Mail::send(
                (new Notification($to_address, $payment, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$title,$number))->build()
            );

            $sendWa = $this->sendWa($payment, $nama, $alamat, $email, $hp,$number,$title);
        }

        if($payment){
            if($payment->id_parent && $status == "paid"){
                $paymentParent    = Payment::where('id',$payment->id_parent)->first();
                // echo "Parent ID : ".$payment->id_parent;
                // echo "<br>";

                // echo "Sisa Bayar Awal : ".$payment->sisa_pembayaran;
                // echo "<br>";
                // echo "Pembayaran : ".$paymentParent->sisa_pembayaran;
                // echo "<br>";
                // $sisaParent = $payment->sisa_pembayaran - $paymentParent->sisa_pembayaran;
                // echo "Sisa Bayar Akhir: ".$sisaParent;
                // echo "<br>";

                $lunasState = "y";#($sisaParent == 0)?'y':'n';

                $paymentParent->lunas = $lunasState;
                // $paymentParent->sisa_pembayaran = $sisaParent;
                $paymentParent->save();    
            }
            $payment->status = $status;
            $payment->save();
            $msg = array("status"=>"true","msg"=>"Berhasil Update Data Transaksi");
        }else{
            $msg = array("status"=>"false","msg"=>"No Transaction Available");
        }
        echo json_encode($msg);
    }

    public function TestRegistration(Request $request){
        $req = $request->all();
        $this->passed = $req;
        $id_unique = $this->getRandomString(10);
        $this->npRegistration($id_unique);
    }

    public function npInuqiry(Request $request){
        $req = $request->all();

        $timestamp      = date("YmdHis");
        $referenceNo    = $req['referenceNo'];
        $tXid           = $req['tXid'];
        $amt            = $req['amt'];

        $payment    = Payment::where('id_transaksi',$referenceNo)->first();
        $paymeth    = Paymeth::find($payment['id_payment_method']);
        $merData    = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];

        $nicepay = new Nicepay;

        $merchantToken  = $nicepay->merchantToken($timestamp,$iMid,$referenceNo,$amt,$merKey);

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>$tXid,
                "iMid"          =>$iMid,
                "referenceNo"   =>$referenceNo,
                "amt"           =>$amt,
                "merchantToken" =>$merchantToken
            );
        $detailTrans =json_encode($detailTrans);
        
        $transaksiAPI   = $nicepay->nicepayApi("nicepay/direct/v2/inquiry",$detailTrans); 
        $response       = json_decode($transaksiAPI);
        $msg            = $response->resultMsg;
        
        $nicepayLog    = new Nicepaylog;
        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->request  = addslashes($detailTrans);
        $nicepayLog->response = addslashes($transaksiAPI);
        $nicepayLog->status   = addslashes($msg);
        $nicepayLog->action   = "Inquiry";
        $nicepayLog->id_entitas = $paymeth['id_entitas'];
        $nicepayLog->save();

        return $transaksiAPI;
    }

    public function npCancelVA(Request $request){
        $req = $request->all();

        $timestamp      = date("YmdHis");
        $referenceNo    = $req['referenceNo'];
        $tXid           = $req['tXid'];
        $amt            = $req['amt'];
        
        $payment    = Payment::where("id_transaksi",$referenceNo)->first();
        $paymeth    = Paymeth::find($payment['id_payment_method']);
        $merData    = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];
        
        $payMeth    = sprintf("%02d", $payment["id_payment_method"]);


        $nicepay = new Nicepay;

        $merchantToken  = $nicepay->merchantToken($timestamp,$iMid,$tXid,$amt,$merKey);

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>$tXid,
                "iMid"          =>$iMid,
                "amt"           =>$amt,
                "merchantToken" =>$merchantToken,
                "payMethod"     =>$payMeth,
                "preauthToken"  =>"",
                "worker"        =>"",
                "cancelType"    =>1,
                "cancelMsg"     =>"Need To Be Canceled",
                "cancelServerIp"=>"",
                "cancelUserId"  =>"",
                "cancelUserInfo"=>"",
                "cancelRetryCnt"=>""
            );
        // echo
        $detailTrans =json_encode($detailTrans);
        
        $transaksiAPI   = $nicepay->nicepayApi("nicepay/direct/v2/cancel",$detailTrans); 
        $response       = json_decode($transaksiAPI);
        $msg            = $response->resultMsg;
        
        $nicepayLog    = new Nicepaylog;
        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->update   = Carbon::now();
        $nicepayLog->request  = addslashes($detailTrans);
        $nicepayLog->response = addslashes($transaksiAPI);
        $nicepayLog->status   = addslashes($msg);
        $nicepayLog->action   = "CancelVA";
        $nicepayLog->id_entitas = $paymeth['id_entitas'];
        
        $nicepayLog->save();

        return $transaksiAPI;
    }

    function getRandomString($n) { 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = ''; 
  
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    }


    public function sendWa($payment, $nama, $alamat, $email, $hp,$number,$title){
    if (substr($hp,0,1) == 0) {
      $nohp = str_replace('0','+62',$hp);
    }

    else {
        $nohp = $hp;
    }

    $bankRek = DB::table('ra_bank_rek')->select('keterangan','id_rekening','gambar','id_payment_method','parent_id')
                 ->where('id', $payment->id_payment_method)
                 ->first();

    if ($bankRek->keterangan == "cash") {
      $rek = $bankRek->keterangan;
    } else {
      $rek = $bankRek->keterangan.'\\n'.$bankRek->id_rekening;
    }

    $key='d99e363936ff07dec5c545c3cf7b780126ab3d3c5e86b071';
    $url='http://116.203.92.59/api/async_send_message';
    $data = array(
                  "phone_no"=> $nohp,
                  "key"   =>$key,
                  "message" =>
                                "Assalamu'alaikum".' '. $nama.', 🌟😍'
                                .'\\n'.'Terima kasih atas pembayaran anda 😍😍😍'
                                .'\\n'.'-----------------------------------------'
                                .'\\n'
                                .'\\n'.'Dengan detail pembayaran order sebagai berikut:'
                                .'\\n'.'Order ID : '.$payment->id_transaksi.'
                                \\n'.'Nama : '.$nama.'
                                \\n'.'No. Hp : '.$hp.'
                                \\n'.'Total Pembayaran : IDR '.number_format($payment['nominal_total']).'
                                \\n'.'
                                \\n'.'Pembayaran dilakukan melalui:'
                                .'\\n'.
                                '\\n'.'- '.$rek.'
                                \\n'.'- Kode Pembayaran : '.$number.'
                                \\n'.'
                                \\n'.'Untuk check pesanan anda silahkan klik link berikut :'.'
                                \\n'.'https://order.rumahaqiqah.co.id/tracking-order.php?id='.$payment->id_transaksi.'
                                \\n'.'
                                \\n'.'Butuh bantuan? Silahkan klik wa.me/6281370071330
                                '.'\\n'.'Ingat Order ID Anda saat menghubungi Customer Care.
                                '.'\\n'.
                                '\\n'.'Terima kasih telah memilih rumahaqiqah.co.id
                                '.'\\n'.
                                '\\n'.'Salam,
                                '.'\\n'.'rumahaqiqah.co.id'
                );
    $data_string = json_encode($data);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($data_string))
    );
    $res=curl_exec($ch);
    curl_close($ch);
  }

}
