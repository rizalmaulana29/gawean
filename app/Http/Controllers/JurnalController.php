<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Order;
use App\CmsUser;
use App\Payment;
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
use App\Helpers\JurnalApi;

class JurnalController extends Controller
{
    public function getCustomers(){
      try {
          // Define the request method and endpoint
          $requestMethod = 'GET';
          $requestPath = '/public/jurnal/api/v1/customers/68843878';

          // Make the API request
          $jurnalApi = new JurnalApi();
          $response = $jurnalApi->request($requestMethod, $requestPath);

          // Display the response
          return response()->json(json_decode($response, true));

      } catch (\Exception $e) {
          return response()->json(['error' => $e->getMessage()], 500);
      }
    }

    public function Filtering(){
      $endDate = Carbon::now()->endOfMonth();
      $start = Carbon::today()->addHour(1)->toDateTimestring();

      $getDataTransaksi = Payment::select('ra_payment_dua.id as id','id_transaksi','nama_customer','alamat',
                                          'ra_payment_dua.tgl_transaksi',
                                          'ra_payment_dua.id_payment_method','tgl_kirim','hp','email','ra_payment_dua.id_kantor',
                                          'ra_payment_dua.id_agen','nominal_diskon','nominal_bayar','nominal_total','jenis','tgl',
                                          'tunai','ra_order_dua.id_entitas as id_entitas')
                                 ->leftjoin('ra_order_dua', 'ra_payment_dua.id_transaksi', '=', 'ra_order_dua.id_order')
                                 ->where([["ra_payment_dua.tgl_transaksi", ">=", $start],
                                          ["ra_payment_dua.tgl_transaksi", "<=", $endDate->toDateTimestring()]])
                                 ->where('tunai','Tunai')
                                 ->where('status','paid')
                                 ->where('varian','!=','Qurban')
                                 ->where('ra_payment_dua.lunas','y')
                                 ->where('person_id','=','')
                                 ->where(function($q) {
                                            $q->where('sisa_pembayaran', '=', 0)
                                            ->orWhereNull('sisa_pembayaran');
                                        })
                                 ->orderBy('ra_payment_dua.tgl_transaksi','ASC')
                                 ->first();
                                 // ->limit(50) //==>untuk mengambil data lebih banyak *update juga di createCustomer looping data
                                 // ->get();

      if (isset($getDataTransaksi)) {
        $validasiJurnal = $this->Entitas($getDataTransaksi['id_entitas'],$requester = $getDataTransaksi['id_transaksi']);
        if ($validasiJurnal['status'] == true) {
          $createCustomer = $this->CreateCustomer($getDataTransaksi);
          if ($createCustomer['status'] == true) {
            if ($getDataTransaksi['tgl_kirim'] <= $endDate->toDateString()) {
              $salesOrder = $this->SalesOrder($getDataTransaksi,$createCustomer['message']);
                if ($salesOrder['status'] == true) {
                        return response()->json(["status"       => true,
                                                 "message"      => "Data sales invoice berhasil di inputkan ke JurnalID",
                                                 "Data Request" => $getDataTransaksi,
                                                 "Data Response"=> $salesOrdertoInvoice['message']
                                                ],200);
                   
                }
                return $salesOrder;
            }else{
              $creditMemo = $this->creditMemo($getDataTransaksi,$createCustomer['message']);
              if ($creditMemo['status'] == true) {
                return response()->json(["status"       => true,
                                         "message"      => "Data berhasil di inputkan ke MEMO",
                                         "Data Request" => $getDataTransaksi,
                                         "Data Response"=> $creditMemo['message']
                                        ],200);
              }
              return $creditMemo;
            }
          } 
          return $createCustomer;
        }
        return response()->json(["status"       => false,
                                 "message"      => "Entitas / Kantor belum terdaftar di Jurnal"
                                ],200);
      }
      return response()->json(["status"       => false,
                               "message"      => "Tidak ada Data yang dapat di inputkan ke jurnalID"
                              ],200);

    }

