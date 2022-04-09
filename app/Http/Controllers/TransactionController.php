<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Harga;
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
      'id_payment_method' => 'required'
    ]);

    if ($validator->fails()) {
        return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    $trx = Payment::select("expired_at","id_transaksi")
      ->where("id_transaksi", $request->input("id_order"))
      ->where("id_payment_method", $request->input("id_payment_method"))
      ->first();
    if(!$trx){ 
      return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    $no_bayar  = Nicepaylog::where("id_order", $request->input("id_order"))
      ->where("action", "Registration")
      ->value("virtual_account_no");

    if(!$no_bayar){ 
      return response()->json(["status" => false, "message" => "invalidInput"], 400);
    }

    return response()->json([
      "status" => true,
      "no_bayar" => $no_bayar,
      "id_transaksi" => $trx->id_transaksi,
      "expired_at" => $trx->expired_at
    ], 200);

  }

}
