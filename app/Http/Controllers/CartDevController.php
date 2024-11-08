<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Order;
use App\Payment;
use App\Paymeth;
use App\PO;
use App\PO_detail;
use App\AdminEntitas;
use App\Anak;
use App\Instruction;
use App\Mail\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Nicepaylog;
use App\Thirdparty\Nicepay\Nicepay;

class CartDevController extends Controller
{
    public function __construct()
    {
      // $this->middleware('auth');
      Nicepay::$isProduction = env('NICEPAY_IS_PRODUCTION', 'true');

      date_default_timezone_set("Asia/Jakarta");
    }
    
    public function cart(Request $request){
      // return response()->json(['status' => true]);
      //test
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
          'varian'      => 'Aqiqah',
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
          'lunas' => 'Y',
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
          $order->lunas = 'y';
          $order->approve = 'y';
          $order->keterangan = 'Tunai';
          $order->nik_input = $request->input('nik_input');
          $order->cur = "IDR";
          // $id_produk_parent = Produk::select('id_produk_parent')->where('id_produk',$id_produk)->first();
          if($request['id_produk_parent'][$key] == 89 || $request['id_produk_parent'][$key] == 20){
            $order->disaksikan = $request['disaksikan'][$key];
          }
          else{$order->disaksikan = 'N';}
          $order->note = $request['note'][$key];

          $order->save();

          $n++;
      }

      if($request['id_produk_parent'][$key] == 22){
            $stockingTool = $this->stock($request->input('id_kantor'),$result[2]->id_transaksi);
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
        $result[1]->keterangan     = $req['keterangan'][$key];
        $result[1]->ra_payment_id  = $result[2]->id;
        $result[1]->id_order       = $result[2]->id_transaksi;

        // dd($request->file('foto_anak') );
        // if(isset($request->file('foto_anak')[$key])) {
        //   $image = $request->file('foto_anak')[$key];
        //   $imageName = 'raqiqah'. rand(1,1000). '.' . $image->getClientOriginalExtension();
        //   // $storeDatabase = $url. "/" .$imageName;
        //   // $path= "/uploads/online/";
        //   // $image->storeAs($path,$imageName);

        //   // Store File
        //   $disk = Storage::disk('gcs');
        //   // create a file
        //   $disk->putFileAs('foto', $request->file('foto_anak')[$key], $imageName);

        //   $result[1]->foto = $disk->url('foto/$imageName');;

        // }elseif (!isset($request->file('foto_anak')[$key])) {
        //   $result[1]->foto = 'https://backend.rumahaqiqah.co.id/vendor/crudbooster/default.jpg';
        // } else {
        //   return response()->json(["Status" => "Field Foto is Not file"]);
        // }
        $result[1]->save();
      }

      
      #ASK. GIMANA PENENTUAN JENIS PAYMENT METHODNYA?
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
      $hasil = $this->sendWa($transdata, $nama, $alamat, $email, $hp,$number,$title);

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