    public function AdjustmentTransaksi (){

      $endDate = Carbon::now()->endOfMonth();
      $start = Carbon::now()->toDatestring();

      $getDataTransaksi = Payment::select('ra_payment_dua.id as id','id_transaksi','nama_customer','alamat','person_id',
                                          'ra_payment_dua.tgl_transaksi',
                                          'ra_payment_dua.id_payment_method','tgl_kirim','hp','email','ra_payment_dua.id_kantor',
                                          'ra_payment_dua.id_agen','nominal_diskon','nominal_bayar','nominal_total','jenis','tgl',
                                          'tunai','ra_order_dua.id_entitas as id_entitas','sales_invoice_id','memo_id',
                                          'recieve_payment_id','sales_order_id','order_message','apply_memo_id')
                                 ->leftjoin('ra_order_dua', 'ra_payment_dua.id_transaksi', '=', 'ra_order_dua.id_order')
                                 ->where('status','paid')
                                 ->where('tunai','Tunai')
                                 ->where('ra_payment_dua.lunas','y')
                                 ->where('person_id','!=','')
                                 ->where(function($q) {
                                            $q->where('memo_id', '!=', '')
                                            ->orWhere('order_message','!=','');
                                        })
                                 ->where('apply_memo_id','=','')
                                 ->Where('recieve_payment_id','=','')
                                 ->where(function($q) {
                                            $q->where('sisa_pembayaran', '=', 0)
                                            ->orWhereNull('sisa_pembayaran');
                                        })
                                 ->where('ra_payment_dua.tgl_kirim','=',$start)
                                 ->orderBy('ra_payment_dua.tgl_transaksi','ASC')
                                 ->first();
      // dd($getDataTransaksi);
      if (isset($getDataTransaksi)) {
        if ($getDataTransaksi['memo_id'] == '' && $getDataTransaksi['sales_order_id'] != '' && $getDataTransaksi['sales_invoice_id'] == '' && $getDataTransaksi['receive_payment_id'] == '') {
          $salesOrdertoInvoice = $this->SalesOrdertoInvoice($getDataTransaksi,$message = 0);
            if ($salesOrdertoInvoice['status'] == true){
              $createPayment = $this->receivePayment($getDataTransaksi,$salesOrdertoInvoice['message']);
              if ($createPayment['status'] == true) {
                return response()->json(["status"       => true,
                                         "message"      => "Data berhasil di inputkan ke JurnalID",
                                         "Data Request" => $getDataTransaksi,
                                         "Data Response"=> $createPayment['message']
                                        ],200);
              }
              return $createPayment;
            }
            return $salesOrdertoInvoice; 

        } else {
          $salesOrder = $this->SalesOrder($getDataTransaksi,$getDataTransaksi['person_id']);
          if ($salesOrder['status'] == true) {
            $salesOrdertoInvoice = $this->SalesOrdertoInvoice($getDataTransaksi,$salesOrder['message']);
              if ($salesOrdertoInvoice['status'] == true) {
                $applyMemo = $this->ApllyCreditMemo($getDataTransaksi,$salesOrdertoInvoice['id']);
                if ($applyMemo['status'] == true) {
                  return response()->json(["status"       => true,
                                       "message"      => "Data berhasil di inputkan ke Apply MEMO",
                                       "Data Request" => $getDataTransaksi,
                                       "Data Response"=> $applyMemo['message']
                                      ],200);
                }
                return $applyMemo;
              }
              return $salesOrdertoInvoice;
          }
          return $salesOrder;
        }
      }
      return response()->json(["status"       => false,
                               "message"      => "Tidak ada Data yang dapat di inputkan ke jurnalID"
                              ],200);
    }

