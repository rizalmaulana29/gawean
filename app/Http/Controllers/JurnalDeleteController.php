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

class JurnalDeleteController extends Controller
{
    public function DeleteDataJurnal(Request $request){

      $DataTransaksi = Payment::where('id',$request['id'])->get();

      if (count($DataTransaksi) > 0) {
        foreach ($DataTransaksi as $key => $getDataTransaksi) {

          if ($getDataTransaksi['sales_invoice_id'] != '' || $getDataTransaksi['sales_invoice_id'] != null) {  //Sales Invoice
            $urldata  = "https://api.jurnal.id/core/api/v1/sales_invoices/".$getDataTransaksi['sales_invoice_id'];
            $salesInvoice = $this->CurlDelete($urldata);
            $salesInvoice = $salesInvoice['message'];

          }else{
            $salesInvoice = "Tidak ada data Sales Invoice yang di hapus";
          }
          if ($getDataTransaksi['sales_order_id'] != '' || $getDataTransaksi['sales_order_id'] != null) { //Sales order
            $urldata  = "https://api.jurnal.id/core/api/v1/sales_orders/".$getDataTransaksi['sales_order_id'];
            $salesOrder = $this->CurlDelete($urldata);
            $salesOrder = $salesOrder['message'];

          }else{
            $salesOrder = "Tidak ada data Sales Order yang di hapus";
          }
          if ($getDataTransaksi['exspense_id'] != '' || $getDataTransaksi['exspense_id'] != null) { //exspense
            $urldata  = "https://api.jurnal.id/core/api/v1/expenses/".$getDataTransaksi['exspense_id'];
            $expense = $this->CurlDelete($urldata);
            $expense = $expense['message'];

          } else{
            $expense = "Tidak ada data Expense yang di hapus";
          }
          if ($getDataTransaksi['apply_memo_id'] != '' || $getDataTransaksi['apply_memo_id'] != null) { //apply Credit memo
            $urldata  = "https://api.jurnal.id/core/api/v1/customer_apply_credit_memo?id=".$getDataTransaksi['apply_memo_id'];
            $applyMemo = $this->CurlDelete($urldata);
            $applyMemo = $applyMemo['message'];

          } else{
            $applyMemo = "Tidak ada data Apply Memo yang di hapus";
          }
          if ($getDataTransaksi['memo_id'] != '' || $getDataTransaksi['memo_id'] != null) { //Credit Memo
            $urldata  = "https://api.jurnal.id/core/api/v1/credit_memos?id=".$getDataTransaksi['memo_id'];
            $memo = $this->CurlDelete($urldata);
            $memo = $memo['message'];

          } else{
            $memo = "Tidak ada data Memo yang di hapus";
          }
          if ($getDataTransaksi['person_id'] != '' || $getDataTransaksi['person_id'] != null) { //Customer
            $urldata  = "https://api.jurnal.id/core/api/v1/customers/".$getDataTransaksi['person_id'];
            $customer = $this->CurlDelete($urldata);
            $customer = $customer['message'];
          }
            $response = array("status"=>true,"message"=> ["Invoice" =>$salesInvoice,"Order" =>$salesOrder,"Expense" =>$expense,"Apply Memo" =>$applyMemo,"Memo" =>$memo,"Customer" =>$customer]);
        }
        return response()->json($response,200);

      } else {
        return response()->json(["status"       => false,
                                 "message"      => "Tidak Ada Data Dengan ID Transaksi".' '.$request['id']
                                ],400);
      }
    }

    private function CurlDelete ($urldata){

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => $urldata,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "DELETE",
        CURLOPT_POSTFIELDS => "{}",
        CURLOPT_HTTPHEADER => array(
          "apikey: 56593d3e45a37eb7033e356d33fd83c4",
          "authorization: 815f1ce4f83e46a3a3f2b87ac79fc79c",
          "content-type: application/json"
        ),
      ));

      $response1 = curl_exec($curl);
      $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      $err = curl_error($curl);

      curl_close($curl);

      if ($err) {
        $response = array("status"=>false,"message"=>"cURL Error #:" . $err);
      } else {

        if ($httpcode != 204 || $httpcode != 201) {
          $response = array("status"=>false,"message"=> $response1);
        } else {
          $response = array("status"=>true,"message"=> "data berhasil di hapus");
        }

      }

      return $response;
    }

}

