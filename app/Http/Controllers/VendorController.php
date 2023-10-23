<?php
namespace App\Http\Controllers;

use App\CmsUser;
use App\Kantor;
use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Request;

class VendorController extends Controller{
    public function vendor(Request $request){
        $limit = $request->input('limit',20);
        $offset =  $request->input('offset',0);
        $id_kantor = $request->input('id_kantor');

        $queryVendor = CmsUser::selectRaw("cms_users.id, cms_users.name, cms_users.email, cms_users.hp, cms_users.status, cms_users.id_kantor, ra_kantor.kantor, cms_users.vendor_id, cms_users.id_cms_privileges")
        ->leftJoin('ra_kantor','cms_users.id_kantor','=','ra_kantor.id')
        ->where(function($query){
            $query->where('id_cms_privileges', 5)
                ->orWhere('id_cms_privileges',6);
        })
        ->where('status', 'Active')
        ->limit($limit)
        ->offset($offset)
        ->get();
        // dd($queryVendor);

        $formattedVendors = $queryVendor->map(function ($vendor) {
            return [
                'id' => $vendor->id,
                'display_name' => $vendor->name,
                'jenis_vendor' => $vendor->id_cms_privileges,
                'address' => null,
                'email' => $vendor->email,
                'mobile' => null,
                'phone' => $vendor->hp,
                'status' => $vendor->status,
                'id_kantor' => $vendor->id_kantor,
                'kantor' => $vendor->kantor,
                'vendor_jurnal_id' => $vendor->vendor_id,
            ];
        });

        $response = [
            'list_vendor' => $formattedVendors,
        ];

        return response()->json($response);

    }
}

