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
        $user = User::select("name", "created_at", "label", "photo", "bank_pencairan", "norek_pencairan")
            ->where("id",$request->auth)
            ->where("status","Active")
            ->whereIn("id_cms_privileges",[2,4,8,9,10,11,12,15,16,17])
            ->first();

        if(!$user){
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        return response()->json([
            "status"    => true,
            "agen"      => $user
        ]);
    }

    public function referralLink(Request $request){
        if (!$request->auth){
            return response()->json(['status' => false, "message" => 'Unauthorized Access'],401);
        }

        $linkRA = "https://www.rumahaqiqah.com";
        $linkRQ = "https://www.rumahqurban.com";
        $linkSF = "https://www.sanusafood.com";
        return response()->json([
            "status" => true,
            "data"  =>[
                "ra_link"  => $linkRA."/shop?ref=".$request->auth,
                "rq_link"  => $linkRQ."/shop?ref=".$request->auth,
                "sf_link"  => $linkSF."/shop?ref=".$request->auth,
            ]
        ]);
    }

}
