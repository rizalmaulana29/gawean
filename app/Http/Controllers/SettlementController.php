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

class SettlementController extends Controller
{
    public function MIDDate(){

        $dataMID = AdminEntitas::select('merchant_id','passwd')->where('merchant_id','!=','')->get();

        foreach ($dataMID as $key => $mid) {
            $getSettlement = $this->settlementNP($mid['merchant_id'],$mid['passwd']);
        }
        $response = array("status"=>true,"message"=>$getSettlement);
        
        return $response;

    }

    private function settlementNP ($mid,$passwd){

        $date = Carbon::now()->format('Ymd');

        $Data = [
            "mid" => $mid,
            "passwd"=>$passwd,
            "settlmntdt"=> $date,
            "startno"=>"1",
            "endno"=>"100"
        ];

        $encodeData = json_encode($Data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://bo.nicepay.co.id/settlmntdt.do',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>$encodeData,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $searchResponse = stripos($response, 'DATA');

            if ($err) {
                $response = array("status"=>"failed","message"=>$err.$mid);
            } 
            else {
                if ($searchResponse == true){
                  $dataResponse = json_decode($response);
                  foreach ($dataResponse->DATA as $key => $data) {
                      $checkSettlement = Payment::where('id_transaksi',$data->ORDER_NO)->value('tgl_settlement');
                      if (!$checkSettlement || $checkSettlement == null || $checkSettlement == '') {
                          continue;
                      }else{
                          if ($checkSettlement == '0000-00-00') {
                              $tgl_settlement = date_format(date_create($data->SETTLMNT_DT),"Y-m-d");
                              $updateSettlement = Payment::where('id_transaksi',$data->ORDER_NO)->
                                                           update(['tgl_settlement' => $tgl_settlement]);
                          } else {
                              continue;
                          }
                      }
                  }
                  $response = array("status"=>true,"message"=> $dataResponse->DATA);
                }
                else{

                  $response = array("status"=>false,"message"=>$response, "data"=>$mid);
                }
            }
          
        return $response;
    }
}
