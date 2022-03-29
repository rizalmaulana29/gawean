<?php

namespace App\Http\Controllers\Signed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Helpers\OTP;
use App\Helpers\JWT;
use App\CmsUser As User;
use App\Payment;
use App\Pencairan;
use App\PencairanDetail;
use App\Pendapatan;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index(Request $request)
    {   
        if (!$request->auth) {
            return response()->json(['status' => 'Unauthorized Access'],401);
        }

        #Total Fee Null
        $totalFeeUnprocessed = PencairanDetail::where("id_agen",$request->auth)
            ->where(function($q) {
                $q->where('status_pencairan', '=', '')
                ->orWhereNull('status_pencairan');
            })
            ->sum("nominal_fee");
        
        #Total Penjualan
        $totalPenjualan = PencairanDetail::where("id_agen",$request->auth)
            ->sum("nominal_total");
        
        #Total Belanja
        $totalBelanja = Pendapatan::where("id_agen",$request->auth)
            ->whereIn("id_produk_parent",[329,330,384])
            ->where("lunas", "y")
            ->sum("total_transaksi");
        
        #Total Reward
        $totalReward = Payment::where("id_agen",$request->auth)
            ->where("status", "paid")
            ->where("lunas", "y")
            ->where("tipe", "transaksi")
            ->count();

        #Total Fee diajukan
        $totalFeeDiajukan = Pencairan::where("id_agen", $request->auth)
            ->where("status_pencairan","diajukan")
            ->sum("total_pencairan");

        #Total Fee diproses
        $totalFeeDiproses = Pencairan::where("id_agen", $request->auth)
            ->where("status_pencairan","diproses")
            ->sum("total_pencairan");

        #Total Fee selesai
        $totalFeeSelesai = Pencairan::where("id_agen", $request->auth)
            ->where("status_pencairan","selesai")
            ->sum("total_pencairan");

        return response()->json([
            "status" => true,
            "data" => [
                "totalFeeNull"  => [
                    "Total Fee",
                    $totalFeeUnprocessed ? $totalFeeUnprocessed : 0
                ],
                "totalFeeDiajukan"  => [
                    "Total Fee Diajukan",
                    $totalFeeDiajukan ? $totalFeeDiajukan : 0
                ],
                "totalFeeDiproses"  => [
                    "Total Fee Diproses",
                    $totalFeeDiproses ? $totalFeeDiproses : 0,
                ],
                "totalFeeSelesai"  => [
                    "Total Fee Selesai",
                    $totalFeeSelesai ? $totalFeeSelesai : 0,
                ],
                "totalPenjualan"  => [
                    "Total Penjualan",
                    $totalPenjualan ? $totalPenjualan : 0,
                ],
                "totalBelanja"  => [
                    "Total Belanja",
                    $totalBelanja ? $totalBelanja : 0,
                ],
                "totalReward"  => [
                    "Total Reward",
                    $totalReward ? $totalReward : 0,
                ],
            ]
        ]);
    }

    public function sakBabyPass(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'invalidInput'],400);
        }

        if ($request->header("Authorization") != "xF8shfjsfo934j5o03d352jn8EReH23T") {
            return response()->json(['status' => 'Unauthorized Access'],401);
        }

        $donatur = User::where('email', $request->input('email'))
            ->where("status","Active")
            ->first();

        // not registed set default id_donatur and redirect to signup page.
        if (!$donatur) {
            return response()->json([
                'status' => false,
                'message' => "No User Found."
            ]);
        }

        return response()->json([
            'status' => 'success',
            'token' => JWT::Sign($donatur->id),
            'expired' => time() + 60 * 60 * 24 * 7
        ]);
    }

}
