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
    // public function Filtering(){
    //   $getDataTransaksi = Payment::where()
    // }
    public function CreateCustomer (){

      $dataRaw = [
                    "customer"  => ["first_name"   => "Savitri Wulan Agustin Test From API", //nama lengkap dengan id_transaksi
                                    "display_name" => "Savitri Wulan Agustin Test From API", //nama lengkap
                                    "address"      => "Savitri Wulan Agustin",
                                    "phone"        => "081320314029",
                                    "mobile"       => "081320314029",
                                    "email"        => "Cappietori.86@gmail.com",
                                    "custom_id"    => "2011240027650" //id_transaksi
                                    ]
                  ];

      // $headers = array(
      //               "apikey: 56593d3e45a37eb7033e356d33fd83c4",
      //               "Authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
      //               "Content-Type: application/json; charset=utf-8",
      //               "Cookie: visid_incap_1892526=sSSXIkPcR2OGEG8EIsR1kvKfq18AAAAAQUIPAAAAAAAbLIHIENx0sm8jw/V3q49p"
      //           );

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
        CURLOPT_POSTFIELDS     =>$dataRaw,
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

    // dd($response);

}