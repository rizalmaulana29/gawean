<?php

namespace App\Thirdparty\Nicepay;
// use App\Exceptions\NicepayException as Exception;

use DateTime;
use DateInterval;

Class Nicepay
{
	// public static $serverKey;

    public static $isProduction;

  	public static $curlOptions = array();	

  	const SANDBOX_API_URL      = 'https://dev.nicepay.co.id/';
    const PRODUCTION_API_URL   = 'https://api.nicepay.co.id/';

    const NICEPAY_DATE_FORMAT      = 'Y-m-d H:i:s';
    
    // const NICEPAY_IMID          = "IONPAYTEST";
    // const NICEPAY_MERCHANT_KEY  = "33F49GnCMS1mFYlGXisbUDzVf2ATWCl9k3R++d5hDd3Frmuos/XLx8XhXpe+LDYAbpGKZYSwtlyyLOtS/8aD7A==";
    const NICEPAY_CALLBACK_URL  = "http://localhost/nicepay-sdk/result.html";
    const NICEPAY_DBPROCESS_URL_SAND = "http://ptsv2.com/t/kwurx-1575983336/post";   
    const NICEPAY_DBPROCESS_URL_PROD = "";   
    

    public function config($params)
    {
        // Nicepay::$serverKey = $params['server_key'];
        Nicepay::$isProduction = $params['production'];
    }

    /**
    * @return string Veritrans API URL, depends on $isProduction
    */

  	public static function getBaseUrlAPI()
  	{
    	return Nicepay::$isProduction ? Nicepay::PRODUCTION_API_URL : Nicepay::SANDBOX_API_URL;
    }
    public static function getUrlNotif()
    {
        return Nicepay::$isProduction ? Nicepay::NICEPAY_DBPROCESS_URL_PROD : Nicepay::NICEPAY_DBPROCESS_URL_SAND;
    }
    public static function getMerchantID()
    {
        return Nicepay::$isProduction ? env('NICEPAY_MERCHAT_ID_PROD') : env('NICEPAY_MERCHAT_ID_SAND');
    }
    public static function getMerchantKey()
    {
        return Nicepay::$isProduction ? env('NICEPAY_MERCHAT_KEY_PROD') : env('NICEPAY_MERCHAT_KEY_SAND');
    }
    // public static function getClientID()
  	// {
    // 	return Nicepay::$isProduction ? env('NICEPAY_CLIENT_ID_PROD') : env('NICEPAY_CLIENT_ID_SAND');
    // }

    // public static function getClientSecret()
  	// {
    // 	return Nicepay::$isProduction ? env('NICEPAY_CLIENT_SECRET_PROD') : env('NICEPAY_CLIENT_SECRET_SAND');
    // }

    /**
     * Generate random GUID
     */
    public function generateGuid()
    {
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');

        $data    = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function merchantToken($timestamp,$referenceNo, $amt) {
        // SHA256( Concatenate(iMid + referenceNo + amt + merchantKey) )
        // echo $timestamp.' ';
        // echo $this->getMerchantID().' ';
        // echo $referenceNo.' ';
        // echo $amt.' ';
        // echo $this->getMerchantKey().' ';
        // echo 
        $hash = hash('sha256',$timestamp.
                            $this->getMerchantID().
                            $referenceNo.
                            $amt.
                            $this->getMerchantKey()
        );
        return $hash;
    }

    /**
     * Validate $phone based on NICEPAY standard, it should be:
     * - 08XXXXXXXXX
     * - 062+8XXXXXXXX
     *
     * @param $phone string phone no
     *
     * @return bool
     */
    public function isValidPhone($phone)
    {
        if (strpos($phone, '062+8') === 0 || strpos($phone, '08') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Remove unwanted character in $phone
     *
     * @param $phone string, unsanitized phone no
     *
     * @return string, sanitized phone without spaces/whitespaces
     */
    public function sanitizePhone($phone)
    {
        $phone = preg_replace('/\s/', '', $phone);

        if (strpos($phone, '062+8') === 0) {
            $suffix    = substr($phone, strlen('062+8'));
            $sanitized = '062+8' . preg_replace('/\D/', '', $suffix);
        } else {
            $sanitized = preg_replace('/\D/', '', $phone);
        }

        return $sanitized;
    }


    /**
     * Main api function to call to NICEPAY
     *
     * @param $url string, the path of api, without domain name
     * @param $payloadObject array, object that need to be sent to $url
     *
     * @return string response payload
     */
    public function nicepayApi($url, $payloadObject)
    {

        $jsonPayload = $payloadObject;
        
        $curl = curl_init();
        $opts = [
            CURLOPT_URL            => Nicepay::getBaseUrlAPI() . $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $jsonPayload,
            CURLOPT_HTTPHEADER     => [
                "Accept: */*",
                "Content-Type: application/json",
                "Host: dev.nicepay.co.id",
                "Cache-control: no-cache",
            ]
        ];

        curl_setopt_array($curl, $opts);

        $response = curl_exec($curl);
        $err      = curl_error($curl);

        curl_close($curl);


        if ($err) {
            $result = "cURL Error #:" . $err;
        } else {
            $result = $response;
        }
        
        return $result;
    }
    
    public function getError($id)
    {
        $error = array(

            // That always Unknown Error :)
            '00' =>   array(
                'errorCode'    => '00000',
                'errorMsg' => 'Unknown error. Contact it.support@ionpay.net.'
            ),
            // General Mandatory parameters
            '01' =>   array(
                'error'    => '10001',
                'errorMsg' => '(iMid) is not set. Please set (iMid).'
            ),
            '02' =>   array(
                'error'    => '10002',
                'errorMsg' => '(payMethod) is not set. Please set (payMethod).'
            ),
            '03' =>   array(
                'error'    => '10003',
                'errorMsg' => '(currency) is not set. Please set (currency).'
            ),
            '04' =>   array(
                'error'    => '10004',
                'errorMsg' => '(amt) is not set. Please set (amt).'
            ),
            '05' =>   array(
                'error'    => '10005',
                'errorMsg' => '(instmntMon) is not set. Please set (instmntMon).'
            ),
            '06' =>   array(
                'error'    => '10006',
                'errorMsg' => '(referenceNo) is not set. Please set (referenceNo).'
            ),
            '07' =>   array(
                'error'    => '10007',
                'errorMsg' => '(goodsNm) is not set. Please set (goodsNm).'
            ),
            '08' =>   array(
                'error'    => '10008',
                'errorMsg' => '(billingNm) is not set. Please set (billingNm).'
            ),
            '09' =>   array(
                'error'    => '10009',
                'errorMsg' => '(billingPhone) is not set. Please set (billingPhone).'
            ),
            '10' =>   array(
                'error'    => '10010',
                'errorMsg' => '(billingEmail) is not set. Please set (billingEmail).'
            ),
            '11' =>   array(
                'error'    => '10011',
                'errorMsg' => '(billingAddr) is not set. Please set (billingAddr).'
            ),
            '12' =>   array(
                'error'    => '10012',
                'errorMsg' => '(billingCity) is not set. Please set (billingCity).'
            ),
            '13' =>   array(
                'error'    => '10013',
                'errorMsg' => '(billingState) is not set. Please set (billingState).'
            ),
            '14' =>   array(
                'error'    => '10014',
                'errorMsg' => '(billingCountry) is not set. Please set (billingCountry).'
            ),
            '15' =>   array(
                'error'    => '10015',
                'errorMsg' => '(deliveryNm) is not set. Please set (deliveryNm).'
            ),
            '16' =>   array(
                'error'    => '10016',
                'errorMsg' => '(deliveryPhone) is not set. Please set (deliveryPhone).'
            ),
            '17' =>   array(
                'error'    => '10017',
                'errorMsg' => '(deliveryAddr) is not set. Please set (deliveryAddr).'
            ),
            '18' =>   array(
                'error'    => '10018',
                'errorMsg' => '(deliveryCity) is not set. Please set (deliveryCity).'
            ),
            '19' =>   array(
                'error'    => '10019',
                'errorMsg' => '(deliveryState) is not set. Please set (deliveryState).'
            ),
            '21' =>   array(
                'error'    => '10020',
                'errorMsg' => '(deliveryPostCd) is not set. Please set (deliveryPostCd).'
            ),
            '22' =>   array(
                'error'    => '10021',
                'errorMsg' => '(deliveryCountry) is not set. Please set (deliveryCountry).'
            ),
            '23' =>   array(
                'error'    => '10022',
                'errorMsg' => '(callBackUrl) is not set. Please set (callBackUrl).'
            ), '8' =>   array(
                'error'    => '10023',
                'errorMsg' => '(dbProcessUrl) is not set. Please set (dbProcessUrl).'
            ),
            '24' =>   array(
                'error'    => '10024',
                'errorMsg' => '(vat) is not set. Please set (vat).'
            ),
            '25' =>   array(
                'error'    => '10025',
                'errorMsg' => '(fee) is not set. Please set (fee).'
            ),
            '26' =>   array(
                'error'    => '10026',
                'errorMsg' => '(notaxAmt) is not set. Please set (notaxAmt).'
            ),
            '27' =>   array(
                'error'    => '10027',
                'errorMsg' => '(description) is not set. Please set (description).'
            ),
            '28' =>   array(
                'error'    => '10028',
                'errorMsg' => '(merchantToken) is not set. Please set (merchantToken).'
            ),
            '29' =>   array(
                'error'    => '10029',
                'errorMsg' => '(bankCd) is not set. Please set (bankCd).'
            ),

            // Mandatory parameters to Check Order Status
            '30' =>   array(
                'error'    => '10030',
                'errorMsg' => '(tXid) is not set. Please set (tXid).'
            )

        );
        return (json_encode($this->oneLiner($error[$id])));
    }
}