<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Order;
use App\Payment;
use App\Kontak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Thirdparty\Nicepay\Nicepay;
use App\Thirdparty\Nicepay\Nicepaylog;

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
    }

    public function TestRegistration(Request $request){
        $req = $request->all();
        $this->passed = $req;
        $id_unique = $this->getRandomString(10);
        $this->npRegistration($id_unique);
    }

    public function TestInquiry(){
        $id_unique = $this->getRandomString(10);
        $this->npInuqiry($id_unique);
    }

    private function npInuqiry($id_trx){
  
        $nicepay = new Nicepay;
       
        $NicepayLog     = Nicepaylog::where('id_order',$id_trx)->first();
        $tXid           = $NicepayLog->tXid;

        $timestamp      = date("YmdHis");
        $referenceNo    = $id_trx;
        $amt            = "120000";

        $merchantToken  = $nicepay->merchantToken($timestamp,$referenceNo,$amt);
        $payMethod      = "02";

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>"IONPAYTEST02201912101705334004",
                "iMid"          =>$nicepay->getMerchantID(),
                "referenceNo"   =>$referenceNo,
                "amt"           =>$amt,
                "merchantToken" =>$merchantToken
            );
        $detailTrans =json_encode($detailTrans);

        $transaksiAPI = $nicepay->nicepayApi("nicepay/direct/v2/inquiry",$detailTrans); 
        
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
        $nicepayLog->no_reference = $referenceNo;
        $nicepayLog->virtual_account_no = $vacctno;
        // $nicepayLog->update = Carbon::now();
        $nicepayLog->request  = addslashes($detailTrans);
        $nicepayLog->response = addslashes($transaksiAPI);
        $nicepayLog->status   = addslashes($msg);
        $nicepayLog->action   = "Registration";
        var_dump($nicepayLog);
  
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
