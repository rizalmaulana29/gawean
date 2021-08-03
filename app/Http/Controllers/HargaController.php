<?php

namespace App\Http\Controllers;
use App\Harga;
use App\Kantor;
use App\Produk;

class HargaController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function show ()
    {
        
        $result = Harga::get()->all();
        return response()->json($result);
    }

    //
}
