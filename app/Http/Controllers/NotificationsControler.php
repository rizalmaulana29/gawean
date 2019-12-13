<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Thirdparty\Nicepay\Nicepay;
use App\Nicepaylog;
use App\Payment;
use App\Response;
use Carbon\Carbon;

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
        $vacctValidDt	= $req['vacctValidDt'];
        $vacctValidTm   = $req['vacctValidTm'];

        $mtNotif    = $nicepay->getMerTokNotif($tXid,$amt);
        if($merchantToken != $mtNotif) {
            die("Antum Dilarang Masuk Euy!! Beda Data na ge.");exit();
        }

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
            $code           = $req['bankCd'];
            $vacctNo        = $req['vacctNo'];
            $vacctValidDt   = $req['vacctValidDt'];
            $vacctValidTm   = $req['vacctValidTm'];
        }
        else if($payMethod == "03"){
            $code           = $req['mitraCd'];
            $payNo          = $req['payNo'];
            $payValidDt     = $req['payValidDt'];
            $payValidTm     = $req['payValidTm'];
            $receiptCode    = $req['receiptCode'];
            $mRefNo         = $req['mRefNo'];
            $depositDt      = $req['depositDt'];
            $depositTm      = $req['depositTm'];
        }

        $nicepayLog    = new Nicepaylog;

        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->payment_method = $payMethod;
        $nicepayLog->code     = $code;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->virtual_account_no = $vacctNo;
        $nicepayLog->update   = Carbon::now();
        $nicepayLog->request  = addslashes(json_encode($req));
        $nicepayLog->response = "";
        $nicepayLog->status   = addslashes($status);
        $nicepayLog->action   = "Notification";

        // echo json_encode($req);
        $nicepayLog->save();
        echo $status;
        $status = ($status == 0)?"success":($status == 1)?"failed":($status == 2)?"void":($status == 3)?"expired":($status == 4)?"expired":($status == 5)?"readyToPaid":"What method?";

        $payment = Payment::where('id_transaksi', $referenceNo)->first();
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

        $nicepay = new Nicepay;

        $merchantToken  = $nicepay->merchantToken($timestamp,$referenceNo,$amt);

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>$tXid,
                "iMid"          =>$nicepay->getMerchantID(),
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
        $nicepayLog->save();

        return $transaksiAPI;
    }

    public function npCancelVA(Request $request){
        $req = $request->all();

        $timestamp      = date("YmdHis");
        $referenceNo    = $req['referenceNo'];
        $tXid           = $req['tXid'];
        $amt            = $req['amt'];
        
        $payMeth    = Payment::where("id_transaksi",$referenceNo)->pluck("id_payment_method");
        $payMeth    = sprintf("%02d", $payMeth[0]);


        $nicepay = new Nicepay;

        $merchantToken  = $nicepay->merchantToken($timestamp,$tXid,$amt);

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>$tXid,
                "iMid"          =>$nicepay->getMerchantID(),
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
        
        $nicepayLog->save();

        return $transaksiAPI;
    }

    private function npRegistration($id_trx){
      
        $nicepay = new Nicepay;
        $vacctValidDt   = date("Ymd");
        $vacctValidDt   = date('Ymd', strtotime($vacctValidDt . ' +1 day'));
        $vacctValidTm   = date("His");
  
        // $payment        = Payment::where('id_transaksi',$id_trx)->first();
        // $detailOrder    = Order::where('id_order',$id_trx)->first();
        // $kontak         = Kontak::where('id_order',$id_trx)->where('status','Kostumer')->first();
  
        $num      = $this->passed['id_payment'];
        $num_pad  = sprintf("%02d", $num);
        $bCode    = $this->passed['bankCd'];
  
        $timestamp      = date("YmdHis");
        $referenceNo    = $id_trx;
        $amt            = $this->passed['nominal_transaksi'];
        $payMethod      = $num_pad;
        $bankCd         = "BMRI";
  
        $merchantToken  = $nicepay->merchantToken($timestamp,$referenceNo,$amt);
  
        #ASK. GIMANA MENDINAMIS KAN PARAMETERNYA?
        $customerName       = $this->passed['nama'][0];
        $customerPhone      = $this->passed['hp'][0];
        // $customerEmail      = $this->passed['email'][0];
        $customerAddress    = $this->passed['alamat'][0];
        $customerCity       = $this->passed['kota'][0];
        // $customerProv       = "Jawa Barat";
        // $customerPostId     = "40331";
        // $customerCountry    = "Indonesia";
  
        $deliveryNm         = "Nama Pengirim";
        $deliveryPhone      = "No Penerima";
        $deliveryAddr       = "Jalan Bukit Berbunga 22";
        $deliveryCity       = "Jakarta";
        $deliveryState      = "DKI Jakarta";
        $deliveryPostCd     = "12345";
        $deliveryCountry    = "Indonesia";
        $description        = "Desctiption";
  
        #billing = detail customer
        #delivery = detail pengiriman
        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "iMid"          =>$nicepay->getMerchantID(),
                "payMethod"     =>$payMethod,
                "currency"      =>"IDR",
                "amt"           =>$amt,
                "referenceNo"   =>$referenceNo,
                "goodsNm"       =>"Rumah Aqiqah",
                "billingNm"     =>$customerName,
                "billingPhone"  =>$customerPhone,
                // "billingEmail"  =>$customerEmail,
                "billingAddr"   =>$customerAddress,
                "billingCity"   =>$customerCity,
                // "billingState"  =>$customerProv,
                // "billingPostCd" =>$customerPostId,
                // "billingCountry"=>$customerCountry,
                "deliveryNm"    =>$deliveryNm,
                "deliveryPhone" =>$deliveryPhone,
                "deliveryAddr"  =>$deliveryAddr,
                "deliveryCity"  =>$deliveryCity,
                "deliveryState" =>$deliveryState,
                "deliveryPostCd"=>$deliveryPostCd,
                "deliveryCountry"=>$deliveryCountry,
                "description"   =>$description,
                "dbProcessUrl"  =>$nicepay->getUrlNotif(),
                "merchantToken" =>$merchantToken,
                "reqDomain"     =>"rumahaqiqah.co.id",
                // "reqServerIP"   =>"127.0.0.1",
                // "userIP"        =>"127.0.0.1",
                "userSessionID" =>"697D6922C961070967D3BA1BA5699C2C",
                "userAgent"     =>"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML,like Gecko) Chrome/60.0.3112.101 Safari/537.36",
                "userLanguage"  =>"ko-KR,en-US;q=0.8,ko;q=0.6,en;q=0.4",
                "cartData"      =>"{}",
                "bankCd"        =>$bankCd,
                "vacctValidDt"  =>$vacctValidDt,
                "vacctValidTm"  =>$vacctValidTm,
                "merFixAcctId"  =>""
            );
        $detailTrans =json_encode($detailTrans);
  
  
        $transaksiAPI = $nicepay->nicepayApi("nicepay/direct/v2/registration",$detailTrans); 
        
        $response     = json_decode($transaksiAPI);
        if($response->resultCd == '0000'){
          $tXid     = $response->tXid;
          $vacctno  = $response->vacctNo;
          $msg      = $response->resultMsg;
        }else{
          $tXid     = "";
          $vacctno  = "";
          $msg      = $response->resultMsg;
        }
  
        $nicepayLog    = new Nicepaylog;
        $nicepayLog->id_order = $id_trx;
        $nicepayLog->payment_method = $payMethod;
        $nicepayLog->code     = $bCode;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->update   = NOW();
        $nicepayLog->virtual_account_no = $vacctno;
        $nicepayLog->request  = addslashes($detailTrans);
        $nicepayLog->response = addslashes($transaksiAPI);
        $nicepayLog->status   = addslashes($msg);
        $nicepayLog->action   = "Registration";

  
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
