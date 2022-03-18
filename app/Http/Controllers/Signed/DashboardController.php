<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Helpers\OTP;
use App\Helpers\JWT;
use App\Helpers\Tools;
use App\CmsUser As User;
use App\Pencairan;
use App\PencairanDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class DashboardController extends Controller
{
    public function index(Request $request)
    {   
        if (!$request->auth) {
            return response()->json(['status' => 'Unauthorized Access'],401);
        }

        #Total Fee Null
        $totalFeeUnprocessed = PencairanDetail::sum("nominal_fee")
            ->where("id_agen",$request->auth)
            ->where(function($q) {
                $q->where('status_pencairan', '=', '')
                ->orWhereNull('status_pencairan');
            });

        #Total Fee diajukan
        $totalFeeDiajukan = Pencairan::sum("total_pencairan")
            ->where("id_agen", $request->auth)
            ->where("status_pencairan","diajukan");

        #Total Fee diproses
        $totalFeeDiproses = Pencairan::sum("total_pencairan")
            ->where("id_agen", $request->auth)
            ->where("status_pencairan","diproses");

        #Total Fee selesai
        $totalFeeSelesai = Pencairan::sum("total_pencairan")
            ->where("id_agen", $request->auth)
            ->where("status_pencairan","selesai");
        
        // not registed set default id_user and redirect to signup page.

        return response()->json([
            "status" => true,
            "totalFeeNull"  => $totalFeeUnprocessed ? $totalFeeUnprocessed : 0,
            "totalFeeDiajukan"  => $totalFeeDiajukan ? $totalFeeDiajukan : 0,
            "totalFeeDiproses"  => $totalFeeDiproses ? $totalFeeDiproses : 0,
            "totalFeeSelesai"  => $totalFeeSelesai ? $totalFeeSelesai : 0,
        ]);
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'invalidInput'],400);
        }
        $donatur = User::where('email', $request->input('email'))
            ->where("status","Active")
            ->first();

        // not registed set default id_donatur and redirect to signup page.
        if (!$donatur) {
            return response()->json([
                'status' => 'notRegistered',
                'token' => JWT::Sign('99999999'),
                'expired' => time() + 60 * 60 * 24 * 7
            ]);
        }

        return response()->json([
            'status' => 'success',
            'token' => JWT::Sign($donatur->id),
            'expired' => time() + 60 * 60 * 24 * 7
        ]);
    }
    
    public function store(Request $request)
    {
    }

    public function show(Request $request)
    {
    }

    public function edit(Request $request)
    {
    }

    public function update(Request $request)
    {
    }
    
    public function destroy(Request $request)
    {
    }
}
