<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Order;
use App\Payment;
use App\Kontak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function cart(Request $request){

    // $order = Order::find($id_order);
    // dd($order);
    $req = $request->all();
    $now = Carbon::now()->addHour(7);
    // $id_T = $this->GenerateIDTransaksi($user->id_office);
    $total = 0;
    $id = [];
    $x = [];
    $n = 0;
    foreach ($req['id_produk'] as $key => $id_produk) {
      $produk = Produk::find($id_produk);
      $id[$n] = $produk->produk;
      $order = new Order;
      $order->id_order = date("ymd") . $request->input('id_kantor') . str_pad(1, 4, '0', STR_PAD_LEFT);
      $order->id_kantor = $request->input('id_kantor');
      $order->id_via_bayar = 1;
      $order->id_pelanggan = $order->id_order;
      $order->id_via_bayar = 1;
      $order->coa_debit = $request->input('coa_debit'); 
      $order->quantity = $req['qty'][$key];
      $order->harga = $req['harga'][$key];
      $total += $order->quantity * $order->harga;
      $order->tgl_transaksi = $now;
      $order->total_transaksi = $total;
      $order->id_payment_method = $request->input('id_payment');
      $order->lunas = 'y';
      $order->approve = 'y';
      $order->keterangan = $req['keterangan'][$key];
      $order->nik_input = $request->input('nik_input');
      $order->cur = "IDR";
      // dd($order);
      $order->save();
      $n++;
    }
    $x = $order->quantity;
    $count = count($x);

    $result[1] = Payment::create([
                                  'id_transaksi' => $order->id_order,
                                  'id_payment_method' => $request->input('id_payment'),
                                  'nominal' => $total,
                                  'coa_debit' => $request->input('coa_debit'),
                                  'status' => 0,
                                  'kode' => $request->input('kode'),
                                  'nominal_transfer' => $request->input('nominal_transfer')
                                ]);
    
    foreach ($req['status'] as $key => $status) {
      $result[2] = new Kontak;
      $result[2]->id_kontak = date("ymd") . 00 . $request->input('id_kantor') . str_pad(1, 4, '0', STR_PAD_LEFT);
      $result[2]->nama_kontak = $req['nama'][$key];
      $result[2]->tgl_lahir = $req['tgl_lahir'][$key];
      $result[2]->tempat_lahir = $req['tempat_lahir'][$key];
      $result[2]->alamat = $req['alamat'][$key];
      $result[2]->kota = $req['kota'][$key];
      $result[2]->kecamatan = $req['kecamatan'][$key];
      $result[2]->status = $status;
      $result[2]->id_order = $order->id_order;
      $result[2]->tgl_reg = $now;
      $result[2]->telepon = $request->input('telepon');
      $result[2]->hp = $request->input('hp');
      $result[2]->email = $request->input('email');
      $result[2]->jk = $request->input('jk');
      $result[2]->id_kantor = $request->input('id_kantor');
      $result[2]->save();
    }
    
    
    
    return response()->json(["status" => "success", "message" => $count, $result[1]->nominal],200);
  }

}
