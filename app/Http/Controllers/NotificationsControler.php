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
    
    public function dbProcess(Request $request){
        $req = $request->all();
        $nicepay = new Nicepay;

        $amt            = $req['amt'];
        $billingNm      = $req['billingNm'];
        $currency	    = $req['currency'];
        $goodsNm	    = $req['goodsNm'];
        $matchCl	    = $req['matchCl'];
        $merchantToken	= $req['merchantToken'];
        $payMethod	    = $req['payMethod'];
        $referenceNo	= $req['referenceNo'];
        $status	        = $req['status'];
        $tXid	        = $req['tXid'];
        $transDt	    = $req['transDt'];
        $transTm	    = $req['transTm'];

        $payment    = Payment::where('id_transaksi',$referenceNo)->first();
        $paymeth    = Paymeth::find($payment['id_payment_method']);
        $merData    = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];

        // $mtNotif    = $nicepay->getMerTokNotif($iMid,$tXid,$amt,$merKey);
        // if($merchantToken != $mtNotif) {
        //     die("Antum Dilarang Masuk Euy!! Beda Data na ge.");exit();
        // }

        if($payMethod == "01"){
            $authNo         = $req['authNo'];
            $IssueBankCd    = $req['IssueBankCd'];
            $IssueBankNm    = $req['IssueBankNm'];
            $acquBankCd     = $req['acquBankCd'];
            $acquBankNm     = $req['acquBankNm'];
            $cardNo         = $req['cardNo'];
            $cardExpYymm    = $req['cardExpYymm'];
            $instmntMon     = $req['instmntMon'];
            $instmntType    = $req['instmntType'];
            $preauthToken   = $req['preauthToken'];
            $recurringToken = $req['recurringToken'];
            $ccTransType    = $req['ccTransType'];
            $vat            = $req['vat'];
            $fee	        = $req['fee'];
            $notaxAmt       = $req['notaxAmt'];
        }
        else if($payMethod == "02"){
            $code       = $req['bankCd'];
            $code_bayar = $req['vacctNo'];
            $ValidDt    = $req['vacctValidDt'];
            $ValidTm    = $req['vacctValidTm'];
        }
        else if($payMethod == "03"){
            $code           = $req['mitraCd'];
            $code_bayar     = $req['payNo'];
            $ValidDt        = $req['payValidDt'];
            $ValidTm        = $req['payValidTm'];
            // $receiptCode    = $req['receiptCode'];
            // $mRefNo         = $req['mRefNo'];
            // $depositDt      = $req['depositDt'];
            // $depositTm      = $req['depositTm'];
        }

        $nicepayLog    = new Nicepaylog;

        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->payment_method = $payMethod;
        $nicepayLog->code     = $code;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->virtual_account_no = $code_bayar;
        $nicepayLog->update   = Carbon::now();
        $nicepayLog->request  = addslashes(json_encode($req));
        $nicepayLog->response = "";
        $nicepayLog->status   = addslashes($status);
        $nicepayLog->action   = "Notification";
        $nicepayLog->id_entitas = $paymeth['id_entitas'];
        // echo json_encode($req);
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
                $number = $req['vacctNo'];;
              } elseif ($parent_id == 3) {
                $title  = "Kode Pembayaran :";
                $number = $req['payNo'];
              } else{
                $title = "No.Rekening :";
                $number = DB::table('ra_bank_rek')->where('id_payment_method',$request->input('id_payment'))->where('id_kantor',$request->input('id_kantor'))->value('id_rekening');
              }

            $hasil = Mail::send(
                    (new Notification($to_address, $payment, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$title,$number))->build()
                );
        }

        if($payment){
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
        $iMid       = Nicepay::$isProduction ? $merData['mid']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merkey']:$merData['merkey_sand'];

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
        $iMid       = Nicepay::$isProduction ? $merData['mid']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merkey']:$merData['merkey_sand'];
        
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

}
