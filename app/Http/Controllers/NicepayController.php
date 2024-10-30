<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;
use Illuminate\Pagination;
// use App\Transaksi;
// use App\TransThirdparty;
use App\NicepayLog;
use App\Http\Controllers\SendWAController;

use Carbon\Carbon;
// use Response;
// use View;
// use Auth;

#NICEPAY
use App\Thirdparty\Nicepay\Nicepay;

class NicepayController extends Controller
{
    public function __construct()
    {
        Nicepay::$isProduction = env('NICEPAY_IS_PRODUCTION', 'true');
        
        date_default_timezone_set("Asia/Jakarta");
    }

    public function success(Request $request)
    {   
        dd($request);
    }
    public function getSuccess(Request $request)
    {   
        return view('thankspage.pending');
    }
    public function successApi(Request $request)
    {   
        $data = $request->all();
        NicepayLog::create([
            'response' => json_encode($data,true)
        ]);

        $payloadText = json_encode($data,true);

        $requestData = $data['request'];
        $requestDataHead = $requestData['head'];
        $requestDataBody = $requestData['body'];
        
        if($requestDataHead['function'] == "dana.acquiring.order.finishNotify"){
            $this->handleFinishNotify($payloadText,$requestData,$requestDataHead,$requestDataBody);
        }
        elseif($requestDataHead['function'] == "dana.acquiring.order.paymentCodeNotify"){
            $this->handleVACodeNotify($payloadText,$requestData,$requestDataHead,$requestDataBody);
        }

    }
    function handleFinishNotify($payloadText,$requestData,$requestDataHead,$requestDataBody){
        $dana = new Nicepay;
        $notifPembayaran = new SendWAController();

        $acquirementId     = $requestDataBody['acquirementId'];
        $merchantTransId   = $requestDataBody['merchantTransId'];
        $acquirementStatus = $requestDataBody['acquirementStatus'];
        $orderAmount       = $requestDataBody['orderAmount']['value'];
        $finishedTime      = $requestDataBody['finishedTime'];
        $createdTime       = $requestDataBody['createdTime'];

        TransThirdparty::where('id_transaksi',$merchantTransId)->update(['requestOvo' => $payloadText]);
        if($acquirementStatus == 'SUCCESS'){
            Transaksi::where('id_transaksi',$merchantTransId)->update(['status' => 'paid']);
            $notifPembayaran->sendWhatsappManual($merchantTransId);
        }
        elseif($acquirementStatus == 'CLOSED'){
            Transaksi::where('id_transaksi',$merchantTransId)->update(['status' => 'pending']);
        }

        $verifyNicepaySignature = $dana->verifyPayloadSignature($payloadText, $dana->getNicepayPublicKey());
        if ($verifyNicepaySignature != true) {
            die('Signature invalid');
        }

        $responseData = [
            'head' => [
                'version'      => '2.0',
                'function'     => $requestDataHead['function'],
                'clientId'     => $dana->getClientID(),
                'clientSecret' => $dana->getClientSecret(),
                'respTime'     => $dana->getDateNow(),
                'reqMsgId'     => $requestDataHead['reqMsgId'],
            ],
            'body' => [
                'resultInfo' => [
                    'resultStatus' => 'S',
                    'resultCodeId' => '00000000',
                    'resultCode' => 'SUCCESS',
                    'resultMsg' => 'success'
                ]
            ]
        ];

        $response = $dana->composeNotifyResponse($responseData);
        header('Content-type: application/json');
        echo $response;
        die;
    }

    function handleVACodeNotify($payloadText,$requestData,$requestDataHead,$requestDataBody){
        $dana = new Nicepay;

        // This might be useful for you
        $merchantId      = $requestDataBody['merchantId'];
        $acquirementId   = $requestDataBody['acquirementId'];
        $merchantTransId = $requestDataBody['merchantTransId'];
        $payMethod       = $requestDataBody['payMethod'];
        $bankCode        = $requestDataBody['bankCode'];
        $bankName        = $requestDataBody['bankName'];
        $companyCode     = $requestDataBody['companyCode'];
        $paymentCode     = $requestDataBody['paymentCode'];
        $expiryTime      = $requestDataBody['expiryTime'];

        TransThirdparty::where('id_transaksi',$merchantTransId)->update(['requestOvoHeader' => $payloadText]);

        $verifyNicepaySignature = $dana->verifyPayloadSignature($payloadText, $dana->getNicepayPublicKey());
        if ($verifyNicepaySignature != true) {
            die('Signature invalid');
        }

        
        $responseData = [
            'head' => [
                'version'      => '2.0',
                'function'     => $requestDataHead['function'],
                'clientId'     => $dana->getClientID(),
                'clientSecret' => $dana->getClientSecret(),
                'respTime'     => $dana->getDateNow(),
                'reqMsgId'     => $requestDataHead['reqMsgId'],
            ],
            'body' => [
                'resultInfo' => [
                    'resultStatus' => 'S',
                    'resultCodeId' => '00000000',
                    'resultCode' => 'SUCCESS', // SUCCESS means, your backend acknowledge of receiving the message/notification.
                    'resultMsg' => 'success'
                ]
            ]
        ];

        $response = $dana->composeNotifyResponse($responseData);
        header('Content-type: application/json');
        echo $response;
        die;
    }
}
