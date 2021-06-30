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
use App\Expenses;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JurnalExspensesController extends Controller
{
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


    private function getExpenses($getDataTransaksi){
      
      $tgl = strtotime($getDataTransaksi['tgl_transaksi']);
      $tglTransaksi = date('Y-m-d',$tgl);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.jurnal.id/core/api/v1/expenses",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "{}",
        CURLOPT_HTTPHEADER => array(
                                    "apikey: ".$jurnalKoneksi['jurnal_key'],
                                    "Authorization: ".$jurnalKoneksi['jurnal_auth'],
                                    "content-type: application/json"
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
              $updatePayment = Payment::where('id_transaksi',$getDataTransaksi['id_transaksi'])->update(['expenses_id' => $dataResponse->expense->id]);
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

