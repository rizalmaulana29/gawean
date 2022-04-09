<?php

namespace App\Http\Controllers\Signed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CmsUser as User;
use App\Kontak;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->auth) {
            return response()->json(['status' => false, "message" => 'Unauthorized Access'], 401);
        }

        $cu = "cms_users";
        $rk = "ra_kontak";
        #get User
        $user = User::select("$cu.name", "$cu.created_at", "$cu.label", "$cu.photo", "$cu.bank_pencairan", "$cu.norek_pencairan", "$cu.id_cms_privileges",
        "$rk.tgl_lahir","$rk.tempat_lahir","$rk.alamat","$rk.kota","$rk.kecamatan","$rk.jk","$rk.id_kantor")
            ->leftJoin("$rk", "$cu.id", "=", "$rk.id_agen")
            ->where("$cu.id", $request->auth)
            ->where("$cu.status", "Active")
            ->whereIn("$cu.id_cms_privileges", [4, 17])
            // ->whereIn("id_cms_privileges",[2,4,8,9,10,11,12,15,16,17])
            ->first();

        if (!$user) {
            return response()->json(["status" => false, "message" => "Unauthorized Access"], 401);
        }

        $user['tipe'] = $user["id_cms_privileges"];
        unset($user["id_cms_privileges"]);

        return response()->json([
            "status"    => true,
            "agen"      => $user
        ]);
    }

    public function referralLink(Request $request)
    {
        if (!$request->auth) {
            return response()->json(['status' => false, "message" => 'Unauthorized Access'], 401);
        }

        $linkRA = "https://order.rumahaqiqah.co.id/?source=";
        $linkRQ = "https://agrosurya.co.id/katalog/?source=";
        $linkSF = "https://order.rumahaqiqah.co.id/?source=";
        $signup = "https://beta.kawandagang.id/agen/signup?source=";
        return response()->json([
            "status" => true,
            "data"  => [
                "ra_link"  => $linkRA . $request->auth,
                "rq_link"  => $linkRQ . $request->auth,
                "sf_link"  => $linkSF . $request->auth,
                "signup_link"  => $signup . $request->auth,
            ]
        ]);
    }

    public function update(Request $request)
    {
        if (!$request->auth) {
            return response()->json(['status' => false, "message" => 'Unauthorized Access'], 401);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'bank_pencairan' => 'required',
            'norek_pencairan' => 'required',
            'hp' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(["status" => false, "message" => "invalidInput"], 400);
        }

        $user = User::where("id", $request->auth)->first();
        if (!$user) {
            return response()->json(["status" => false, "message" => "Who Are You?"], 400);
        }
        $kontak = Kontak::where("id_agen", $user->id)->first();
        if (!$kontak) {
            $kontak = Kontak::where("email", $user->email)->first();

            if (!$kontak) {
                return response()->json(["status" => false, "message" => "Tidak Ada Kontak Agen"], 400);
            }

            $kontak->update([
                "id_agen" => $user->id
            ]);
        }

        # Required
        $nama           = $request->input("nama") ?? null ? $request->input("nama") : "";
        $bank_pencairan = $request->input("bank_pencairan") ?? null ? $request->input("bank_pencairan") : "";
        $norek_pencairan = $request->input("norek_pencairan") ?? null ? $request->input("norek_pencairan") : "";
        $hp             = $request->input("hp") ?? null ? $request->input("hp") : "";

        # Optional
        $photo          = $request->input("photo") ?? null ? $request->input("photo") : "";

        $tgl_lahir      = $request->input("tgl_lahir") ?? null ? $request->input("tgl_lahir") : null;
        $tempat_lahir   = $request->input("tempat_lahir") ?? null ? $request->input("tempat_lahir") : null;
        $alamat         = $request->input("alamat") ?? null ? $request->input("alamat") : null;
        $kota           = $request->input("kota") ?? null ? $request->input("kota") : null;
        $kecamatan      = $request->input("kecamatan") ?? null ? $request->input("kecamatan") : null;
        $jk             = $request->input("jk") ?? null ? $request->input("jk") : null;
        $id_kantor      = $request->input("kecamatan") ?? null ? $request->input("kecamatan") : null;

        $listKontak = [
            "tgl_lahir" => ($tgl_lahir ? $tgl_lahir : null),
            "tempat_lahir" => ($tempat_lahir ? $tempat_lahir : null),
            "alamat" => ($alamat ? $alamat : null),
            "kota" => ($kota ? $kota : null),
            "kecamatan" => ($kecamatan ? $kecamatan : null),
            "jk" => ($jk ? $jk : null),
            "id_kantor" => ($id_kantor ? $id_kantor : null),
        ];
        $listKontak = array_filter($listKontak, 'strlen');

        $updateUser = $user->update([
            "name" => $nama,
            // "photo" => $photo,
            "bank_pencairan" => $bank_pencairan,
            "norek_pencairan" => $norek_pencairan,
            "hp" => $hp
        ]);

        $updateKontak = $kontak->update($listKontak);

        if ($updateUser && $updateKontak) {
            return response()->json([
                "status" => true,
                "message" => "Update Success"
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Failed Update"
            ]);
        }
    }
}
