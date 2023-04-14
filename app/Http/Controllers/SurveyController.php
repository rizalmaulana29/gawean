<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Harga;
use App\Helpers\APILegacy;
use App\Kantor;
use App\Produk;
use App\Order;
use App\Payment;
use App\Survey;
use App\SurveyMaster;
use Exception;
use Illuminate\Support\Facades\Validator;

class SurveyController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index()
    {
        $data_survey = SurveyMaster::where('active', 'y')->get();
        if ($data_survey->count() > 0) {
            $response = ['status' => true, 'data' => $data_survey];
            return response()->json($response, 200);
        }
        return response()->json(
            ['status' => false, 'message' => 'No Data'],
            200
        );
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_order' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['status' => false, 'message' => 'invalidInput!'],
                200
            );
        }

        $check_id_order = $this->check_id_order($request->input('id_order'));
        if (!$check_id_order['status']) {
            return response()->json(
                ['status' => false, 'message' => $check_id_order['message']],
                200
            );
        }

        try {
            $create_survey = Survey::create([
                'id_order' => $request->input('id_order'),
                'rating_layanan' => $request->input('rating_layanan'),
                'rating_app' => $request->input('rating_app'),
                'saran' => $request->input('saran'),
                'opsi_penilaian' => $request->input('opsi_penilaian'),
            ]);
        } catch (Exception $e) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $e->getMessage(),
                ],
                200
            );
        }

        if (!$create_survey) {
            response()->json(
                [
                    'status' => false,
                    'keterangan' => 'Failed to Create Survey',
                ],
                200
            );
        }

        return response()->json(
            [
                'status' => true,
                'keterangan' => 'Success Create Survey',
            ],
            200
        );
    }

    public function checkStatusSurvey(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_order' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(
                ['status' => false, 'message' => 'invalidInput!'],
                200
            );
        }

        $check_id_order = $this->check_id_order($request->input('id_order'));
        if (!$check_id_order['status']) {
            return response()->json(
                ['status' => false, 'message' => $check_id_order['message']],
                200
            );
        }
        return response()->json(
            [
                'status' => true,
                'keterangan' => 'You can create new Survey for this Order',
            ],
            200
        );
    }

    private function check_id_order($id_order)
    {
        if (!$id_order) {
            return ['status' => false, 'message' => 'No Id Order Found. P'];
        }

        $payment = Payment::where('id_transaksi', $id_order)->first();
        if (!$payment) {
            return ['status' => false, 'message' => 'No id Order Found. P'];
        }

        $survey = Survey::where('id_order', $id_order)->first();
        if (!$survey) {
            return [
                'status' => true,
                'message' => 'No id Order Found, so it safe',
            ];
        }
        return ['status' => false, 'message' => 'id Order Found'];
    }
}