      // dd($merData);
      $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
      $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];

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
              // "billingCity"   =>$customerCity,
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
      $nicepayLog->id_entitas = $paymeth['id_entitas'];
      $nicepayLog->expired_at= $payment['expired_at'];
      $nicepayLog->source_data= "fe";
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

  public function image($imageName){
    $path = '/code/storage/app/uploads/online/'. $imageName;
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $headers = [
      'Content-Type' => 'image/'. $type,
  ];
    $response = new BinaryFileResponse($path, 200 , $headers);

    return $response;
    // return response()->download($path, $imageName, $header);
  }

  private function sendWa($transdata, $nama, $alamat, $email, $hp,$number,$title){
    if (substr($hp,0,1) == 0) {
      $nohp = str_replace('0','+62',$hp);
    }

    else {
        $nohp = $hp;
    }

    $bankRek = DB::table('ra_bank_rek')->select('keterangan','id_rekening','gambar','id_payment_method','parent_id')
                 ->where('id', $transdata->id_payment_method)
                 ->first();

    $link = DB::table('ra_payment_method')
                 ->where('id', $bankRek->id_payment_method)
                 ->value('url_bayar');

    $order = Order::where('id_order', $transdata->id_transaksi)->get();

    if ($link == null || $link == ' ') {
      $link = " ";
    } else {
      $link = $link;
    }
    

    if ($bankRek->keterangan == "cash") {
      $rek = $bankRek->keterangan;
    } else {
      $rek = $bankRek->keterangan.'\\n'.$bankRek->id_rekening;
    }

    $produk = "";
    $i = 1;
    foreach ($order as $key) {
      $label = DB::table('ra_produk_harga')->select('nama_produk')->where('id', $key->ra_produk_harga_id)->first();
      $ending = (count($order) == $i)?"":" + ";
      $produk .= $key->quantity.' '.$label->nama_produk .$ending;
      $i++;
    }
    

    $key='d99e363936ff07dec5c545c3cf7b780126ab3d3c5e86b071';
    $url='http://116.203.92.59/api/async_send_message';

    $data = array("phone_no"=> $nohp,
                  "key"   =>$key,
                  "message" =>
                  "Assalamu'alaikum Ayah/Bunda".' '.$nama.', 🙏'.'
                  \\n'.'Berikut adalah tagihan transaksi Ayah/Bunda di Rumah Aqiqah'.'
                  \\n'.'untuk pemesanan di tanggal '.date('d M Y ,H:i',strtotime($transdata->expired_at)).'
                  \\n'.'
                  \\n'.'Dengan detail order sebagai berikut:'.'
                  \\n'.' Order ID          : '.$transdata->id_transaksi.'
                  \\n'.' Nama              : '.$nama.'
                  \\n'.' No. Hp            : '.$hp.'
                  \\n'.' Keterangan pesanan: '.$produk.'
                  \\n'.' Total Tagihan     : IDR '.number_format($transdata['nominal_total']).'
                  \\n'.'
                  \\n'.'Silahkan melakukan pembayaran maksimal 24 jam sejak Ayah/Bunda menerima pesan ini,'.'
                  \\n'.'atau pemesananan Ayah/Bunda akan di anggap gagal.'.'
                  \\n'.'
                  \\n'.'Metode Pembayaran:'.'
                  \\n'.'- '.$rek.'
                  \\n'.'- Kode pembayaran : '.$number.'
                  \\n'.'
                  \\n'.'Untuk panduan bayar, silahkan klik link berikut:'.'
                  \\n'.$link.'
                  \\n'.'
                  \\n'.'Butuh bantuan layanan Customer Care kami, silahkan klik link berikut:'.'
                  \\n'.'wa.me/6282218757703'.'
                  \\n'.'
                  \\n'.'Ingat Order ID Ayah/Bunda saat menghubungi Customer Care.'.'
                  \\n'.'
                  \\n'.'Terima kasih telah memilih rumahaqiqah.co.id'.'
                  \\n'.'
                  \\n'.'Terima Kasih 😊🙏'

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

  public function checkNumber(Request $request){

    $phone_no = $request->input('hp');
    // dd($phone_no);
    $key='d99e363936ff07dec5c545c3cf7b780126ab3d3c5e86b071';
    $url='http://116.203.92.59/api/check_number';
    $data = array(
      "phone_no" =>$phone_no,
      "key"    =>$key
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

    if ($res == 'exist') {
      echo "Valid";
    } else {
      echo "not Valid";
    }
    
    curl_close($ch);
  }

  public function notifTransaksi(Request $request){
    //$transdata->id_kantor
    $kantor = Kantor::where('id',$request['id_kantor'])->value('kantor');
    
    $data ='Ada transaksi Customer di Rumah Aqiqah Cabang '.$kantor.'
    untuk pemesanan di tanggal '.date('d M Y ,H:i',strtotime($request['expired_at'])).'
    Dengan detail order sebagai berikut:'.'
      Order ID          : '.$request['id_transaksi'].'
      Nama              : '.$request['nama'].'
      Total Tagihan     : IDR '.number_format($request['nominal_total']).'

    Metode Pembayaran:'.'
      - '.$request['rek'].'

    Tolong di cek @Rumah_Aqiqah,'.'
    Terima Kasih';

    $datasend = urlencode($data);

    $url='https://api.telegram.org/bot1582839336:AAED5tbyAI3o93qMELdCX7Awvs6vAmDSJ7A/sendMessage?chat_id=-1001257247870&text='.$datasend;
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    // echo $response;
  }

  private function stock($id_kantor,$id_transaksi){

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.rumahaqiqah.co.id/api/stockTool',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array('id_transaksi' => $id_transaksi,'id_kantor' => $id_kantor),
    ));

    $response = curl_exec($curl);

    curl_close($curl);

  }

  public function testing_aja (Request $request){
    
    $jurnalKoneksi = $this->Entitas($request['id_entitas'],$requester = 'konektor');

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.jurnal.id/core/api/v1/sales_orders/'.$request['id'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
      CURLOPT_HTTPHEADER => array(
        "apikey: ".$jurnalKoneksi['jurnal_key'],
        "Authorization: ".$jurnalKoneksi['jurnal_auth'],
        'content-type:  application/json',
      ),
    ));

      $response = curl_exec($curl);
      $err = curl_error($curl);
      $findString     = 'sales_order';
      $searchResponse = stripos($response, 'sales_order');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $message = json_encode($dataResponse->sales_order->transaction_lines_attributes);
              // dd($message);
              $updatePayment= Payment::where('sales_order_id',$request['id'])->update(['sales_order_id' => $dataResponse->sales_order->id, 'order_message' => $message]);

              $response = array("status" =>true,
                                "id"     => $dataResponse->sales_order->id);
          }
          else{

              $response = array("status"=>false,"message"=> "sales order".$response);
          }
      }
      
      return $response;
  }

  private function Entitas($id_entitas,$requester){
      
      $getDataKoneksi = AdminEntitas::where('id_entitas',$id_entitas)->first();
      if ($getDataKoneksi['jurnal_key'] != '' && $getDataKoneksi['jurnal_key'] != null ) {
        if ($requester != 'konektor') {
          $response = array("status"=>true,"message"=> "API key dan API auth terdaftar");
        } else {
          $response = $getDataKoneksi;
        }
      } else {
        if ($requester != 'konektor') {
          $update = Payment::where('id_transaksi',$requester)->update(['person_id' => 'none','sales_order_id' => 'none','sales_invoice_id' => 'none','recieve_payment_id' => 'none','memo_id' => 'none','apply_memo_id' => 'none']);
          $response = array("status"=>false,"message"=> "belum ada key jurnal");
        } else {
          $response = array("status"=>false,"message"=> "belum ada key jurnal");
        }
      }

      return $response;
    }

}
