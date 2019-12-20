<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Order;
use App\Payment;
use App\Paymeth;
use App\Kontak;
use App\Mail\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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

      #Create C'Babeh
        $tempat_lahir = (isset($req['tempat_lahir'][0]))?$req['tempat_lahir'][0]:"";
        $tgl_lahir    = (isset($req['tgl_lahir'][0]))?$req['tgl_lahir'][0]:"";
        $jk    = (isset($req['jk'][0]))?$req['jk'][0]:"";

        $kontakCus = new Kontak;
        $kontakCus->id_kontak   = date("ymd") . 00 . $request->input('id_kantor') . mt_rand(0000,9999);
        $kontakCus->nama_kontak = $req['nama'][0];
        $kontakCus->tgl_lahir   = $tgl_lahir;
        $kontakCus->tempat_lahir = $tempat_lahir;
        $kontakCus->alamat      = $req['alamat'];
        $kontakCus->kota        = $req['kota'];
        $kontakCus->kecamatan   = $req['kecamatan'];
        $kontakCus->status      = $req['status'][0];
        $kontakCus->tgl_reg     = $now;
        $kontakCus->telepon     = $request->input('telepon');
        $kontakCus->hp          = $request->input('hp');
        $kontakCus->email       = $request->input('email');
        $kontakCus->jk          = $jk;
        $kontakCus->id_kantor   = $request->input('id_kantor');
        $kontakCus->save();

      foreach ($req['status'] as $key => $status) {
        if($key != 0){
          
          $tempat_lahir = (isset($req['tempat_lahir'][$key]))?$req['tempat_lahir'][$key]:"";
          $tgl_lahir    = (isset($req['tgl_lahir'][$key]))?$req['tgl_lahir'][$key]:"";
          $jk    = (isset($req['jk'][$key]))?$req['jk'][$key]:"";


          $result[1] = new Kontak;
          $result[1]->id_kontak   = date("ymd") . 00 . $request->input('id_kantor') . mt_rand(1000,9999);
          $result[1]->nama_kontak = $req['nama'][$key];
          $result[1]->tgl_lahir   = $tgl_lahir;
          $result[1]->tempat_lahir = $tempat_lahir;
          $result[1]->alamat      = $req['alamat'];
          $result[1]->kota        = $req['kota'];
          $result[1]->kecamatan   = $req['kecamatan'];
          $result[1]->status      = $status;
          $result[1]->tgl_reg     = $now;
          $result[1]->telepon     = $request->input('telepon');
          $result[1]->hp          = $request->input('hp');
          $result[1]->email       = $request->input('email');
          $result[1]->jk          = $jk;
          $result[1]->id_kantor   = $request->input('id_kantor');
          $result[1]->save();

        }
      }
// var_dump($request->input('id_payment'));
      $result[2] = Payment::create([
          'id_transaksi' => date("ymd") . '001' . mt_rand(1000,9999),
          'id_kantor' => $request->input('id_kantor'),
          'id_kontak' => $kontakCus->id_kontak,
          'id_payment_method' => $request->input('id_payment'),
          'nominal' => $request->input('nominal'),
          'nominal_total' => $request->input('total'),
          'nominal_bayar' => $request->input('total'),
          'coa_debit' => $request->input('coa'),
          'tgl_transaksi' => $now,
          'status' => 'Tunai',
          'jenis' => 'Online',
          'kode' => $request->input('promo'),
          'id_agen' => $request->input('agen'),
          'tgl_kirim' => $request->input('tgl_kirim'),
          'waktu_kirim' => $request->input('waktu_kirim')
      ]);
