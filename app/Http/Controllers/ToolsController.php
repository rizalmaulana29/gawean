<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Payment;
use App\Order;
use Carbon\Carbon;
use App\StockToolHistory;
use App\StockTool;

class ToolsController extends Controller
{
    
    public function toolsMath (Request $request) //$id_kantor,$id_transaksi
    {
        
        $getIdProduk = Order::where('id_order',$request['id_transaksi'])->where('id_produk_parent','22')->value('ra_produk_harga_id');
        $qty = Order::where('id_order',$request['id_transaksi'])->where('id_produk_parent','22')->value('quantity');
        $getNamaProduk = Harga::where('id',$getIdProduk)->value('nama_produk');

        var_dump($getNamaProduk);

        if ($getNamaProduk == 'Ordinary') {

            $tools    = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Bento')->first();
            $qtyBento = $tools->jumlah_stock;
            $history  = $this->history($request['id_kantor'],$request['id_transaksi'],$qty,$tools->id);
            $updateBento = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Bento')->update(['jumlah_stock' => $qtyBento - $qty]);

            $getQtyTool = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Perlengkapan')->get();
            foreach ($getQtyTool as $key => $dataTool) {
                $id_tool = $dataTool['id'];
                $history = $this->history($request['id_kantor'],$request['id_transaksi'],$qty,$id_tool);
                $updateStock = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Perlengkapan')->update(['jumlah_stock' => $dataTool['jumlah_stock'] - $qty]);
            }
            if ($updateBento) {
                $response = "Data Bento berhasil di update";
            } else {
                $response = "Data Bento gagal di update";
            } 
            
        } else {
            $getQtyTool = StockTool::where('id_kantor',$request['id_kantor'])->whereIn('keterangan', ['Perlengkapan', 'Box'])->get();
            foreach ($getQtyTool as $key => $dataTool) {
                $id_tool = $dataTool['id'];
                $history = $this->history($request['id_kantor'],$request['id_transaksi'],$qty,$id_tool);
                $updateStock = StockTool::where('id_kantor',$request['id_kantor'])->whereIn('keterangan', ['Perlengkapan', 'Box'])->update(['jumlah_stock' => $dataTool['jumlah_stock'] - $qty]);
            }
            if ($updateStock) {
                $response = "Data Box berhasil di update";
            } else {
                $response = "Data Box gagal di update";
            } 
        }

        return response()->json(["status"       => true,
                                 "Data Response"=> $response
                                ],200);
        
    }

    private function history($id_kantor,$id_transaksi,$qty,$id_tool){

        $historystok                   = new StockToolHistory;
        $hystorystok->id_tools         = $id_tool;
        $hystorystok->id_kantor        = $id_kantor;
        $hystorystok->id_produk_parent = 22;
        $hystorystok->jumlah_out       = $qty;
        $hystorystok->tgl              = Carbon::now()->format('Y-m-d');
        $hystorystok->keterangan       = "Pengurangan dari Transaksi ".$id_transaksi;
        $hystorystok->dtu              = Carbon::now();
        $hystorystok->save();
    }

}
