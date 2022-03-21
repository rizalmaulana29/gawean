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

    public function payout(Request $request)
    {   
        if (!$request->auth) {
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        #Progressed
        $progressed = Pencairan::where("id_agen", $request->auth)
            
            ->where(function($q) {
                $q->where("status_pencairan","diajukan")
                ->orWhere("status_pencairan","diproses");
            })
            ->sum("total_pencairan");
        
        if($progressed > 0){
            return response()->json([
                "status" => false,
                "message" => "You still have Unfinished payout."
            ], 401);
        }

        #Saldo
        $saldo = PencairanDetail::where("id_agen",$request->auth)
            ->where(function($q) {
                $q->where('status_pencairan', '=', '')
                ->orWhereNull('status_pencairan');
            });

        $pencairan = $saldo->sum("nominal_fee");
        if($pencairan == 0){
            return response()->json([
                "status" => false,
                "message" => "You Have 0 Saldo."
            ], 404);
        }

        $now  = Carbon::now();

        $payout = new Pencairan;
        $payout->id_pencairan   = $now->format("ymdHi").rand(100, 999);
        $payout->id_agen        = $request->auth();
        $payout->total_pencairan    = $pencairan;
        $payout->tgl_pengajuan      = $now()->toDateTimeString();
        $payout->status_pencairan   = "diajukan";
        $payout->save();

        $saldo_diajukan = $saldo->update(["status_pencairan"=>"diajukan"]);

        if($saldo_diajukan && $payout){
            return response()->json([
                "status" => true,
                "message" => "Payout Has Been Requested"
            ],200);
        }
        else{
            return response()->json([
                "status" => false,
                "message" => "Some Update Fail"
            ],400);
        }
    }
}
