<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\CmsUser;
use App\Payment;
use App\PO;
use App\PO_detail;
use App\Pendapatan;
use App\JurnalLog;
use App\Anak;
use App\AdminEntitas;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JurnalPOController extends Controller
{
    public function FilteringPO(){
      $endDate = Carbon::now()->endOfMonth();
      $start = Carbon::today()->addHour(1)->toDatestring();

      $getDataTransaksiPO = PO::where([["tgl_po", ">=", $start],["tgl_po", "<=", $endDate->toDatestring()]])
                                 ->where('status','paid')
                                 ->where('purchase_order_id','')
                                 ->where('purchase_invoice_id','')
                                 ->where('purchase_payment_id','')
                                 ->whereIn('id_kantor', [6,17,2,3,7,8,16])
                                 ->orderBy('tgl_po','ASC')
                                 ->first();

      if (isset($getDataTransaksiPO)) {
        $checkVendorId = CmsUser::where('id',$getDataTransaksiPO['id_vendor'])->first();
        
        if ($checkVendorId['vendor_id'] != '' || $checkVendorId['vendor_id'] != null) {
          $vendor_id = $checkVendorId['vendor_id'];
        }else{
          $createVendor = $this->CreateVendor($checkVendorId);
          $vendor_id = $createVendor['message'];
        }
        $purchaseOrder = $this->PurchaseOrder($getDataTransaksiPO,$vendor_id);
          if ($purchaseOrder['status'] == true) {
            $purchaseOrdertoInvoice = $this->PurchaseOrdertoInvoice($getDataTransaksiPO,$purchaseOrder['id'],$purchaseOrder['message']);
              if ($purchaseOrdertoInvoice['status'] == true) {
                $purchasePayment = $this->PurchasePayment($getDataTransaksiPO,$purchaseOrdertoInvoice['message']);
                  if ($purchasePayment['status'] == true) {
                    return response()->json(["status"       => true,
                                             "message"      => "Data PO berhasil di inputkan ke JurnalID",
                                             "Data Request" => $getDataTransaksiPO,
                                             "Data Response"=> $purchasePayment['message']
                                            ],200);
                  }
                  return $purchasePayment;
              }
              return $purchaseOrdertoInvoice;
          }
          return $purchaseOrder;
      }
      return response()->json(["status"       => false,
                               "message"      => "Tidak ada Data PO yang dapat di inputkan ke jurnalID"
                              ],200);

    }

    public function CreateVendor ($checkVendorId){
      //Tambahkan looping (mis:foreach) jika data lebih dari satu
      $jurnalKoneksi = $this->Entitas($checkVendorId['id_kantor']);

      $dataRaw = [
                    "vendor"  => [  "first_name"   => $checkVendorId['name'].' '.$checkVendorId['id'],
                                    "display_name" => $checkVendorId['name'].' '.$checkVendorId['id'],
                                    "email"        => $checkVendorId['email'],
                                    "custom_id"    => $checkVendorId['id']
                                    ]
                  ];

      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/vendors",
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
                                        "Content-Type: application/json; charset=utf-8"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);

      $insertTolog = JurnalLog::insert([
                                        'action' => "CreateVendor",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'vendor';
      $searchResponse = stripos($response, 'vendor');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updateCmsUser = CmsUser::where('id',$checkVendorId['id'])->update(['vendor_id' => $dataResponse->vendor->id]);
              $response = array("status"=>true,"message"=> $dataResponse->vendor->id);
          }
          else{

              $response = array("status"=>false,"message"=> "create vendor".$response);
          }
      }
      
      return $response;
    }

    public function PurchaseOrder($getDataTransaksiPO,$vendor_id){ 

      $jurnalKoneksi = $this->Entitas($getDataTransaksiPO['id_kantor']);

      $detailDataPO = PO_detail::where('id_po_detail',$getDataTransaksiPO['id']);
      $id_transaksi = $detailDataPO->first();
      $namaCustomer = Payment::where('id',$id_transaksi->id_order)->value('nama_customer');
      $kantor       = Kantor::where('id',$getDataTransaksiPO['id_kantor'])->value('kantor');

      $tglTransaksi = Carbon::now()->toDatestring();

      if ($getDataTransaksiPO['static_data'] == 20) {
        $keterangan = "Pembelian Hewan";
      } else {
        $keterangan = "Biaya";
      }
      

      $dataOrderPo   = $detailDataPO->get();
      $detail_produk = [];
      foreach ($dataOrderPo as $key => $orderPO) {
        $get_produk   = Pendapatan::where('id',$orderPO['ra_produk_harga_po_id'])->value('ra_produk_harga_id');
        $produk_harga = Harga::where('id',$get_produk)->first();
        $produk       = ["quantity" => $orderPO['quantity'], "rate"=> $orderPO['hpp'],"product_id"=> $produk_harga['jurnal_product_id'],"description"=>$keterangan.' '.$orderPO['quantity'].' '.$produk_harga['nama_produk'].' '.'an. '.$namaCustomer];
        array_push($detail_produk,$produk);
      }

      $dataRaw = [
                "purchase_order"  => [ 
                                  "transaction_date"             => $tglTransaksi,
                                  "transaction_lines_attributes" => $detail_produk,
                                  "shipping_date"      => $getDataTransaksiPO['tgl_eksekusi'],
                                  "is_shipped"         => true,
                                  "due_date"           => $getDataTransaksiPO['tgl_eksekusi'],
                                  "person_id"          => $vendor_id,
                                  "tags"               => [$kantor],
                                  "email"              => $getDataTransaksiPO['email'],
                                  "transaction_no"     => $getDataTransaksiPO['id_po_trans'],
                                  "custom_id"          => $getDataTransaksiPO['id_po_trans']
                                  ]
                  ];
      
      $encodedataRaw = json_encode($dataRaw);
      var_dump($dataRaw);
      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/purchase_orders",
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
                                        "Content-Type: application/json; charset=utf-8"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksiPO['id'],
                                        'id_transaksi' =>$getDataTransaksiPO['id_po_trans'],
                                        'action' => "PurchaseOrder",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'purchase_order';
      $searchResponse = stripos($response, 'purchase_order');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePO = PO::where('id',$getDataTransaksiPO['id'])->update(['purchase_order_id' => $dataResponse->purchase_order->id]);
              $response = array("status" =>true,
                                "id"     => $dataResponse->purchase_order->id,
                                "message"=> $dataResponse->purchase_order->transaction_lines_attributes);
          }
          else{

              $response = array("status"=>false,"message"=> "purchase order".$response);
          }
      }
      
      return $response;
    }

    public function PurchaseOrdertoInvoice($getDataTransaksiPO,$purchase_order_id,$purchase_order_atribute){

      $jurnalKoneksi = $this->Entitas($getDataTransaksiPO['id_kantor']);

      $detail_atribute = [];
      foreach ($purchase_order_atribute as $key => $atribute) {
  
        $produk              = ["id" => $atribute->id, "quantity"=> $atribute->quantity];
        array_push($detail_atribute,$produk);
      }
      $tglTransaksi = Carbon::now()->toDatestring();

      $dataRaw = [
                "purchase_order"  => [ 
                                  "transaction_date"   => $tglTransaksi,
                                  "transaction_lines_attributes" => $detail_atribute
                                  ]
                  ];

      $encodedataRaw = json_encode($dataRaw);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL            => "https://api.jurnal.id/core/api/v1/purchase_orders/".$purchase_order_id."/convert_to_invoice",
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
                                        "Content-Type: application/json; charset=utf-8"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksiPO['id'],
                                        'id_transaksi' =>$getDataTransaksiPO['id_po_trans'],
                                        'action' => "PurchaseOrdertoInvoice",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'purchase_invoice';
      $searchResponse = stripos($response, 'purchase_invoice');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePO = PO::where('id',$getDataTransaksiPO['id'])->update(['purchase_invoice_id' => $dataResponse->purchase_invoice->id]);
              $response = array("status" => true,
                                "id"     => $dataResponse->purchase_invoice->id,
                                "message"=> $dataResponse->purchase_invoice->transaction_no);
          }
          else{

              $response = array("status"=>false,"message"=> "purchase invoice".$response);
          }
      }

      return $response;
    }

    public function PurchasePayment($getDataTransaksiPO,$transaction_no){

      $jurnalKoneksi = $this->Entitas($getDataTransaksiPO['id_kantor']);

      $ana = [6,17];
      $lma = [2,3,7,8,16];

      if (in_array($getDataTransaksiPO['id_kantor'], $ana)) {
        if ($getDataTransaksiPO['payment_method'] != 'Kas') {

          $payment_method_name = "Transfer Bank";
          $payment_method_id   = 792898;
          $deposit_to_name     = "Mandiri ANA Operasional 131-00-0732212-8"; 

        } 
        elseif ($getDataTransaksiPO['payment_method'] == 'Kas' && $getDataTransaksiPO['id_kantor'] == 6) {
          $payment_method_name = "Cash";
          $payment_method_id   = 984210;
          $deposit_to_name     = "Kas Bandung";
        } else {
          $payment_method_name = "Cash";
          $payment_method_id   = 984210;
          $deposit_to_name     = "Kas Cirebon";
        }
      }else{
        if ($getDataTransaksiPO['payment_method'] != 'Kas') {

          $payment_method_name = "Transfer Bank";
          $payment_method_id   = 1528391;
          $deposit_to_name     = "Mandiri LMA Pusat 1310016430102 Ops"; 

        } 
        elseif ($getDataTransaksiPO['payment_method'] == 'Kas' && $getDataTransaksiPO['id_kantor'] == 2) {
          $payment_method_name = "Kas Tunai";
          $payment_method_id   = 1528389;
          $deposit_to_name     = "Kas Tangerang";
        }
        elseif ($getDataTransaksiPO['payment_method'] == 'Kas' && $getDataTransaksiPO['id_kantor'] == 3){
          $payment_method_name = "Kas Tunai";
          $payment_method_id   = 1528389;
          $deposit_to_name     = "Kas Bogor";
        }
        elseif ($getDataTransaksiPO['payment_method'] == 'Kas' && $getDataTransaksiPO['id_kantor'] == 8){
          $payment_method_name = "Kas Tunai";
          $payment_method_id   = 1528389;
          $deposit_to_name     = "Kas Cilegon";
        }
        else{
          $payment_method_name = "Kas Tunai";
          $payment_method_id   = 1528389;
          $deposit_to_name     = "Kas Jakarta";
        }
      }

      $tglTransaksi = Carbon::now()->format('d/m/Y');

      $dataRaw = [
                "purchase_payment"  => [ 
                                        "transaction_date"    => $tglTransaksi,
                                        "records_attributes"  => [[ "transaction_no" => $transaction_no,
                                                                    "amount"         => $getDataTransaksiPO['total_po']]],
                                        "custom_id"           => $getDataTransaksiPO['id_po_trans'],
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
                                        "Content-Type: application/json; charset=utf-8"
                                      ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $insertTolog = JurnalLog::insert(['ra_payment_id' => $getDataTransaksiPO['id'],
                                        'id_transaksi' =>$getDataTransaksiPO['id_transaksi'],
                                        'action' => "purchasePayment",
                                        'insert_at' => Carbon::now()->format('Y-m-d H:i:s'),
                                        'request_body' => $encodedataRaw,
                                        'response_body' => $response
                                        ]);

      $findString    = 'purchase_payment';
      $searchResponse = stripos($response, 'purchase_payment');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = json_decode($response);
              $updatePO = PO::where('id',$getDataTransaksiPO['id'])->update(['purchase_payment_id' => $dataResponse->purchase_payment->id]);
              $response = array("status" => true,
                                "id"     => $dataResponse->purchase_payment->id,
                                "message"=> $dataResponse->purchase_payment->transaction_no);
          }
          else{

              $response = array("status"=>false,"message"=> "purchase payment".$response);
          }
      }

      return $response;
    }

    private function Entitas($id_kantor){
      
      $ana = [6,17];
      $lma = [2,3,7,8,16];

      if (in_array($id_kantor, $ana)) {
        $id_entitas = 'ANA';
      }
      elseif (in_array($id_kantor, $lma)) {
         $id_entitas = 'LMA';
      } else {
        echo "id_kantor tidak memiliki koneksi jurnal";
        die;
      }
      
      $getDataKoneksi = AdminEntitas::where('id_entitas',$id_entitas)->first();

      return $getDataKoneksi;
    }

}

