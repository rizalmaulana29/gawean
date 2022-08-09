<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

use GuzzleHttp\Client;
use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class QurbanController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set("Asia/Jakarta");
    }

    public function redirectReport($payloads)
    {
        if (!$payloads) {
            return redirect()->away("https://www.rumahqurban.id/sorry");
        }

        try {
            $client = new Client(array(
                'cookies' => true
            ));

            $result = $client->request('POST', 'https://backend.rumahaqiqah.co.id/download/qurban/report', [
                'verify' => false,
                'form_params' => [
                    'payloads' => $payloads,
                ]
            ]);

            $filename = rand(0,99);
            $filename = 'temp' . $filename . '.pdf';

            $headers = ['Content-Type' =>'application/pdf'];
            Storage::put($filename, $result->getBody());
            $response = new BinaryFileResponse(storage_path('app/'.$filename), 200 , $headers);
            return $response;
        } catch (ClientException $ex) {
            // $ex->getMessage();
            if($ex->getCode()){
                return redirect("https://www.rumahqurban.id/sorry");
            }
        } catch (ServerException $ex) {
            if($ex->getMessage()){
                return redirect("https://www.rumahqurban.id/sorry");
            }
        }    
    }

    public function redirectCertificate($payloads)
    {
        if (!$payloads) {
            return redirect()->away("https://www.rumahqurban.id/sorry");
        }

        try {
            $client = new Client(array(
                'cookies' => true
            ));

            $result = $client->request('POST', 'https://backend.rumahaqiqah.co.id/download/qurban/report', [
                'verify' => false,
                'form_params' => [
                    'payloads' => $payloads,
                    'tipe_notif' => "certificate",
                ]
            ]);

            $filename = rand(0,99);
            $filename = 'temp' . $filename . '.pdf';

            $headers = ['Content-Type' =>'application/pdf'];
            Storage::put($filename, $result->getBody());
            $response = new BinaryFileResponse(storage_path('app/'.$filename), 200 , $headers);
            return $response;
        } catch (ClientException $ex) {
            // $ex->getMessage();
            if($ex->getCode()){
                return redirect("https://www.rumahqurban.id/sorry");
            }
        } catch (ServerException $ex) {
            if($ex->getMessage()){
                return redirect("https://www.rumahqurban.id/sorry");
            }
        }
    }

    public function redirectReportDakta($payloads)
    {
        if (!$payloads) {
            return redirect()->away("https://www.rumahqurban.id/sorry");
        }

        try {
            $client = new Client(array(
                'cookies' => true
            ));

            $result = $client->request('POST', 'https://backend.rumahaqiqah.co.id/download/qurban/report', [
                'verify' => false,
                'form_params' => [
                    'payloads' => $payloads,
                    'tipe_notif' => "reportdakta",
                ]
            ]);

            $filename = rand(0,99);
            $filename = 'temp' . $filename . '.pdf';

            $headers = ['Content-Type' =>'application/pdf'];
            Storage::put($filename, $result->getBody());
            $response = new BinaryFileResponse(storage_path('app/'.$filename), 200 , $headers);
            return $response;
        } catch (ClientException $ex) {
            // $ex->getMessage();
            if($ex->getCode()){
                return redirect("https://www.rumahqurban.id/sorry");
            }
        } catch (ServerException $ex) {
            if($ex->getMessage()){
                return redirect("https://www.rumahqurban.id/sorry");
            }
        }
    }
}
