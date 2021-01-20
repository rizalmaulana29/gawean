<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Harga;
use App\Kantor;
use App\Produk;
use App\Payment;
use App\Order;
use App\StockTool;

class ToolsController extends Controller
{
    
    public function toolsMath (Request $request) //$id_kantor,$id_transaksi
    {
        
        $getIdProduk = Order::where('id_order',$request['id_transaksi'])->where('id_produk_parent','22')->value('ra_produk_harga_id');
        $qty = Order::where('id_order',$request['id_transaksi'])->where('id_produk_parent','22')->value('quantity');
        $getNamaProduk = Harga::where('id',$getIdProduk)->value('nama_produk');

        if ($getNamaProduk == 'Ordinary') {

            $qtyBento = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Bento')->value('jumlah_stock');
            $updateBento = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Bento')->update(['jumlah_out' => $qty,'jumlah_stock' => $qtyBento - $qty]);
            $getQtyTool = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Perlengkapan')->get();
            foreach ($getQtyTool as $key => $dataTool) {
                $updateStock = StockTool::where('id_kantor',$request['id_kantor'])->where('keterangan','Perlengkapan')->update(['jumlah_out' => $qty,'jumlah_stock' => $dataTool['jumlah_stock'] - $qty]);
            }
            if ($updateBento) {
                $response = "Data Bento berhasil di update";
            } else {
                $response = "Data Bento gagal di update";
            } 
            
        } else {
            $getQtyTool = StockTool::where('id_kantor',$request['id_kantor'])->whereIn('keterangan', ['Perlengkapan', 'Box'])->get();
            foreach ($getQtyTool as $key => $dataTool) {
                $updateStock = StockTool::where('id_kantor',$request['id_kantor'])->whereIn('keterangan', ['Perlengkapan', 'Box'])->update(['jumlah_out' => $qty,'jumlah_stock' => $dataTool['jumlah_stock'] - $qty]);
            }
            if ($updateBento && $updateStock) {
                $response = "Data Box berhasil di update";
            } else {
                $response = "Data Box gagal di update";
            } 
        }

        return response()->json(["status"       => true,
                                     "Data Response"=> $response
                                    ],200);
        
    }

}
