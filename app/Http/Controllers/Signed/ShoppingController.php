<?php

namespace App\Http\Controllers\Signed;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CmsUser as User;
use App\Order;
use App\Payment;
use App\Pencairan;
use App\PencairanDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class ShoppingController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->auth) {
            return response()->json(
                ['status' => false, 'message' => 'Unauthorized Access'],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'skip' => 'required',
            'take' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['status' => false, 'message' => 'invalidInput'],
                400
            );
        }

        # Param Pencarian
        $start =
            $request->input('start_date') ?? null
                ? $request->input('start_date')
                : null;
        $end =
            $request->input('end_date') ?? null
                ? $request->input('end_date')
                : null;
        $take = $request->input('take') ?? null ? $request->input('take') : 10;
        $skip = $request->input('skip') ?? null ? $request->input('skip') : 0;
        $varian =
            $request->input('varian') ?? null ? $request->input('varian') : 0;

        $order =
            $request->input('order') ?? null
                ? $request->input('order')
                : 'tgl_transaksi';
        $sort =
            $request->input('sort') ?? null ? $request->input('sort') : 'DESC';

        # shopping
        $shopping = Payment::select(
            'id_transaksi',
            'tgl_transaksi',
            'status',
            'nominal_total'
        )
            ->where('id_agen', $request->auth)
            // ->where("status", "paid")
            // ->where("lunas", "y")
            ->where('tipe', 'transaksi');

        if ($start && $end) {
            $shopping = $shopping
                ->where('tgl_transaksi', '>=', $start)
                ->where('tgl_transaksi', '<=', $end);
        }

        if ($varian) {
            $shopping = $shopping->where('varian', $varian);
        }

        $total = $shopping->count();

        $shopping = $shopping
            ->take($take)
            ->skip($skip)
            ->orderBy($order, $sort)
            ->get();

        if ($shopping->count() > 0) {
            return response()->json(
                [
                    'status' => true,
                    'data' => $shopping,
                    'total' => $total,
                ],
                200
            );
        }
        return response()->json(
            [
                'status' => false,
                'message' => 'No History Shopping',
            ],
            404
        );
    }

    public function detailShopping(Request $request)
    {
        if (!$request->auth) {
            return response()->json(
                ['status' => false, 'message' => 'Unauthorized Access'],
                401
            );
        }

        // request pencarian
        $id_transaksi = $request->input('id_transaksi');

        $shopping = Payment::select(
            'ra_payment_dua.nama_customer',
            'ra_payment_dua.id_transaksi',
            'ra_payment_dua.nominal_total',
            'ra_order_dua.harga',
            'ra_order_dua.quantity',
            'ra_order_dua.total_transaksi',
            'ra_produk_harga.nama_produk'
        )
            ->where('id_transaksi', $id_transaksi)

            ->leftjoin(
                'ra_order_dua',
                'ra_order_dua.id_order',
                '=',
                'ra_payment_dua.id_transaksi'
            )
            ->leftjoin(
                'ra_produk_harga',
                'ra_produk_harga.id',
                '=',
                'ra_order_dua.ra_produk_harga_id'
            )
            ->get();

        $total = $shopping->count();
        // var_dump($shopping, 'test');
        // exit();
        if ($shopping->count() > 0) {
            return response()->json(
                [
                    'status' => true,
                    'data' => $shopping,
                    'total' => $total,
                ],
                200
            );
        }
        return response()->json(
            [
                'status' => false,
                'message' => 'No History Shopping',
            ],
            404
        );
    }
}
