<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// use App\Helpers\OTP;
use App\Helpers\JWT;
use App\Helpers\Tools;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'otp' => 'required',
            'uuid' => 'required'

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'invalidInput'],400);
        }

        // if (!OTP::Verify($request->input('uuid'), $request->input('otp').":".$request->input('email'))) {
        //     return response()->json([
        //         'status' => 'wrongOTPCode'
        //     ], 400);
        // }
        $password = $request->input("password") ?? null ? $request->input("password") : null;
        
        
        $user = User::where('email', $request->input('email'))
            ->where("password",$password)
            ->whereIn("id_cms_privileges",[2,4,8,9,10,11,12,15,16,17])
            ->where("status","Active")
            ->first();

        if(!$user){
            return response()->json(["status" => false, "message" => "No user Found"], 404);
        }

        $password1  = Hash::make($password);
        $password   = Hash::check($password, $user->password);
        var_dump($password1);
        dd($password);
        // not registed set default id_user and redirect to signup page.
        if (!$user) {
            return response()->json([
                'status' => 'notRegistered',
                'token' => JWT::Sign('99999999'),
                'expired' => time() + 60 * 60 * 24 * 7
            ]);
        }

        return response()->json([
            'status' => 'success',
            'token' => JWT::Sign($user->id_donatur),
            'expired' => time() + 60 * 60 * 24 * 7
        ]);
    }

    public function SSOauthenticate(Request $request)
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
            'token' => JWT::Sign($donatur->id_donatur),
            'expired' => time() + 60 * 60 * 24 * 7
        ]);
    }
}
