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
    public function settlementNP (){


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
          CURLOPT_POSTFIELDS =>'{
            "mid": "RMHAQIQAH2",
            "passwd": "bUP7eVTVXlkUcugu6sv38Wsrs006x4GRoiS7GA4+0vRyTac7Ad8GgdDpvij9WqHuPlFWy4MCMKr2dY8n9Qr6eQ==",
            "settlmntdt": "20210113",
            "startno": "1",
            "endno": "50"
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $searchResponse = stripos($response, 'DATA');

            if ($err) {
                $response = array("status"=>"failed","message"=>$err);
            } 
            else {
                if ($searchResponse == true){
                  $dataResponse = json_decode($response);
                  // $updateCmsUser = CmsUser::where('id',$checkVendorId['id'])->update(['vendor_id' => $dataResponse->vendor->id]);
                  $response = array("status"=>true,"message"=> $dataResponse->DATA);
                }
                else{

                  $response = array("status"=>false,"message"=> "create vendor".$response);
                }
            }
          
        return $response;
    }
}
