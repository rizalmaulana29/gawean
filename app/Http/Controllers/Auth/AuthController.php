<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Helpers\OTP;
use App\Helpers\JWT;
use App\Helpers\Tools;
use App\CmsUser As User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'invalidInput'],400);
        }
        
        if ($request->header("Authorization") != "xF8shfjsfo934j5o03d352jn8EReH23T") {
            return response()->json(['status' => 'Unauthorized Access'],401);
        }

        $password = $request->input("password") ?? null ? $request->input("password") : null;
        
        
        $user = User::where('email', $request->input('email'))
            ->where("id_cms_privileges",4)
            // ->whereIn("id_cms_privileges",[2,4,8,9,10,11,12,15,16,17])
            ->where("status","Active")
            ->first();
        if(!$user){
            return response()->json(["status" => false, "message" => "No User Found"], 404);
        }
        
        $password   = Hash::check($password, $user->password);
        if(!$password){
            return response()->json(["status" => false, "message" => "No User Found"], 404);
        }
        // not registed set default id_user and redirect to signup page.

        return response()->json([
            'status' => true,
            'token' => JWT::Sign($user->id),
            'expired' => time() + 60 * 60 * 24 * 7
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
}
