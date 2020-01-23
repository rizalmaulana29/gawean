<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Order;
use App\Payment;
use App\Paymeth;
use App\AdminEntitas;
use App\Anak;
use App\Instruction;
use App\Mail\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
      $now = Carbon::now();
      $expired_at = Carbon::now()->addHour(24);

      $this->passed = $req;

      $total = 0;
      $id = [];
      $x = [];
      $n = 0;

      $result[2] = Payment::create([
          'id_transaksi' => date("ymd") . '001' . mt_rand(1000,9999),
          'nama_customer' => $request->input('nama'),
          'alamat'      => $request->input('alamat'),
          'hp'          => $request->input('hp'),
          'email'       => $request->input('email'),
          'jk'          => $request->input('jk'),
          'id_kantor' => $request->input('id_kantor'),
          'id_payment_method' => $request->input('id_payment'),
          'nominal' => $request->input('nominal'),
          'nominal_total' => $request->input('total'),
          'nominal_diskon' => $request->input('diskon'),
          'coa_debit' => $request->input('coa'),
          'sumber_informasi' => $request->input('sumber_info'),
          'tgl_transaksi' => $now,
          'status' => 'Tunai',
          'jenis' => 'Online',
          'kode' => $request->input('promo'),
          'id_agen' => $request->input('agen'),
          'tgl_kirim' => $request->input('tgl_kirim'),
          'waktu_kirim' => $request->input('waktu_kirim'),
          'expired_at' => $expired_at
      ]);

      foreach ($req['id_produk_harga'] as $key => $id_produk) {
  
          $order = new Order;
          $order->id_ra_payment = $result[2]->id;
          $order->id_order = $result[2]->id_transaksi;
          $order->id_kantor = $request->input('id_kantor');
          $order->ra_produk_harga_id = $id_produk; 
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


      #ganti table kontak dengan hanay table anak
      $url = "https://api.rumahaqiqah.co.id/uploads/online";
      foreach ($req['nama_anak'] as $key => $anak) {
        $result[1] = new Anak;
        $result[1]->nama_anak      = $anak;
        $result[1]->tgl_lahir      = $req['tgl_lahir'][$key];
        $result[1]->tempat_lahir   = $req['tempat_lahir'][$key];
        $result[1]->jk             = $req['jk_anak'][$key];
        $result[1]->ibu            = $req['ibu'][$key];
        $result[1]->ayah           = $req['ayah'][$key];
        $result[1]->ra_payment_id  = $result[2]->id;
        $result[1]->id_order       = $result[2]->id_transaksi;

        // dd($request->file('foto_anak') );
        if ($request->file('foto_anak')[$key]) {
          $image = $request->file('foto_anak')[$key];
          $imageName = 'raqiqah'. rand(1,1000). '.' . $image->getClientOriginalExtension();
          $storeDatabase = $url. "/" .$imageName;
          $path= "/uploads/online/";
          $image->storeAs($path,$imageName);
          $result[1]->foto = $storeDatabase;
        } else {
          return response()->json(["Status" => "Field Foto is Not file"]);
        }
        $result[1]->save();
      }

      
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

      if ($paymeth['parent_id'] == 2 && $response->resultCd == '0000') {
        $title = "Virtual Account :";
        $number = $response->vacctNo;
      } elseif ($paymeth['parent_id'] == 3 && $response->resultCd == '0000') {
        $title = "Kode Pembayaran :";
        $number = $response->payNo;
      } else{
        $title = "No.Rekening :";
        $number = DB::table('ra_bank_rek')->where('id_payment_method',$request->input('id_payment'))->where('id_kantor',$request->input('id_kantor'))->value('id_rekening');
      }

      $to_address = $request->input('email');
      $transdata = Payment::where('id',$result[2]->id)->first();
      $orderdata = Order::where('id_order',$result[2]->id_transaksi)->get();

      $nama = $req['nama'];
      $alamat = $req['alamat'];
      $email = $request->input('email'); 
      $hp = $request->input('hp');
      $parent_id = $request->input('parent_id');

      $hasil = Mail::send(
            (new Invoice($to_address, $transdata, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$number,$title))->build()
        );

      #ASK. GIMANA RESPONSE TERBAIKNYA? KUMAHA MANEH WE
      if($np){
          if($response->resultCd == '0000'){
            return response()->json(["data"=>$response, 
                                      "status" => "true", 
                                      "message" => $response->resultMsg,
                                      "id_transaksi"=>$result[2]->id_transaksi,
                                      "parent_id"=>$paymeth['parent_id'],
                                      "expired_at"=>date('d M Y ,H:i',strtotime($transdata->expired_at))
                                    ],200);
          }else{
            return response()->json(["errCode" => $response->resultCd, 
                                      "status" => "false",
                                      "message" => $response->resultMsg,
                                      "id_transaksi"=>$result[2]->id_transaksi,
                                      "parent_id"=>$paymeth['parent_id'],
                                      "expired_at"=>date('d M Y ,H:i',strtotime($transdata->expired_at))
                                    ],200);
          }
      }
      else{
          return response()->json(["status" => "true", 
                                    "message" => $response,
                                    "id_transaksi"=>$result[2]->id_transaksi,
                                    "parent_id"=>$paymeth['parent_id'],
                                    "expired_at"=>date('d M Y ,H:i',strtotime($transdata->expired_at))
                                  ],200);
      }
  }
  
  private function npRegistration($id_trx){
      
      $nicepay = new Nicepay;
      // $vacctValidDt   = date("Ymd");
      // $ValidDt   = date('Ymd', strtotime($vacctValidDt . ' +1 day'));
      // $ValidTm   = date("His");

      $payment        = Payment::where('id_transaksi',$id_trx)->first();
      $detailOrder    = Order::where('id_order',$id_trx)->first();
      // $kontak         = Kontak::where('id',$payment['id_kontak'])->where('status','customer')->first();
      $paymeth        = Paymeth::find($payment['id_payment_method']);
      $merData        = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();

      $iMid       = Nicepay::$isProduction ? $merData['mid']:$merData['mid_sand'];
      $merKey     = Nicepay::$isProduction ? $merData['merkey']:$merData['merkey_sand'];

      $timestamp      = date("YmdHis");
      $referenceNo    = $id_trx;
      $amt            = $payment['nominal'];
      
      $payMeth        = $paymeth['parent_id'];
      $payMethod      = sprintf("%02d", $payMeth);
      $code           = $paymeth['code'];

      $ValidDt   = date('Ymd',strtotime($payment['expired_at']));
      $ValidTm   = date('His',strtotime($payment['expired_at']));
      
      $merchantToken  = $nicepay->merchantToken($timestamp,$iMid,$referenceNo,$amt,$merKey);

      #ASK. GIMANA MENDINAMIS KAN PARAMETERNYA?
      $customerName       = $payment['nama_customer'];
      $customerPhone      = $payment['hp'];
      $customerEmail      = $payment['email'];
      $customerAddress    = $payment['alamat'];
      // $customerCity       = $payment['kota'];
      // $customerProv       = "Jawa Barat";
      // $customerPostId     = "40331";
      // $customerCountry    = "Indonesia";

      $deliveryNm         = "Rumah Aqiqah";
      $deliveryPhone      = "0817 274 724 / 0813 7007 1330";
      $deliveryAddr       = "Jl. Babakan Sari I No. 149";
      $deliveryCity       = "Bandung";
      $deliveryState      = "Jawa Barat";
      $deliveryPostCd     = "40283";
      $deliveryCountry    = "Indonesia";
      $description        = "Transaksi Rumah Aqiqah";

      #billing = detail customer
      #delivery = detail pengiriman
      $detailTrans = array(
              "timeStamp"     =>$timestamp,
              "iMid"          =>$iMid,
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
              "merFixAcctId"  =>""
          );
      
      $codeArray   = ($payMeth == 1)?array():
                      (
                        ($payMeth == 2)?array("bankCd"=>$code,"vacctValidDt"=>$ValidDt,"vacctValidTm"=>$ValidTm):
                        (
                          ($payMeth == 3)?array("mitraCd"=>$code,"payValidDt"=>$ValidDt,"payValidTm"=>$ValidTm):array()
                        )
                      );

      $detailTrans = array_merge($detailTrans,$codeArray);
      $detailTrans = json_encode($detailTrans);

      $transaksiAPI = $nicepay->nicepayApi("nicepay/direct/v2/registration",$detailTrans); 
      
      $response     = json_decode($transaksiAPI);
      if($response->resultCd == '0000'){
        $tXid     = $response->tXid;
        $msg      = $response->resultMsg;
        $no_bayar = ($payMeth == 2)?$response->vacctNo:(($payMeth == 3)?$response->payNo:"");
      }else{
        $tXid     = "";
        $no_bayar = "";
        $msg      = $response->resultMsg;
      }

      $nicepayLog = new Nicepaylog;
      $nicepayLog->id_order = $id_trx;
      $nicepayLog->payment_method = $payMethod;
      $nicepayLog->code     = $code;
      $nicepayLog->txid     = $tXid;
      $nicepayLog->virtual_account_no = $no_bayar;
      $nicepayLog->update = Carbon::now();
      $nicepayLog->request  = addslashes($detailTrans);
      $nicepayLog->response = addslashes($transaksiAPI);
      $nicepayLog->status   = addslashes($msg);
      $nicepayLog->action   = "Registration";
      $nicepayLog->expired_at= $payment['expired_at'];
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

  public function image($imageName){
    $path = '/usr/share/nginx/html/api.rumahaqikah.co.id/storage/app/uploads/online/'. $imageName;
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $header = ['Content-Type' => pathinfo($path, PATHINFO_EXTENSION)];
    $response = new BinaryFileResponse($path, 200 , $header);

    return $response;
    // return response()->download($path, $imageName, $header);
  }

}
