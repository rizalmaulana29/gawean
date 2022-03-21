<?php

namespace App\Http\Controllers\Signed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Helpers\OTP;
use App\Helpers\JWT;
use App\CmsUser As User;
use App\Pencairan;
use App\PencairanDetail;
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
                    "title"     => "Total Fee",
                    "nominal"   => $totalFeeUnprocessed ? $totalFeeUnprocessed : 0
                ],
                "totalFeeDiajukan"  => [
                    "title"     => "Total Fee Diajukan",
                    "nominal"   => $totalFeeDiajukan ? $totalFeeDiajukan : 0
                ],
                "totalFeeDiproses"  => [
                    "title"     => "Total Fee Diproses",
                    "nominal"   => $totalFeeDiproses ? $totalFeeDiproses : 0,
                ],
                "totalFeeSelesai"  => [
                    "title"     => "Total Fee Selesai",
                    "nominal"   => $totalFeeSelesai ? $totalFeeSelesai : 0,
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