    public function CreateCustomer ($getDataTransaksi){
      //Tambahkan looping (mis:foreach) jika data lebih dari satu
      $jurnalKoneksi = $this->Entitas($getDataTransaksi['id_entitas'],$requester = 'konektor');

      $dataRaw = [
                    "customer"  => ["first_name"     => $getDataTransaksi['nama_customer'].' ID'.substr($getDataTransaksi['id_transaksi'],-5), //nama lengkap dengan id_transaksi
                                    "display_name"   => $getDataTransaksi['nama_customer'].' ID'.substr($getDataTransaksi['id_transaksi'],-5), //nama lengkap
                                    "address"        => substr($getDataTransaksi['alamat'],0,255),
                                    "billing_address"=> substr($getDataTransaksi['alamat'],0,255),
                                    "phone"          => $getDataTransaksi['hp'],
                                    "mobile"         => $getDataTransaksi['hp'],
                                    "email"          => $getDataTransaksi['email'],
                                    "custom_id"      => $getDataTransaksi['id_transaksi'], //id_transaksi tidak boleh sama
                                    "default_ap_account_name" => "Pendapatan Diterima Di Muka"
                                    ]
                  ];

      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/customers",
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
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
                                        'id_transaksi'  =>$getDataTransaksi['id_transaksi'],
                                        'action'        => "CreateCustomer",
                                        'insert_at'     => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body'  => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'customer';
      $searchResponse = stripos($response, 'customer');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['person_id' => $dataResponse->customer->id]);
              $response = array("status"=>true,"message"=> $dataResponse->customer->id);
          }
          else{

              $response = array("status"=>false,"message"=> "create customer".$response);
          }
      }
      
      return $response;
    }

    public function SalesOrder($getDataTransaksi,$person_id){ 

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['id_entitas'],$requester = 'konektor');

      $agen   = '';
      if ($getDataTransaksi['id_agen'] != null) {
        $agen = CmsUser::where('id',$getDataTransaksi['id_agen'])->value('name');
      }

      if ($getDataTransaksi['nominal_diskon'] != null || $getDataTransaksi['nominal_diskon'] != " ") {
        $dataDiskon = $getDataTransaksi['nominal_diskon'];
      }else{
        $dataDiskon   = 0;
      }

      $kantor       = Kantor::where('id',$getDataTransaksi['id_kantor'])->value('kantor');
      $dataOrder    = Pendapatan::where('id_order',$getDataTransaksi['id_transaksi'])->get();
      $tglTransaksi = Carbon::now()->toDatestring();

      if ($getDataTransaksi['id_entitas'] == 'PDN') {
        $wh_name = $kantor;
      } else {
        $wh_name = "";
      }

      $detail_produk = [];
      foreach ($dataOrder as $key => $order) {

        $produk_harga        = Harga::where('id',$order['ra_produk_harga_id'])->value('jurnal_product_id');
        $nama_produk         = Harga::where('id',$order['ra_produk_harga_id'])->value('nama_produk');
        $produk              = ["quantity" => $order['quantity'], "rate"=> $order['harga'],"product_id"=> $produk_harga,"description" =>$nama_produk];
        array_push($detail_produk,$produk);
      }

      $dataRaw = [
                "sales_order"  => [ 
                                  "transaction_date"             => $tglTransaksi,
                                  "transaction_lines_attributes" => $detail_produk,
                                  "shipping_date"      => $getDataTransaksi['tgl_kirim'],
                                  "shipping_price"     => 0,
                                  "shipping_address"   => substr($getDataTransaksi['alamat'],0,250),
                                  "warehouse_name"     => $wh_name,
                                  "is_shipped"         => true,
                                  "address"            => substr($getDataTransaksi['alamat'],0,250),
                                  "due_date"           => $getDataTransaksi['tgl_kirim'],
                                  "discount_unit"      => $dataDiskon,
                                  "discount_type_name" => "Value",
                                  "person_id"          => $person_id,
                                  "tags"               => [$getDataTransaksi['tgl'],$getDataTransaksi['jenis'],$getDataTransaksi['tunai'],$kantor,$agen],
                                  "email"              => $getDataTransaksi['email'],
                                  "transaction_no"     => $getDataTransaksi['id_transaksi'],
                                  "custom_id"          => $getDataTransaksi['id_transaksi']
                                  ]
                  ];
      
      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/sales_orders",
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
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id'=> $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action'       => "SalesOrder",
                                        'insert_at'    => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body'=> $response
                                        ]);

      $findString     = 'sales_order';
      $searchResponse = stripos($response, 'sales_order');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $message = json_encode($dataResponse->sales_order->transaction_lines_attributes);
              $updatePayment= Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['sales_order_id' => $dataResponse->sales_order->id, 'order_message' => $message]);

              $response = array("status" =>true,
                                "id"     => $dataResponse->sales_order->id,
                                "message"=> $dataResponse->sales_order->transaction_lines_attributes);
          }
          else{
              $updatePayment= Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['sales_order_id' => "failed",'apply_memo_id' => "failed",'recieve_payment_id'=>"failed"]);
              $response = array("status"=>false,"message"=> "sales order".$response);
          }
      }
      
      return $response;
    }

    public function SalesOrdertoInvoice($getDataTransaksi,$message){

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['id_entitas'],$requester = 'konektor');

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
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['sales_invoice_id' => $dataResponse->sales_invoice->transaction_no]);

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

    public function receivePayment($getDataTransaksi,$transaction_no){

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['id_entitas'],$requester = 'konektor');

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
      
      $tglTransaksi = $getDataTransaksi['tgl_kirim'];

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

    //TIDAK DI GUNAKAN TAPI BISA DI BUTUHKAN
    // private function createExpenses($getDataTransaksi){
      
    //   $tgl = strtotime($getDataTransaksi['tgl_transaksi']);
    //   $tglTransaksi = date('Y-m-d',$tgl);

    //   $dataRaw = [
    //             "expense"  => [ 
    //                                     "refund_from_name"   => "Mandiri Publik 131 000 711 2586",
    //                                     "person_name"        => "Nicepay",
    //                                     "transaction_date"   => $tglTransaksi,
    //                                     "payment_method_name"=> "Transfer Bank",
    //                                     "payment_method_id"  => 792898,
    //                                     "transaction_no"     => $getDataTransaksi['id_transaksi'],
    //                                     "custom_id"          => $getDataTransaksi['id_transaksi'],
    //                                     "expense_payable"    => false,
    //                                     "transaction_account_lines_attributes"  => [[ "account_name" => "Biaya lain-lain",
    //                                                                                   "description"  => "biaya nicepay 2011240027650",
    //                                                                                   "debit"        => 4400
    //                                                                                ]],
    //                                   ]
    //               ];


    //   $encodedataRaw = json_encode($dataRaw);

    //   $curl = curl_init();

    //   curl_setopt_array($curl, array(
    //     CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/expenses",
    //     CURLOPT_RETURNTRANSFER => true,
    //     CURLOPT_ENCODING       => "",
    //     CURLOPT_MAXREDIRS      => 10,
    //     CURLOPT_TIMEOUT        => 0,
    //     CURLOPT_FOLLOWLOCATION => true,
    //     CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    //     CURLOPT_CUSTOMREQUEST  => "POST",
    //     CURLOPT_POSTFIELDS     => $encodedataRaw,
    //     CURLOPT_HTTPHEADER     => array(
    //                                     "apikey: 56593d3e45a37eb7033e356d33fd83c4",
    //                                     "Authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
    //                                     "Content-Type: application/json; charset=utf-8",
    //                                     "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p; nlbi_1892526=8trIdrnO9S4KHtCQKezQ4QAAAAC1Ln3MtHQzDOiZP5/QXp4v; incap_ses_959_1892526=3SwTKPUwzU0bAScrnA1PDc+i0V8AAAAA2wbKV6ShlqO9SQ9NTtMN7g=="
    //                                   ),
    //   ));
    //   $response = curl_exec($curl);
    //   $err = curl_error($curl);

    //   $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
    //                                     'id_transaksi' =>$getDataTransaksi['id_transaksi'],
    //                                     'action' => "createExpenses",
    //                                     'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
    //                                     'request_body' => $encodedataRaw,
    //                                     'response_body' => $response
    //                                     ]);

    //   $findString    = 'expense';
    //   $searchResponse = stripos($response, 'expense');

    //   if ($err) {
    //       $response = array("status"=>"failed","message"=>$err);
    //   } 
    //   else {
    //       if ($searchResponse == true){
    //           $dataResponse = json_decode($response);
    //           $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['expenses_id' => $dataResponse->expense->id]);
    //           $response = array("status" => true,
    //                             "id"     => $dataResponse->expense->id,
    //                             "message"=> $dataResponse->expense->transaction_no);
    //       }
    //       else{

    //           $response = array("status"=>false,"message"=> "sales order".$response);
    //       }
    //   }

    //   return $response;
    // }

    public function creditMemo ($getDataTransaksi,$person_id){
      
      $jurnalKoneksi = $this->Entitas($getDataTransaksi['id_entitas'],$requester = 'konektor');

      $paymentMethode =  Paymeth::where('id',$getDataTransaksi['id_payment_method'])->value('methode_jurnal');

      if ($getDataTransaksi['tunai'] == "Tunai") {
        $tipeTransaksi = "Pembayaran".$getDataTransaksi['id_transaksi'];
        $nominal       = $getDataTransaksi['nominal_total'];
      }else{
        $tipeTransaksi = "Dp".$getDataTransaksi['id_transaksi'];
        $nominal       = $getDataTransaksi['nominal_bayar'];
      }

      $tglTransaksi = Carbon::now()->toDatestring();

      $dataRaw = [
                "credit_memo"  => [ 
                                        "person_id"          => $person_id,
                                        "person_name"        => $getDataTransaksi['nama_customer'].' ID'.substr($getDataTransaksi['id_transaksi'],-5),
                                        "person_type"        => "customer",
                                        "transaction_date"   => $tglTransaksi,
                                        "transaction_no"     => $getDataTransaksi['id_transaksi'],
                                        "transaction_account_lines_attributes" => [[ "account_name"=> $paymentMethode,
                                                                                     "description" => $tipeTransaksi,
                                                                                     "debit"       => $nominal]],
                                        "memo"               => $tipeTransaksi,
                                        "custom_id"          => $getDataTransaksi['id_transaksi']
                                      ]
                  ];  
        

      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/credit_memos",
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
                                        'action' => "Credit Memo",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'credit_memo';
      $searchResponse = stripos($response, 'credit_memo');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['memo_id' => $dataResponse->credit_memo->id]);
              $response = array("status" => true,
                                "message"=> $dataResponse->credit_memo->id);
          }
          else{

              $response = array("status"=>false,"message"=> "credit memo".$response);
          }
      }

      return $response;     
    }

    public function ApllyCreditMemo ($getDataTransaksi,$transaction_no){

      $jurnalKoneksi = $this->Entitas($getDataTransaksi['id_entitas'],$requester = 'konektor');

      $nominal       = $getDataTransaksi['nominal_total'];

      $tglTransaksi = Carbon::now()->toDatestring();

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