//       var_dump($result[2]);
// dd($request->input('id_payment'));
      $total = 0;
      $id = [];
      $x = [];
      $n = 0;
      foreach ($req['id_produk_harga'] as $key => $id_produk) {
  
          $order = new Order;
          $order->id_ra_payment = $result[2]->id;
          $order->id_order = $result[2]->id_transaksi;
          $order->id_kantor = $request->input('id_kantor');
          $order->ra_produk_harga_id = $id_produk;
          $order->id_pelanggan = Kontak::where('id',$kontakCus->id_kontak)->where('status','customer')->value('id');
          $order->id_anak = Kontak::where('id',$result[1]->id_kontak)->where('status','anak')->value('id');
          // $order->id_via_bayar = 1;
          $order->id_agen = $request->input('agen');
          $order->coa_debit = $request->input('coa'); 
          $order->quantity = $req['qty'][$key];
          $order->harga = $req['harga'][$key];
          $order->tgl_transaksi = $now;
          $order->total_transaksi = $req['qty'][$key] * $req['harga'][$key];
          $order->id_payment_method = $request->input('id_payment');
          // $order->id_produk_parent = $request->input('id_produk_parent');
          $order->lunas = 'y';
          $order->approve = 'y';
          $order->keterangan = 'Tunai';
          $order->nik_input = $request->input('nik_input');
          $order->cur = "IDR";
          $order->save();
          $n++;
      }


      $to_address = $request->input('email');
      $transdata = Payment::where('id',$result[2]->id)->first();
      $orderdata = Order::where('id_order',$result[2]->id_transaksi)->get();
      dd($transdata->id_transaksi);
      $nama = $req['nama'][0];
      $alamat = $req['alamat'];
      $kokec = $req['kota']; $req['kecamatan'];
      $email = $request->input('email'); $request->input('hp');
      $instruksion = DB::table('ra_payment_instructions')->select('keterangan')->where('id_payment_method',$request->input('id_payment'))->get();

      $hasil = Mail::send(
            (new Invoice($to_address, $transdata, $orderdata, $nama, $alamat, $kokec, $email, $instruksion))->build()
        );
      
      #ASK. GIMANA PENENTUAN JENIS PAYMENT METHODNYA? BACOT
      $paymeth = Paymeth::find($result[2]->id_payment_method);
      if($paymeth['parent_id'] <= 5){
          $npRegister = $this->npRegistration($result[2]->id_transaksi);
          $response = json_decode($npRegister);
          $np     = true;
      }
      else{
          $response = $result;
          $np     = false;
      }

      #Delete test Inputed Data
      // (Nicepay::$isProduction)? : $this->deleteTestPayment($result[2]->id_transaksi);

      #ASK. GIMANA RESPONSE TERBAIKNYA? KUMAHA MANEH WE
      if($np){
          if($response->resultCd == '0000'){
            return response()->json(["data"=>$response, 
                                      "status" => "true", 
                                      "message" => $response->resultMsg,
                                      "id_transaksi"=>$result[2]->id_transaksi,
                                      "parent_id"=>$paymeth['parent_id']
                                    ],200);
          }else{
            return response()->json(["errCode" => $response->resultCd, 
                                      "status" => "false",
                                      "message" => $response->resultMsg,
                                      "id_transaksi"=>$result[2]->id_transaksi,
                                      "parent_id"=>$paymeth['parent_id']
                                    ],200);
          }
      }
      else{
          return response()->json(["status" => "true", 
                                    "message" => $response,
                                    "id_transaksi"=>$result[2]->id_transaksi,
                                    "parent_id"=>$paymeth['parent_id']
                                  ],200);
      }
  }
  
  private function npRegistration($id_trx){
      
      $nicepay = new Nicepay;
      $vacctValidDt   = date("Ymd");
      $vacctValidDt   = date('Ymd', strtotime($vacctValidDt . ' +1 day'));
      $vacctValidTm   = date("His");

      $payment        = Payment::where('id_transaksi',$id_trx)->first();
      $detailOrder    = Order::where('id_order',$id_trx)->first();
      $kontak         = Kontak::where('id',$payment['id_kontak'])->where('status','customer')->first();
      $paymeth        = Paymeth::find($payment['id_payment_method']);

      $timestamp      = date("YmdHis");
      $referenceNo    = $id_trx;
      $amt            = $payment['nominal'];
      
      $payMeth        = $paymeth['parent_id'];
      $payMethod      = sprintf("%02d", $payMeth);
      $code           = $paymeth['code'];

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
      
      $codeArray   = ($payMeth == 1)?array():
                      (
                        ($payMeth == 2)?array("bankCd"=>$code):
                        (
                          ($payMeth == 3)?array("mitraCd"=>$code):array()
                        )
                      );

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
      $payment = Payment::select('id_kontak')->where('id_transaksi',$id_trx)->get();
      foreach($payment as $key => $val){
        Kontak::where('id',$val)->delete();
      }
      Payment::where('id_transaksi',$id_trx)->delete();
      Order::where('id_order',$id_trx)->delete();
  }

  public function sendemail(Request $request){
      // $req = $request->all();
      // $to_address = $request->input('email');
      // $transdata = ['id_transaksi' => 123,'tgl_transaksi' => '2019-12-20' ,'id_payment_method'=> 185 ,'jenis'=> 'online','status'=> 'lunas','tipe'=> 'transaksi','lunas'=>'y'];
      // $orderdata = 1912180016137;
     
      // $nama = $req['nama'];
      // $alamat = $req['alamat'];
      // $kokec = $req['kota']; $req['kecamatan'];
      // $email = $request->input('email'); $request->input('hp');
      // $instruksion = DB::table('ra_payment_instruction')->select('keterangan')->where('id_payment_method',$request->input('id_payment'))->get();

      // $hasil = Mail::send(
      //       (new Invoice($to_address, $transdata, $orderdata, $nama, $alamat, $kokec, $email, $instruksion))->build()
      //   );

      //   return response($hasil);

    }
}
