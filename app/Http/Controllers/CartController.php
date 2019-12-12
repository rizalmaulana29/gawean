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

use App\Nicepaylog;
use App\Thirdparty\Nicepay\Nicepay;

class CartController extends Controller
{
    public function __construct()
    {
      // $this->middleware('auth');
      Nicepay::$isProduction = env('NICEPAY_IS_PRODUCTION', 'true');

      date_default_timezone_set("Asia/Jakarta");
    }
    
    public function cart(Request $request){
      $req = $request->all();
      $now = Carbon::now()->addHour(7);
 
      $this->passed = $req;

      $total = 0;

      #ASK. TOTAL NYA NGAMBIL DARIMANA?
      foreach ($req['id_produk'] as $key => $id_produk) {
          $total += $req['qty'][$key] * $req['harga'][$key];
      }

      $result[1] = Payment::create([
          'id_transaksi' => date("ymd") . '001' . mt_rand(1000,9999),
          'id_payment_method' => $request->input('id_payment'),
          'nominal' => $total,
          'coa_debit' => $request->input('coa_debit'),
          'status' => 0,
          'kode' => $request->input('kode'),
          'nominal_transfer' => $request->input('nominal_transfer')
      ]);

      $total = 0;
      $id = [];
      $x = [];
      $n = 0;
      foreach ($req['id_produk'] as $key => $id_produk) {
          $produk = Harga::find($id_produk);
          $id[$n] = $produk->produk;
          $order = new Order;
          $order->id_order = $result[1]->id_transaksi;
          $order->id_kantor = $request->input('id_kantor');
          $order->ra_produk_harga_id = $id_produk;
          $order->id_via_bayar = 1;
          $order->id_pelanggan = $order->id_order;
          $order->id_via_bayar = 1;
          $order->coa_debit = $request->input('coa_debit'); 
          $order->quantity = $req['qty'][$key];
          $order->harga = $req['harga'][$key];
          $total += $order->quantity * $order->harga;
          $order->tgl_transaksi = $now;
          $order->total_transaksi = $total;
          $order->id_payment_method = $request->input('id_payment');
          $order->lunas = 'y';
          $order->approve = 'y';
          $order->keterangan = $req['keterangan'][$key];
          $order->nik_input = $request->input('nik_input');
          $order->cur = "IDR";
          $order->save();
          $n++;
      }
      // $x = $order->quantity;
      // $count = count($x);
      
      foreach ($req['status'] as $key => $status) {
          $result[2] = new Kontak;
          $result[2]->id_kontak   = date("ymd") . 00 . $request->input('id_kantor') . mt_rand(1000,9999);
          $result[2]->nama_kontak = $req['nama'][$key];
          $result[2]->tgl_lahir   = $req['tgl_lahir'][$key];
          $result[2]->tempat_lahir = $req['tempat_lahir'][$key];
          $result[2]->alamat      = $req['alamat'][$key];
          $result[2]->kota        = $req['kota'][$key];
          $result[2]->kecamatan   = $req['kecamatan'][$key];
          $result[2]->status      = $status;
          $result[2]->id_order    = $order->id_order;
          $result[2]->tgl_reg     = $now;
          $result[2]->telepon     = $request->input('telepon');
          $result[2]->hp          = $request->input('hp');
          $result[2]->email       = $request->input('email');
          $result[2]->jk          = $req['jk'][$key];
          $result[2]->id_kantor   = $request->input('id_kantor');
          $result[2]->save();
      }
      
      #ASK. GIMANA PENENTUAN JENIS PAYMENT METHODNYA?
          $npRegister = $this->npRegistration($result[1]->id_transaksi);
          $response = json_decode($npRegister);

      #Delete test Inputed Data
      // (Nicepay::$isProduction)? : $this->deleteTestPayment($result[1]->id_transaksi);

      #ASK. GIMANA RESPONSE TERBAIKNYA?
          if($response->resultCd == '0000'){
            return response()->json(["status" => "success", "message" => $response],200);
          }else{
            return response()->json(["status" => "failed", "detail" => $response->resultCd, "message" => $response->resultMsg],200);
          }
  }
  
