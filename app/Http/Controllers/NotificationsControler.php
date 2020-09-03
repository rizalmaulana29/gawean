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

        $merchantTokenComparator = $nicepay->getMerTokNotif($iMid,$referenceNo,$amt,$merKey);

        echo "id_payment_method : ".$payment['id_payment_method']."<br>";
        echo "id_entitas : ".$paymeth['id_entitas']."<br>";
        echo "IMID : ".$iMid."<br>";
        echo "referenceNo : ".$referenceNo."<br>";
        echo "amt : ".$amt."<br>";
        echo "merKey : ".$merKey."<br>";
        
        echo "<br>merchantToken : <br>".$merchantToken."<br>";
        echo "merchantTokenComparator : <br>".$merchantTokenComparator."<br>";
        
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
