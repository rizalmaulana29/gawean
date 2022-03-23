<?php

namespace App\Http\Controllers\Signed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CmsUser As User;
use App\Pencairan;
use App\PencairanDetail;
use Carbon\Carbon;

class AffiliateController extends Controller
{
    public function index(Request $request)
    {   
        if (!$request->auth) {
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        #Saldo
        $saldo = PencairanDetail::where("id_agen",$request->auth)
            ->where(function($q) {
                $q->where('status_pencairan', '=', '')
                ->orWhereNull('status_pencairan');
            })
            ->sum("nominal_fee");

        #Progressed
        $progressed = Pencairan::where("id_agen", $request->auth)
            
            ->where(function($q) {
                $q->where("status_pencairan","diajukan")
                ->orWhere("status_pencairan","diproses");
            })
            ->sum("total_pencairan");

        #Withdrawed
        $withdrawed = Pencairan::where("id_agen", $request->auth)
            ->where("status_pencairan","selesai")
            ->sum("total_pencairan");

        return response()->json([
            "status" => true,
            "data" => [
                "saldo"  => $saldo ? $saldo : 0,
                "progressed"  => $progressed ? $progressed : 0,
                "withdrawed"  => $withdrawed ? $withdrawed : 0
            ]
        ],200);
    }
}
