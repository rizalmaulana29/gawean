<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Order;
use App\CmsUser;
use App\Payment;
use App\Pengiriman;
use App\PengirimanDetail;
use App\Pendapatan;
use App\JurnalLog;
use App\Paymeth;
use App\AdminEntitas;
use App\Anak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JurnalDeliveryController extends Controller
{
    public function Filtering(){
      $endDate = Carbon::now()->endOfMonth();
      $start = Carbon::today()->addHour(1)->toDateTimestring();

      $getDataTransaksi = Pengiriman::select('ra_pengiriman.id as id','ra_pengiriman.id_pt','ra_pengiriman.grand_total_harga',
                                          'ra_pengiriman.id_transaksi','ra_pengiriman.alamat','admin_entitas.id_entitas as entitas',
                                          'ra_pengiriman.tgl_kirim','ra_pengiriman.grand_total_qty','delivery_id','ra_payment_dua.email',
                                          'ra_pengiriman.grand_total_hpp','tambahan_ongkir','ra_pengiriman.alamat',
                                          'ra_payment_dua.person_id','ra_payment_dua.id_pt','ra_payment_dua.sales_order_id')
                                 ->leftjoin('ra_payment_dua', 'ra_pengiriman.id_transaksi', '=', 'ra_payment_dua.id_transaksi')
                                 ->leftjoin('admin_entitas', 'ra_payment_dua.id_pt', '=', 'admin_entitas.id')
                                 ->where('delivery_id','=','')
                                 ->orderBy('ra_pengiriman.tgl_kirim','ASC')
                                 ->first();

      if (isset($getDataTransaksi)) {
        $validasiJurnal = $this->Entitas($getDataTransaksi['entitas'],$requester = $getDataTransaksi['id_transaksi']);
        if ($validasiJurnal['status'] == true) {
          $salesDelivery = $this->SalesDelivery($getDataTransaksi);
            if ($salesDelivery['status'] == true) {
                    return response()->json(["status"       => true,
                                             "message"      => "Data sales invoice berhasil di inputkan ke JurnalID",
                                             "Data Request" => $getDataTransaksi,
                                             "Data Response"=> $salesDelivery['message']
                                            ],200);
               
            }
            return $salesDelivery;
        }
        return response()->json(["status"       => false,
                                 "message"      => "Entitas / Kantor belum terdaftar di Jurnal"
                                ],200);
      }
      return response()->json(["status"       => false,
                               "message"      => "Tidak ada Data yang dapat di inputkan ke jurnalID"
                              ],200);

    }

   

    public function SalesDelivery($getDataTransaksi){ 

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['entitas'],$requester = 'konektor');

      $dataOrder    = PengirimanDetail::where('id_pengiriman',$getDataTransaksi['id'])->get();
      $tglTransaksi = Carbon::now()->toDatestring();

      $detail_produk = [];
      foreach ($dataOrder as $key => $order) {

        $produk_harga        = Harga::where('id',$order['id_produk_harga'])->value('jurnal_product_id');
        $nama_produk         = Harga::where('id',$order['id_produk_harga'])->value('nama_produk');
        $produk              = ["quantity" => $order['qty'], "rate"=> $order['harga'],"product_id"=> $produk_harga,"description" =>$nama_produk];
        array_push($detail_produk,$produk);
      }

      $dataRaw = [
                "sales_delivery"  => [ 
                                  "person_id"          => $getDataTransaksi['person_id'],
                                  "email"              => $getDataTransaksi['email'],
                                  "is_shipped"         => true,
                                  "shipping_address"   => substr($getDataTransaksi['alamat'],0,250),
                                  "transaction_date"   => $tglTransaksi,
                                  "transaction_no"     => $getDataTransaksi['id_transaksi'],
                                  "selected_po_id"     => $getDataTransaksi['sales_order_id'],
                                  "transaction_lines_attributes" => $detail_produk,
                                  "shipping_price"     => $getDataTransaksi['tambahan_ongkir']
                                  ]
                  ];
      
      $encodedataRaw = json_encode($dataRaw);
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.jurnal.id/core/api/v1/sales_deliveries/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $encodedataRaw,
        CURLOPT_HTTPHEADER => array(
                                    "apikey: ".$jurnalKoneksi['jurnal_key'],
                                    "Authorization: ".$jurnalKoneksi['jurnal_auth'],
                                    "content-type: application/json"
                                  ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);


      $insertTolog = JurnalLog::insert(['ra_payment_id'=> $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action'       => "SalesDelivery",
                                        'insert_at'    => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body'=> $response
                                        ]);

      $findString     = 'sales_delivery';
      $searchResponse = stripos($response, 'sales_delivery');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePayment= Pengiriman::where('id',$getDataTransaksi['id'])->update(['delivery_id' => $dataResponse->sales_delivery->id]);

              $response = array("status" =>true,
                                "id"     => $dataResponse->sales_delivery->id,
                                "message"=> $dataResponse->sales_delivery->transaction_lines_attributes);
          }
          else{

              $response = array("status"=>false,"message"=> "sales delivery".$response);
          }
      }
      
      return $response;
    }

    public function SalesOrdertoInvoice($getDataTransaksi,$message){

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['entitas'],$requester = 'konektor');

      if ($message == 0 && $getDataTransaksi['order_message'] != " ") {
        $sales_atribute = json_decode($getDataTransaksi['order_message']);
      } else {
        $sales_atribute = $message;
      }

      $detail_atribute = [];
      foreach ($sales_atribute as $key => $atribute) {
  
        $produk              = ["id" => $atribute->id, "quantity"=> $atribute->quantity];
        array_push($detail_atribute,$produk);
      }
      $tglTransaksi = Carbon::now()->toDatestring();

      $dataRaw = [
                "sales_order"  => [ 
                                  "transaction_date"   => $tglTransaksi,
                                  "transaction_lines_attributes" => $detail_atribute
                                  ]
                  ];

      $encodedataRaw = json_encode($dataRaw);

      if ($message == 0 && $getDataTransaksi['sales_order_id'] != " ") {
        $salesOrderId = $getDataTransaksi['sales_order_id'];
      } else {
        $salesOrderId = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->value('sales_order_id');
      }

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/sales_orders/".$salesOrderId."/convert_to_invoice",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $encodedataRaw,
        CURLOPT_HTTPHEADER     => array(
                                        "apikey: ".$jurnalKoneksi['jurnal_key'],
                                        "Authorization: ".$jurnalKoneksi['jurnal_auth'],
                                        "Content-Type: application/json; charset=utf-8",
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p; nlbi_1892526=8trIdrnO9S4KHtCQKezQ4QAAAAC1Ln3MtHQzDOiZP5/QXp4v; incap_ses_959_1892526=3SwTKPUwzU0bAScrnA1PDc+i0V8AAAAA2wbKV6ShlqO9SQ9NTtMN7g=="
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id'=> $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action'       => "SalesOrdertoInvoice",
                                        'insert_at'    => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body'=> $response
                                        ]);

      $findString    = 'sales_invoice';
      $searchResponse = stripos($response, 'sales_invoice');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse  = json_decode($response);
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['sales_invoice_id' => '$dataResponse->sales_invoice->id' ,'si_transaction'=> $dataResponse->sales_invoice->transaction_no]);

              $response = array("status" => true,
                                "id"     => $dataResponse->sales_invoice->id,
                                "message"=> $dataResponse->sales_invoice->transaction_no);
          }
          else{

              $response = array("status"=>false,"message"=> "sales invoice".$response);
          }
      }

      return $response;
    }

    public function ApllyCreditMemo ($getDataTransaksi){

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['entitas'],$requester = 'konektor');

      $sisaBayar = Payment::where('id_parent',$getDataTransaksi['id'])->value('nominal_total');

      if($getDataTransaksi['tunai'] == "Tunai"){

        $nominal       = $getDataTransaksi['nominal_total'];

      }elseif ($getDataTransaksi['tunai'] == " ") {

        $nominal       = $getDataTransaksi['nominal_total'];

      }else{

          $nominal       = $getDataTransaksi['nominal_bayar'] - $sisaBayar;

      }

      $tglTransaksi = Carbon::now()->toDatestring();
      $transaction_no = $getDataTransaksi['sales_invoice_id'];

      $dataRaw = [
                "customer_apply_credit_memo"  => [ 
                                                  "person_id"              => $getDataTransaksi['person_id'],
                                                  "selected_credit_memo_id"=> $getDataTransaksi['memo_id'],
                                                  "records_attributes"     => [[ "transaction_id"=> $transaction_no,
                                                                                 "amount" => $nominal]]
                                                ]
                  ];  
      var_dump($dataRaw);
      $encodedataRaw = json_encode($dataRaw);
      var_dump($encodedataRaw);
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/customer_apply_credit_memo",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $encodedataRaw,
        CURLOPT_HTTPHEADER     => array(
                                        "apikey: ".$jurnalKoneksi['jurnal_key'],
                                        "Authorization: ".$jurnalKoneksi['jurnal_auth'],
                                        "Content-Type: application/json; charset=utf-8",
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p; incap_ses_956_1892526=PV4+bT4OPmmys22YG2VEDUti2F8AAAAAxxOSJglDvTynnT2DtUC2Xg==; nlbi_1892526=swSXL5ITyjseS65LKezQ4QAAAACk4+Rxw/6k0udeObF0BXEI; incap_ses_962_1892526=VQ9FJ6/WYhZ+5dcXGLZZDeL02l8AAAAA/WbrlUVFbEocG5UqQCMVsw=="
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action' => "Apply Credit Memo",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'customer_apply_credit_memo';
      $searchResponse = stripos($response, 'customer_apply_credit_memo');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['apply_memo_id' => $dataResponse->customer_apply_credit_memo->id]);
              $response = array("status" => true,
                                "message"=> $dataResponse->customer_apply_credit_memo);
          }
          else{

              $response = array("status"=>false,"message"=> "customer apply credit memo".$response);
          }
      }

      return $response;     
    }

    public function receivePayment($getDataTransaksi){

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['entitas'],$requester = 'konektor');

      $paymentMethode =  Paymeth::where('id',$getDataTransaksi['id_payment_method'])->first();

      $transfer = [26,33,2,3,4,5]; //id ra_bank_rek u/ transfer dan Nicepay

      if (in_array($paymentMethode->parent_id, $transfer)) {
        if ($getDataTransaksi['id_entitas'] == 'PDN') {
          $payment_method_name = "Transfer Bank";
          $payment_method_id   = "1539636";
          $deposit_to_name     = "Mandiri 1310012793792";
        } else {
          $payment_method_name = "Transfer Bank";
          $payment_method_id   = $paymentMethode->methode_id_jurnal;
          $deposit_to_name     = $paymentMethode->methode_jurnal;
        }
      } else {
        if ($getDataTransaksi['id_entitas'] == 'PDN') {
          $payment_method_name = "Kas Tunai";
          $payment_method_id   = "1539634";
          $deposit_to_name     = "Kas";
        } else {
          if ($getDataTransaksi['id_entitas'] == 'ANA') {
          $payment_method_name = "Cash";
          } else {
            $payment_method_name = "Kas Tunai";
          }
          $payment_method_id   = $paymentMethode->methode_id_jurnal;
          $deposit_to_name     = $paymentMethode->methode_jurnal;
        }
      }
      
      $tglTransaksi = Carbon::now();
      $transaction_no = $getDataTransaksi['si_transaction'];

      $dataRaw = [
                "receive_payment"  => [ 
                                        "transaction_date"    => $tglTransaksi,
                                        "records_attributes"  => [[ "transaction_no" => $transaction_no,
                                                                    "amount"         => $getDataTransaksi['nominal_total']]],
                                        "custom_id"           => $getDataTransaksi['id_transaksi'],
                                        "payment_method_name" => $payment_method_name,
                                        "payment_method_id"   => $payment_method_id,
                                        "is_draft"            => false,
                                        "deposit_to_name"     => $deposit_to_name,
                                      ]
                  ];


      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/receive_payments",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $encodedataRaw,
        CURLOPT_HTTPHEADER     => array(
                                        "apikey: ".$jurnalKoneksi['jurnal_key'],
                                        "Authorization: ".$jurnalKoneksi['jurnal_auth'],
                                        "Content-Type: application/json; charset=utf-8",
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p; nlbi_1892526=8trIdrnO9S4KHtCQKezQ4QAAAAC1Ln3MtHQzDOiZP5/QXp4v; incap_ses_959_1892526=3SwTKPUwzU0bAScrnA1PDc+i0V8AAAAA2wbKV6ShlqO9SQ9NTtMN7g=="
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action' => "receivePayment",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'receive_payment';
      $searchResponse = stripos($response, 'receive_payment');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['recieve_payment_id' => $dataResponse->receive_payment->id]);
              $response = array("status" => true,
                                "id"     => $dataResponse->receive_payment->id,
                                "message"=> $dataResponse->receive_payment->transaction_no);
          }
          else{

              $response = array("status"=>false,"message"=> "recieve payment ".$response);
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

