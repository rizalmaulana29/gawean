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
    
    public function listReseller(Request $request)
    {
        if (!$request->auth) {
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }
        
        $checkMitra = User::where("id", $request->auth)
            ->where("status", "Active")
            ->value("id_cms_privileges");
        if($checkMitra != 17 ){
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        $reseller = User::select("created_at", "name")
            ->selectRaw("id AS agen")
            ->where("id_parent_agen", $request->auth)
            ->where("status", "Active")
            ->get();
        
        if($reseller->count() > 0){
            return response()->json([
                "status" => true,
                "reseller" => $reseller
            ],200);
        }
        return response()->json(['status' => false, "message" => 'No Reseller found'],404);

    }
}
