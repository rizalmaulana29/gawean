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

class JurnalController extends Controller
{
    public function Filtering(){
      $endDate = Carbon::now()->endOfMonth();
      $start = Carbon::now()->firstOfMonth()->toDateTimestring();

      $getDataTransaksi = Payment::where([["tgl_transaksi", ">=", $start],["tgl_transaksi", "<=", $endDate->toDateTimestring()]])
                                 ->where('lunas','y')
                                 ->where('custom_id',null)
                                 ->whereIn('id_kantor', [4, 5, 6, 17])
                                 ->where(function($q) {
                                            $q->where('sisa_pembayaran', '=', 0)
                                            ->orWhereNull('sisa_pembayaran');
                                        })
                                 ->where('tgl_kirim','<=',$endDate->toDateString())
                                 ->orderBy('tgl_transaksi','ASC')
                                 ->first();
                                 // ->limit(50)
                                 // ->get();
      $createCustomer = $this->CreateCustomer($getDataTransaksi);
 
      if ($createCustomer['status'] == true) {
        $salesOrder = $this->SalesOrder($getDataTransaksi,$createCustomer);
      } else {
        return $createCustomer;
      }

      return response()->json($getDataTransaksi);
    }

    public function CreateCustomer ($getDataTransaksi){

      // foreach ($getDataTransaksi as $key => $DataTransaksi) {
      //   # code...
      // }
      // dd($getDataTransaksi['id_transaksi']);

      $dataRaw = [
                    "customer"  => ["first_name"   => $getDataTransaksi['nama_customer'].$getDataTransaksi['id_transaksi'], //nama lengkap dengan id_transaksi
                                    "display_name" => $getDataTransaksi['nama_customer'], //nama lengkap
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
      $findString    = 'customer';
      $searchResponse = stripos($response, 'customer');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['custom_id' => $dataResponse->customer->id]);
              $response = array("status"=>true,"message"=> $dataResponse->customer->id);
          }
          else{

              $response = array("status"=>false,"message"=> $response);
          }
      }

      return $response;
    }

    public function SalesOrder($getDataTransaksi,$person_id){

      var_dump($getDataTransaksi);
      dd($person_id);
      $dataRaw = [
                "sales_order"  => [ 
                                  "transaction_date"   => "Savitri Wulan Agustin Test From API",
                                  "transaction_lines_attributes" => [["quantity"  => 1,"rate"      => 1505000,"product_id"=> "10543700"],
                                                                     ["quantity"  => 1,"rate"      => 1505000,"product_id"=> "10543700"]
                                                                    ],

                                  "shipping_date"      => "Savitri Wulan Agustin",
                                  "shipping_price"        => "081320314029",
                                  "shipping_address"       => "081320314029",
                                  "is_shipped"        => "Cappietori.86@gmail.com",
                                  "address"    => "2011240027653",
                                  "due_date"    => "2011240027653",
                                  "person_id"    => "2011240027653",
                                  "tags"    => ["2011240027653","test"],
                                  "email"    => "2011240027653",
                                  "transaction_no"    => "2011240027653",
                                  "custom_id"    => "2011240027653"
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
      var_dump($response);
      var_dump($err);

      die;
      curl_close($curl);
      
      if ($err) {
          $response = array("status"=>"- fail","message"=>$err);
      } 
      else {
          if ($response != "Bad Request"){
              $response = array("status"=>"- sending","message"=>"Sending Message Success");
          }
          else{
              $response = array("status"=>"- fail: email gagal terkirim !","message"=>"Bad Request");
          }
      }
    }

    public function SalesOrdertoInvoice(){

      $dataRaw = [
                "sales_order"  => [ 
                                  "transaction_date"   => "Savitri Wulan Agustin Test From API",
                                  "transaction_lines_attributes" => [["id" => "fromsalesorder","quantity"  => 1],
                                                                     ["id" => "fromsalesorder","quantity"  => 1]
                                                                    ]
                                  ]
                  ];

      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/sales_orders/201834363/convert_to_invoice",
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
      var_dump($response);
      var_dump($err);

      die;
      curl_close($curl);
      
      if ($err) {
          $response = array("status"=>"- fail","message"=>$err);
      } 
      else {
          if ($response != "Bad Request"){
              $response = array("status"=>"- sending","message"=>"Sending Message Success");
          }
          else{
              $response = array("status"=>"- fail: email gagal terkirim !","message"=>"Bad Request");
          }
      }
    }

    public function receivePayment(){

      $dataRaw = [
                "receive_payment"  => [ 
                                        "transaction_date"   => "Savitri Wulan Agustin Test From API",
                                        "records_attributes" => [["transaction_no" => "fromsalesorder",
                                                                  "amount"  => 1]],
                                        "custom_id"      => "Savitri Wulan Agustin",
                                        "payment_method_name"        => "Transfer Bank",
                                        "payment_method_id"       => 792898,
                                        "is_draft"        => false,
                                        "deposit_to_name"    => "Mandiri Publik 131 000 711 2586",
                                      ]
                  ];

      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/sales_orders/201834363/convert_to_invoice",
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
      var_dump($response);
      var_dump($err);

      die;
      curl_close($curl);
      
      if ($err) {
          $response = array("status"=>"- fail","message"=>$err);
      } 
      else {
          if ($response != "Bad Request"){
              $response = array("status"=>"- sending","message"=>"Sending Message Success");
          }
          else{
              $response = array("status"=>"- fail: email gagal terkirim !","message"=>"Bad Request");
          }
      }
    }

}