  private function npRegistration($id_trx){
      
      $nicepay = new Nicepay;
      $vacctValidDt   = date("Ymd");
      $vacctValidDt   = date('Ymd', strtotime($vacctValidDt . ' +1 day'));
      $vacctValidTm   = date("His");

      $payment        = Payment::where('id_transaksi',$id_trx)->first();
      $detailOrder    = Order::where('id_order',$id_trx)->first();
      $kontak         = Kontak::where('id_order',$id_trx)->where('status','Kostumer')->first();
      
      $timestamp      = date("YmdHis");
      $referenceNo    = $id_trx;
      $amt            = $payment['nominal'];
      
      $payMeth        = $this->passed['id_payment'];
      $payMethod      = sprintf("%02d", $payMeth);
      $code           = $this->passed['code'];

      $merchantToken  = $nicepay->merchantToken($timestamp,$referenceNo,$amt);

      #ASK. GIMANA MENDINAMIS KAN PARAMETERNYA?
      $customerName       = $kontak['nama_kontak'];
      $customerPhone      = $kontak['hp'];
      $customerEmail      = $kontak['email'];
      $customerAddress    = $kontak['alamat'];
      $customerCity       = $kontak['kota'];
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

      // var_dump(
        //   $bankCd             = "BMRI",
        //   $customerName       = $kontak['nama_kontak'],
        //   $customerPhone      = $kontak['hp'],
        //   $customerEmail      = $kontak['email'],
        //   $customerAddress    = $kontak['alamat'],
        //   $customerCity       = $kontak['kota'],
        //   $customerProv       = "Jawa Barat",
        //   $customerPostId     = "40331",
        //   $customerCountry    = "Indonesia",
    
        //   $deliveryNm         = "Nama Pengirim",
        //   $deliveryPhone      = "No Penerima",
        //   $deliveryAddr       = "Jalan Bukit Berbunga 22",
        //   $deliveryCity       = "Jakarta",
        //   $deliveryState      = "DKI Jakarta",
        //   $deliveryPostCd     = "12345",
        //   $deliveryCountry    = "Indonesia",
        //   $description        = "Desctiption",
      // );

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
              "billingEmail"  =>$customerEmail,
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
              "reqServerIP"   =>"127.0.0.1",
              // "userIP"        =>"127.0.0.1",
              "userSessionID" =>$this->getRandomString(32),
              "userAgent"     =>"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML,like Gecko) Chrome/60.0.3112.101 Safari/537.36",
              "userLanguage"  =>"ko-KR,en-US;q=0.8,ko;q=0.6,en;q=0.4",
              "cartData"      =>"{}",
              "vacctValidDt"  =>$vacctValidDt,
              "vacctValidTm"  =>$vacctValidTm,
              "merFixAcctId"  =>""
          );
      
      $codeArray   = ($payMethod == "01")?array():($payMethod == "02")?array("bankCd"=>$code):($payMethod == "03")?array("mitraCd"=>$code):array();
      $detailTrans = array_merge($detailTrans,$codeArray);
      $detailTrans = json_encode($detailTrans);


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

      $nicepayLog = new Nicepaylog;
      $nicepayLog->id_order = $id_trx;
      $nicepayLog->payment_method = $payMethod;
      $nicepayLog->code     = $code;
      $nicepayLog->txid     = $tXid;
      $nicepayLog->virtual_account_no = $vacctno;
      $nicepayLog->update = Carbon::now();
      $nicepayLog->request  = addslashes($detailTrans);
      $nicepayLog->response = addslashes($transaksiAPI);
      $nicepayLog->status   = addslashes($msg);
      $nicepayLog->action   = "Registration";
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

  private function deleteTestPayment($id_trx){
      Payment::where('id_transaksi',$id_trx)->delete();
      Order::where('id_order',$id_trx)->delete();
      Kontak::where('id_order',$id_trx)->delete();
  }
}
