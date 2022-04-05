<?php

namespace App\Http\Controllers\Signed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CmsUser As User;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->auth){
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        #get User
        $user = User::select("name", "created_at", "label", "photo", "bank_pencairan", "norek_pencairan", "id_cms_privileges")
            ->where("id",$request->auth)
            ->where("status","Active")
            ->whereIn("id_cms_privileges",[4,17])
            // ->whereIn("id_cms_privileges",[2,4,8,9,10,11,12,15,16,17])
            ->first();

        if(!$user){
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        $user['tipe'] = $user["id_cms_privileges"] == 4 ? "Agen" : "Mitra";
        unset($user["id_cms_privileges"]);
        
        return response()->json([
            "status"    => true,
            "agen"      => $user
        ]);
    }

    public function referralLink(Request $request){
        if (!$request->auth){
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        $linkRA = "https://order.rumahaqiqah.co.id/?source=";
        $linkRQ = "https://agrosurya.co.id/katalog/?source=";
        $linkSF = "https://order.rumahaqiqah.co.id/?source=";
        $signup = "https://beta.kawandagang.id/agen/signup?source=";
        return response()->json([
            "status" => true,
            "data"  =>[
                "ra_link"  => $linkRA.$request->auth,
                "rq_link"  => $linkRQ.$request->auth,
                "sf_link"  => $linkSF.$request->auth,
                "signup_link"  => $signup.$request->auth,
            ]
        ]);
    }

}
