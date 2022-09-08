<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Harga;
use App\Helpers\APILegacy;
use App\Kantor;
use App\Produk;
use App\Order;
use App\Payment;

use App\Nicepaylog;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
  public function __construct()
  {
    date_default_timezone_set("Asia/Jakarta");
  }

  public function detailTrx(Request $request)
  {
    if (!$request->auth) {
      return response()->json(['status' => false, "message" => 'Unauthorized Access as'], 401);
    }

    $validator = Validator::make($request->all(), [
      'id_order'      => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    $rpyd = "ra_payment_dua";
    $rbr = "ra_bank_rek";

    $trx = Payment::select("$rpyd.expired_at", "$rpyd.id_transaksi", "$rbr.keterangan", "$rbr.gambar")
      ->leftJoin("$rbr", "$rpyd.id_payment_method", "=", "$rbr.id")
      ->where("$rpyd.id_transaksi", $request->input("id_order"))
      ->first();
    if (!$trx) {
      return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    $no_bayar  = Nicepaylog::where("id_order", $request->input("id_order"))
      ->where("action", "Registration")
      ->value("virtual_account_no");

    if (!$no_bayar) {
      return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    return response()->json([
      "status" => true,
      "no_bayar" => $no_bayar,
      "id_transaksi" => $trx->id_transaksi,
      "expired_at" => $trx->expired_at,
      "keterangan" => $trx->keterangan,
      "gambar" => "https://backend.rumahaqiqah.co.id/" . $trx->gambar
    ], 200);
  }

  public function detailTrxPublic(Request $request)
  {
    if (!$request->header('Authorization')) {
      return response()->json(['status' => false, "message" => 'Unauthorized Access!'], 401);
    }

    if ($request->header('Authorization') != "HelloWorldN3v3rD13sDud3s") {
      return response()->json(['status' => false, "message" => 'Unauthorized Access'], 401);
    }

    $validator = Validator::make($request->all(), [
      'id_order'      => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(["status" => false, "message" => "invalidInput!"], 400);
    }

    $rpyd = "ra_payment_dua";
    $rbr = "ra_bank_rek";
    

    $legacy   = new APILegacy;
    $message  = urldecode($request->input("id_order"));
    $message = str_replace(" ","+",$message);
    $id_order = $legacy->DataDecrypt($message);
    // $id_order = $request->input("id_order");

    if(!$id_order){
      return response()->json(["status" => false, "message" => "invalidInput!!"], 400);
    }
    $trx = Payment::select("$rpyd.expired_at", "$rpyd.id_transaksi", "$rbr.keterangan", "$rbr.gambar", "$rpyd.nominal_total", "$rpyd.id_payment_method", "$rbr.id_payment_method AS paymentaja" )
      ->leftJoin("$rbr", "$rpyd.id_payment_method", "=", "$rbr.id")
      ->where("$rpyd.id_transaksi", $id_order)
      ->first();

    if (!$trx) {
      return response()->json(["status" => false, "message" => "No Data"], 404);
    }

    $no_bayar  = Nicepaylog::where("id_order", $id_order)
      ->where("action", "Registration")
      ->value("virtual_account_no");

    if (!$no_bayar) {
      return response()->json(["status" => false, "message" => "No Data!"], 404);
    }

    return response()->json([
      "status" => true,
      "no_bayar" => $no_bayar,
      "id_transaksi" => $trx->id_transaksi,
      "expired_at" => $trx->expired_at,
      "keterangan" => $trx->keterangan,
      "payment_method" => $trx->paymentaja,
      "nominal" => $trx->nominal_total,
      "gambar" => "https://backend.rumahaqiqah.co.id/" . $trx->gambar
    ], 200);

    #nominal dan payment
  }

  public function checkTrx(Request $request)
  {
    if (!$request->header("Authorization")) {
      return response()->json(['status' => false, "message" => 'Unauthorized Access!'], 401);
    }
    if ($request->header('Authorization') != "HelloWorldN3v3rD13sDud3s") {
      return response()->json(['status' => false, "message" => 'Unauthorized Access!'], 401);
    }

    $validator = Validator::make($request->all(), [
      'id_order'      => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    $legacy   = new APILegacy;
    $message  = urldecode($request->input("id_order"));
    $id_order = $legacy->DataDecrypt($message);
    // $id_order = $request->input("id_order");x`

    if(!$id_order){
      return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    $transaction = Payment::where('id_transaksi',$id_order)->value("status");

    if(!$transaction){
      return response()->json(["status" => false, "message" => "No Data"], 404);
    }

    $response = ["status" => false, "message" => "No Transaction"];
    $code_response = 404;   
    
    switch ($transaction) {
      case 'paid':
        $response = ["status" => true, "message" => "Paid"];
        $code_response = 200;
        break;
      case 'checkout':
        $response = ["status" => false, "message" => "Checkout"];
        $code_response = 400;
        break;
      
      default:
        $response = ["status" => false, "message" => "Checkout"];
        $code_response = 400;
        break;
    }

    return response()->json($response,$code_response);
  }

  public function testEncrypt($message){
    $legacy = new APILegacy;
    $response = $legacy->DataEncrypt($message);
    $response = urlencode($response);
    return response()->json(["status" => true, "message" => $response], 200);
  }
  
  public function testDecrypt($message){
    $message = urldecode($message);
    $legacy = new APILegacy;
    $response = $legacy->DataDecrypt($message);
    return response()->json(["status" => true, "message" => $response], 200);
  }
}
