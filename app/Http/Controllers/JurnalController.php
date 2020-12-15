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

class JurnalController extends Controller
{
    public function Filtering(){
      $endDate = Carbon::now()->endOfMonth();
      $start = Carbon::now()->toDateTimestring();

      $getDataTransaksi = Payment::where([["tgl_transaksi", ">=", $start],["tgl_transaksi", "<=", $endDate->toDateTimestring()]])
                                 ->where('lunas','y')
                                 ->where('person_id','')
                                 ->whereIn('id_kantor', [6, 17])
                                 ->where(function($q) {
                                            $q->where('sisa_pembayaran', '=', 0)
                                            ->orWhereNull('sisa_pembayaran');
                                        })
                                 ->where('tgl_kirim','<=',$endDate->toDateString())
                                 ->orderBy('tgl_transaksi','ASC')
                                 ->first();
                                 // ->limit(50) //==>untuk mengambil data lebih banyak *update juga di createCustomer looping data
                                 // ->get();
      if (isset($getDataTransaksi)) {                      
        $createCustomer = $this->CreateCustomer($getDataTransaksi);
        if ($createCustomer['status'] == true) {
          $salesOrder = $this->SalesOrder($getDataTransaksi,$createCustomer['message']);
            if ($salesOrder['status'] == true) {
              $salesOrdertoInvoice = $this->SalesOrdertoInvoice($getDataTransaksi,$salesOrder['id'],$salesOrder['message']);
                if ($salesOrdertoInvoice['status'] == true) {
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
            }
            return $salesOrder;
        } 
        return $createCustomer;
      }
      return response()->json(["status"       => false,
                               "message"      => "Tidak ada Data yang dapat di inputkan ke jurnalID"
                              ],200);

    }

    public function CreateCustomer ($getDataTransaksi){
      //Tambahkan looping (mis:foreach) jika data lebih dari satu
      $dataRaw = [
                    "customer"  => ["first_name"   => $getDataTransaksi['nama_customer'].$getDataTransaksi['id_transaksi'], //nama lengkap dengan id_transaksi
                                    "display_name" => $getDataTransaksi['nama_customer'].$getDataTransaksi['id_transaksi'], //nama lengkap
                                    "address"      => $getDataTransaksi['alamat'],
                                    "phone"        => $getDataTransaksi['hp'],
                                    "mobile"       => $getDataTransaksi['hp'],
                                    "email"        => $getDataTransaksi['email'],
                                    "custom_id"    => $getDataTransaksi['id_transaksi'] //id_transaksi tidak boleh sama
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
                                        "apikey: 56593d3e45a37eb7033e356d33fd83c4",
                                        "Authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
                                        "Content-Type: application/json; charset=utf-8",
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action' => "CreateCustomer",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
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

      $agen      = '';
      if ($getDataTransaksi['id_agen'] != null) {
        $agen = CmsUser::where('id',$getDataTransaksi['id_agen'])->value('name');
      }
      $kantor    = Kantor::where('id',$getDataTransaksi['id_kantor'])->value('kantor');
      $countData = 1;
      $dataOrder = Pendapatan::where('id_order',$getDataTransaksi['id_transaksi'])->get();

      $detail_produk = [];
      foreach ($dataOrder as $key => $order) {

        $produk_harga        = Harga::where('id',$order['ra_produk_harga_id'])->value('jurnal_product_id');
        $produk              = ["quantity" => $order['quantity'], "rate"=> $order['harga'],"product_id"=> $produk_harga];
        array_push($detail_produk,$produk);
      }

      $dataRaw = [
                "sales_order"  => [ 
                                  "transaction_date"             => $getDataTransaksi['tgl'],
                                  "transaction_lines_attributes" => $detail_produk,
                                  "shipping_date"      => $getDataTransaksi['tgl_kirim'],
                                  "shipping_price"     => 0,
                                  "shipping_address"   => $getDataTransaksi['alamat'],
                                  "is_shipped"         => true,
                                  "address"            => $getDataTransaksi['alamat'],
                                  "due_date"           => $getDataTransaksi['tgl'],
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
                                        "apikey: 56593d3e45a37eb7033e356d33fd83c4",
                                        "Authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
                                        "Content-Type: application/json; charset=utf-8",
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action' => "SalesOrder",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'sales_order';
      $searchResponse = stripos($response, 'sales_order');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $response = array("status" =>true,
                                "id"     => $dataResponse->sales_order->id,
                                "message"=> $dataResponse->sales_order->transaction_lines_attributes);
          }
          else{

              $response = array("status"=>false,"message"=> "sales order".$response);
          }
      }
      
      return $response;
    }

    public function SalesOrdertoInvoice($getDataTransaksi,$sales_id,$sales_atribute){

      $detail_atribute = [];
      foreach ($sales_atribute as $key => $atribute) {
  
        $produk              = ["id" => $atribute->id, "quantity"=> $atribute->quantity];
        array_push($detail_atribute,$produk);
      }

      $dataRaw = [
                "sales_order"  => [ 
                                  "transaction_date"   => $getDataTransaksi['tgl'],
                                  "transaction_lines_attributes" => $detail_atribute
                                  ]
                  ];

      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/sales_orders/".$sales_id."/convert_to_invoice",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $encodedataRaw,
        CURLOPT_HTTPHEADER     => array(
                                        "apikey: 56593d3e45a37eb7033e356d33fd83c4",
                                        "Authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
                                        "Content-Type: application/json; charset=utf-8",
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p; nlbi_1892526=8trIdrnO9S4KHtCQKezQ4QAAAAC1Ln3MtHQzDOiZP5/QXp4v; incap_ses_959_1892526=3SwTKPUwzU0bAScrnA1PDc+i0V8AAAAA2wbKV6ShlqO9SQ9NTtMN7g=="
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action' => "SalesOrdertoInvoice",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'sales_invoice';
      $searchResponse = stripos($response, 'sales_invoice');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $response = array("status" => true,
                                "message"=> $dataResponse->sales_invoice->transaction_no);
          }
          else{

              $response = array("status"=>false,"message"=> "sales order".$response);
          }
      }

      return $response;
    }

    public function receivePayment($getDataTransaksi,$transaction_no){

      $paymentMethode =  Paymeth::where('id',$getDataTransaksi['id_payment_method'])->value('keterangan');
      if ($paymentMethode != 'cash') {

        $createExpenses      = $this->createExpenses($getDataTransaksi);

        $payment_method_name = "Transfer Bank";
        $payment_method_id   = 792898;
        $deposit_to_name     = "Mandiri Publik 131 000 711 2586";
      } 
      elseif ($paymentMethode == 'cash' && $getDataTransaksi['id_kantor'] == 6) {
        $payment_method_name = "Kas Tunai";
        $payment_method_id   = 20438423;
        $deposit_to_name     = "Kas Bandung";
      } else {
        $payment_method_name = "Kas Tunai";
        $payment_method_id   = 20438424;
        $deposit_to_name     = "Kas Cirebon";
      }
      
      $dataRaw = [
                "receive_payment"  => [ 
                                        "transaction_date"    => $getDataTransaksi['tgl'],
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
                                        "apikey: 56593d3e45a37eb7033e356d33fd83c4",
                                        "Authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
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
              $response = array("status" => true,
                                "id"     => $dataResponse->receive_payment->id,
                                "message"=> $dataResponse->receive_payment->transaction_no);
          }
          else{

              $response = array("status"=>false,"message"=> "sales order".$response);
          }
      }

      return $response;
    }

    private function createExpenses($getDataTransaksi){
  
      $dataRaw = [
                "expense"  => [ 
                                        "refund_from_name"   => "Mandiri Publik 131 000 711 2586",
                                        "person_name"        => "Nicepay",
                                        "transaction_date"   => $getDataTransaksi['tgl'],
                                        "payment_method_name"=> "Transfer Bank",
                                        "payment_method_id"  => 792898,
                                        "transaction_no"     => $getDataTransaksi['id_transaksi'],
                                        "custom_id"          => $getDataTransaksi['id_transaksi'],
                                        "expense_payable"    => false,
                                        "transaction_account_lines_attributes"  => [[ "account_name" => "Biaya lain-lain",
                                                                                      "description"  => "biaya nicepay 2011240027650",
                                                                                      "debit"        => 4400
                                                                                   ]],
                                      ]
                  ];


      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/expenses",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => "",
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $encodedataRaw,
        CURLOPT_HTTPHEADER     => array(
                                        "apikey: 56593d3e45a37eb7033e356d33fd83c4",
                                        "Authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
                                        "Content-Type: application/json; charset=utf-8",
                                        "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p; nlbi_1892526=8trIdrnO9S4KHtCQKezQ4QAAAAC1Ln3MtHQzDOiZP5/QXp4v; incap_ses_959_1892526=3SwTKPUwzU0bAScrnA1PDc+i0V8AAAAA2wbKV6ShlqO9SQ9NTtMN7g=="
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksi['id'],
                                        'id_transaksi' =>$getDataTransaksi['id_transaksi'],
                                        'action' => "createExpenses",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'expense';
      $searchResponse = stripos($response, 'expense');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $response = array("status" => true,
                                "id"     => $dataResponse->expense->id,
                                "message"=> $dataResponse->expense->transaction_no);
          }
          else{

              $response = array("status"=>false,"message"=> "sales order".$response);
          }
      }

      return $response;
    }

}

