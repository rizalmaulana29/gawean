<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

use Carbon\Carbon;


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

        $curl = curl_init();

        $data = array('payloads' => $payloads);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://backend.rumahaqiqah.co.id/download/qurban/report',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
    }

    public function redirectCertificate($payloads)
    {
        if (!$payloads) {
            return redirect()->away("https://www.rumahqurban.id/sorry");
        }
    }
}
