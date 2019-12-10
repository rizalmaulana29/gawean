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

    public function Test(){
        $this->npRegistration("1912100015235");
    }

    private function npRegistration($id_trx){

        $nicepay = new Nicepay;
        $vacctValidDt   = date("Ymd");
        $vacctValidDt   = date('Ymd', strtotime($vacctValidDt . ' +1 day'));
        $vacctValidTm   = date("His");

        $trx            = Payment::where('id_transaksi',$id_trx)->first();
        $detailOrder    = Order::where('id_order',$id_trx)->first();
        $kontak         = Kontak::where('id_order',$id_trx)->where('status','Kostumer')->first();

        $timestamp      = date("YmdHis");
        $referenceNo    = $id_trx;
        $amt            = "120000";

        $merchantToken  = $nicepay->merchantToken($timestamp,$referenceNo,$amt);

        $payMethod      = "02";

        #ASK. GIMANA MENDINAMIS KAN PARAMETERNYA?
        $bankCd             = "BMRI";
        $customerName       = $kontak->nama_kontak;
        $customerPhone      = $kontak->hp;
        $customerEmail      = $kontak->email;
        $customerAddress    = $kontak->alamat;
        $customerCity       = $kontak->kota;
        $customerProv       = "Jawa Barat";
        $customerPostId     = "40331";
        $customerCountry    = "Indonesia";

        $deliveryNm         = "Nama Pengirim";
        $deliveryPhone      = "No Penerima";
        $deliveryAddr       = "Jalan Bukit Berbunga 22";
        $deliveryCity       = "Jakarta";
        $deliveryState      = "DKI Jakarta";
        $deliveryPostCd     = "12345";
        $deliveryCountry    = "Indonesia";
        $description        = "Desctiption";
        
        #Test
        if($test){
            $bankCd             = "BMRI";
            $customerName       = "Iqbal Sandi Isharmawan" ;
            $customerPhone      = "089601722915";
            $customerEmail      = "rivmochi7@gmail.com";
            $customerAddress    = "Jalan Parakan Saat I No 40";
            $customerCity       = "Bandung";
            $customerProv       = "Jawa Barat";
            $customerPostId     = "40381";
            $customerCountry    = "Indonesia";
        }

        #billing = detail customer
        #delivery = detail pengiriman
        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "iMid"          =>$nicepay->getMerchantID(),
                "payMethod"     =>$payMethod,
                "currency"      =>"IDR",
                "amt"           =>$amt,
                "referenceNo"   =>$referenceNo,
                "goodsNm"       =>"Kambing Aqiqah",
                "billingNm"     =>$customerName,
                "billingPhone"  =>$customerPhone,
                "billingEmail"  =>$customerEmail,
                "billingAddr"   =>$customerAddress,
                "billingCity"   =>$customerCity,
                "billingState"  =>$customerProv,
                "billingPostCd" =>$customerPostId,
                "billingCountry"=>$customerCountry,
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
                "reqServerIP"   =>"127.0.0.1",
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
        
        return $transaksiAPI;
    }

    function getName($n) { 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = ''; 
  
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    }

}
