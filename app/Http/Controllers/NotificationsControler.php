<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Thirdparty\Nicepay\Nicepay;
use App\Nicepaylog;
use App\Order;
use App\Payment;
use App\Paymeth;
use App\AdminEntitas;
use App\Kontak;
use App\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Mail;
use App\Mail\Invoice;
use App\Mail\Notification;

class NotificationsController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
        Nicepay::$isProduction = env('NICEPAY_IS_PRODUCTION', 'true');

        date_default_timezone_set("Asia/Jakarta");
    }

    public function cronNPCheker(Request $request){
        if($request->header('Authorization') != "hUI8fd0u2j3X9z2qja7d98"){
            return response()->json([
              'status'=>false,
              "message" => "Missmatch Token"
            ],422);
        }
        
        #START
        echo date("Y-m-d H:i:s.U")."<br>START PAYMENT CHECKER<br><br>";
        $dateNow = Carbon::now()->toDateTimeString();
        $dateOld = "2020-08-25 00:00:00";
      


        // $payment    = Payment::where('id_transaksi',$referenceNo)->first();
        $paymeth    = Paymeth::where('parent_id','<=','5')->pluck('id');
        #Get DATA Transaksi yg belum kirim Email
        // var_dump($paymeth);
        $listTransaksi = Payment::select('id_transaksi','tgl_transaksi','email','id','id_parent')
            ->where('status','!=','paid')
            ->where('tgl_transaksi','<', $dateNow)
            ->whereIn('id_payment_method',[4,9,15])
            ->orderBy('tgl_transaksi','ASC')
            ->limit(150)
            ->get();

        // if(count($listTransaksi) > 0){
        //     foreach($listTransaksi as $keyTransaksi => $dataTransaksi){
        //         var_dump($dataTransaksi);
        //     }
        // }

        dd($listTransaksi);
        // $tot = count($listTransaksi);
        // echo "Total Trans : ".$tot."<br><br>";
  
        //   # Loop Data list Email yg akan dikirimkan.
        //   foreach ($listTransaksi as $key => $value) {
        //       $transaksi = CorezTransaksi::select('corez_transaksi.*', 'corez_donatur.donatur', 'corez_donatur.email', 'corez_donatur.alamat', 'corez_donatur.npwp', 'corez_donatur.hp', 'setting_program.program', 'hcm_kantor.kantor', 'hcm_karyawan.karyawan', 'hcm_karyawan.hp as hpKaryawan')
        //           ->join('corez_donatur', 'corez_donatur.id_donatur', '=', 'corez_transaksi.id_donatur')
        //           ->join('setting_program', 'setting_program.id_program', '=', 'corez_transaksi.id_program')
        //           ->leftJoin('hcm_kantor', 'hcm_kantor.id_kantor', '=', 'corez_transaksi.id_kantor_transaksi')
        //           ->leftJoin('hcm_karyawan', 'hcm_karyawan.id_karyawan', '=', 'corez_transaksi.id_crm')
        //           ->where(function($q) {
        //                   $q->where('send_email', '=', '')
        //                   ->orWhereNull('send_email');
        //               })
        //           ->where('corez_transaksi.id_donatur', $value->id_donatur)
        //           ->where('corez_donatur.id_donatur', $value->id_donatur)
        //           ->where('corez_transaksi.tgl_transaksi', $value->tgl_transaksi)
        //           ->where('corez_transaksi.approved_transaksi','y')
        //           ->get();
              
        //       echo "<h><b>".$value->id_donatur."</b></h4><br>";
        //       echo "<h><b>".$value->tgl_transaksi."</b></h4><br>";
  
        //       if($value->id_donatur == '9999999999999'){
        //           echo "Why Pandularas <br><br>";
        //           foreach($transaksi as $keyUTransaksi => $valueUTransaksi){
        //               echo "Id Donatur :".$valueUTransaksi->id_donatur."<br> On Date : ".$valueUTransaksi->tgl_transaksi."<br> Id Transaksi : ".$valueUTransaksi->id_transaksi."<br>";
        //               CorezTransaksi::where('corez_transaksi.id_transaksi', $valueUTransaksi->id_transaksi)->where('corez_transaksi.detailid', $valueUTransaksi->detailid)->update(['send_email' => "off"]);
        //           }
        //       }
        //       else{
        //           if(count($transaksi)>0){
        //               if($transaksi[0]->email){
        //                   $total_transaksi = 0;
        //                   $list_transaksi  = "";
        //                   $listProgram = "";
        //                   $listSumberDana = "";
        //                   $programTransaksi = "";
        //                   $listTglTransaksi = "";
        //                   foreach($transaksi as $keyTransaksi => $valueTransaksi){
        //                       $program = SettingProgram::select("id_program", "program","sumber_dana","setting_program.id_sumber_dana")
        //                           ->leftJoin("setting_sumber_dana","setting_program.id_sumber_dana","=","setting_sumber_dana.id_sumber_dana")
        //                           // ->where('aktif','y')
        //                           ->where('id_program',$valueTransaksi->id_program)
        //                           ->first();
        //                       $ending = (count($transaksi) == $keyTransaksi+1)?"":"<br>";
  
        //                       $total_transaksi += $valueTransaksi->transaksi;
        //                       $list_transaksi .= "Rp ".number_format($valueTransaksi->transaksi,2,",",".").$ending;
        //                       $listProgram .= $program->program.$ending;
        //                       $listSumberDana .= $program->sumber_dana.$ending;
        //                       $programTransaksi .= $program->program." ".number_format($valueTransaksi->transaksi,2,",",".").'\n';
        //                       $listTglTransaksi .= $valueTransaksi->tgl_transaksi.'\n';
        //                   }
  
        //                   # URL to Download PDF
        //                   $did = openssl_encrypt($value->id_donatur, "aes128", "JKH21315akdB7sdsI9",0,"xf8f78uZ9xH4S0Jn");
        //                   $savePdf = "https://seuneu.rumahzakat.org/service/cetakTransaksi?id_donatur=".urlencode($did)."&tgl_transaksi=".$value->tgl_transaksi;
                          
        //                   # Data Notifikasi
        //                   $data_notif = Array(
        //                       "savePdf" => $savePdf,
        //                       "email" => $transaksi[0]->email,
        //                       "hp" => $transaksi[0]->hp,
        //                       "donatur" => $transaksi[0]->donatur,
        //                       "id_donatur" => $transaksi[0]->id_donatur,
        //                       "transaksi" => number_format($total_transaksi,2,",","."),
        //                       "list_transaksi" => $list_transaksi,
        //                       "id_transaksi" => $transaksi[0]->id_transaksi,
        //                       "program" => $listProgram,
        //                       "programtransaksi" => $programTransaksi,
        //                       "sumber_dana" => $listSumberDana,
        //                       "tgl_donasi" => $transaksi[0]->tgl_donasi,
        //                       "tgl_donasi_concat" => $listTglTransaksi,
        //                       "tgl_transaksi" => $transaksi[0]->tgl_transaksi,
        //                       "kantor"=> $transaksi[0]->kantor,
        //                       "alamat"=> $transaksi[0]->alamat,
        //                       // "nama_outlet"=> $this->GetViaHimpunDetail($dgTransaksiDetail[0]),
        //                       // "data" =>$dgTransaksiDetail
        //                   );
  
        //                   # Parameter untuk Queue Email
        //                   $dataMsg = array(
        //                       'token' => 'df9kXa8Hu2fa04p0z34LpH1FPHof',
        //                       'email' => $transaksi[0]->email,
        //                       // 'email' => "mrivan7799@gmail.com",
        //                       'tipe'  => 'email',
        //                       'jenis' => 'Notifikasi Transaksi',
        //                       'i'     => 'id_EmailTrans',
        //                       // "attachment"=> $pdf,
        //                       'data'  => json_encode($data_notif)
        //                   );
  
        //                   # CURL Send ke Queue
        //                   $curl = curl_init();
                  
        //                   curl_setopt_array($curl, array(
        //                       CURLOPT_SSL_VERIFYPEER => false,
        //                       CURLOPT_URL => "https://api.rumahzakat.org/service/sent-email",
        //                       CURLOPT_RETURNTRANSFER => true,
        //                       CURLOPT_ENCODING => "",
        //                       CURLOPT_MAXREDIRS => 10,
        //                       CURLOPT_TIMEOUT => 100000,
        //                       CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        //                       CURLOPT_CUSTOMREQUEST => "POST",
        //                       CURLOPT_POSTFIELDS => $dataMsg,
        //                       CURLOPT_HTTPHEADER => array(
        //                           "Content-Type: multipart/form-data" 
        //                       ),
        //                   ));
        //                   $response = curl_exec($curl);
        //                   $err = curl_error($curl);
                  
        //                   curl_close($curl);
                  
        //                   if ($err) {
        //                           echo "cURL Error #:" . $err;
        //                   } 
        //                   else {
        //                       foreach($transaksi as $keyUTransaksi => $valueUTransaksi){
        //                           echo "Id Donatur :".$valueUTransaksi->id_donatur."<br> On Date : ".$valueUTransaksi->tgl_transaksi."<br> Id Transaksi : ".$valueUTransaksi->id_transaksi."<br>";
        //                           echo $transaksi[0]->email." - ".$response;
        //                           CorezTransaksi::where('corez_transaksi.id_transaksi', $valueUTransaksi->id_transaksi)->where('corez_transaksi.detailid', $valueUTransaksi->detailid)->update(['send_email' => $valueUTransaksi->email]);
        //                       }
        //                   }
        //               }
        //               else{
        //                   foreach($transaksi as $keyUTransaksi => $valueUTransaksi){
        //                       echo "Id Donatur :".$valueUTransaksi->id_donatur."<br> On Date : ".$valueUTransaksi->tgl_transaksi."<br> Id Transaksi : ".$valueUTransaksi->id_transaksi."<br>";
        //                       CorezTransaksi::where('corez_transaksi.id_transaksi', $valueUTransaksi->id_transaksi)->where('corez_transaksi.detailid', $valueUTransaksi->detailid)->update(['send_email' => "no email"]);
        //                   }
        //                   echo "No EMAIL for Id Donatur :".$value->id_donatur."<br> On Date : ".$value->tgl_transaksi;
        //               }
        //           }
        //           else{
        //               echo "No Transaksi Found for Id Donatur :".$value->id_donatur."<br> On Date : ".$value->tgl_transaksi;
        //           }
        //           echo "<br><br>";
        //       }
              
        //   }
        //   echo "<br>ENDs";
    }

    public function dbProcess(Request $request){
        $this->validate($request, [
            'payMethod' => 'required',
            'referenceNo' => 'required',
            'status' => 'required',
            'payMethod' => 'required',
            'merchantToken' => 'required',
            'tXid' => 'required'
        ]);
        
        
        $req = $request->all();
        $nicepay = new Nicepay;
            

        $code = "";
        $code_bayar = "";

        $amt            = (isset($req['amt']))?$req['amt']:"";
        $billingNm      = (isset($req['billingNm']))?$req['billingNm']:"";
        $currency	    = (isset($req['currency']))?$req['currency']:"";
        $goodsNm	    = (isset($req['goodsNm']))?$req['goodsNm']:"";
        $matchCl	    = (isset($req['matchCl']))?$req['matchCl']:"";
        $merchantToken	= (isset($req['merchantToken']))?$req['merchantToken']:"";
        $payMethod	    = (isset($req['payMethod']))?$req['payMethod']:"";
        $referenceNo	= (isset($req['referenceNo']))?$req['referenceNo']:"";
        $status	        = (isset($req['status']))?$req['status']:"";
        $tXid	        = (isset($req['tXid']))?$req['tXid']:"";
        $transDt	    = (isset($req['transDt']))?$req['transDt']:"";
        $transTm	    = (isset($req['transTm']))?$req['transTm']:"";


        $payment    = Payment::where('id_transaksi',$referenceNo)->first();
        $paymeth    = Paymeth::find($payment['id_payment_method']);

        $merData    = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];

        $merchantTokenComparator = $nicepay->getMerTokNotif($iMid,$tXid,$amt,$merKey);

        // echo "referenceNo : ".$referenceNo."<br>";
        // echo "id_payment_method : ".$payment['id_payment_method']."<br>";
        // echo "id_entitas : ".$paymeth['id_entitas']."<br>";
        // echo "IMID : ".$iMid."<br>";
        // echo "referenceNo : ".$referenceNo."<br>";
        // echo "amt : ".$amt."<br>";
        // echo "merKey : ".$merKey."<br>";
        
        // echo "<br>merchantToken : <br>".$merchantToken."<br>";
        // echo "merchantTokenComparator : <br>".$merchantTokenComparator."<br>";
        
        if($merchantTokenComparator != $merchantToken){
            return response()->json([
                'status'=>false,
                "message" => "Missmatch Merchant Token!!!",
            ],422);
        }

        if($payMethod == "01"){
            $authNo         = (isset($req['authNo']))?$req['authNo']:"";
            $IssueBankCd    = (isset($req['IssueBankCd']))?$req['IssueBankCd']:"";
            $IssueBankNm    = (isset($req['IssueBankNm']))?$req['IssueBankNm']:"";
            $acquBankCd     = (isset($req['acquBankCd']))?$req['acquBankCd']:"";
            $acquBankNm     = (isset($req['acquBankNm']))?$req['acquBankNm']:"";
            $cardNo         = (isset($req['cardNo']))?$req['cardNo']:"";
            $cardExpYymm    = (isset($req['cardExpYymm']))?$req['cardExpYymm']:"";
            $instmntMon     = (isset($req['instmntMon']))?$req['instmntMon']:"";
            $instmntType    = (isset($req['instmntType']))?$req['instmntType']:"";
            $preauthToken   = (isset($req['preauthToken']))?$req['preauthToken']:"";
            $recurringToken = (isset($req['recurringToken']))?$req['recurringToken']:"";
            $ccTransType    = (isset($req['ccTransType']))?$req['ccTransType']:"";
            $vat            = (isset($req['vat']))?$req['vat']:"";
            $fee	        = (isset($req['fee']))?$req['fee']:"";
            $notaxAmt       = (isset($req['notaxAmt']))?$req['notaxAmt']:"";
        }
        else if($payMethod == "02"){
            $code       = (isset($req['bankCd']))?$req['bankCd']:"";
            $code_bayar = (isset($req['vacctNo']))?$req['vacctNo']:"";
            $ValidDt    = (isset($req['vacctValidDt']))?$req['vacctValidDt']:"";
            $ValidTm    = (isset($req['vacctValidTm']))?$req['vacctValidTm']:"";
        }
        else if($payMethod == "03"){
            $code           = (isset($req['mitraCd']))?$req['mitraCd']:"";
            $code_bayar     = (isset($req['payNo']))?$req['payNo']:"";
            $ValidDt        = (isset($req['payValidDt']))?$req['payValidDt']:"";
            $ValidTm        = (isset($req['payValidTm']))?$req['payValidTm']:"";
            // $receiptCode    = (isset($req['receiptCode']))?$req['amt']:"";
            // $mRefNo         = (isset($req['mRefNo']))?$req['amt']:"";
            // $depositDt      = (isset($req['depositDt']))?$req['amt']:"";
            // $depositTm      = (isset($req['depositTm']))?$req['amt']:"";
        }

        $nicepayLog    = new Nicepaylog;

        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->payment_method = $payMethod;
        $nicepayLog->code     = $code;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->virtual_account_no = $code_bayar;
        $nicepayLog->update   = Carbon::now();
        $nicepayLog->request  = "";
        $nicepayLog->response = addslashes(json_encode($req));
        $nicepayLog->status   = addslashes($status);
        $nicepayLog->action   = "Notification";
        $nicepayLog->id_entitas = $paymeth['id_entitas'];

        $nicepayLog->save();

        $status = ($status == 0)?"paid":(
                        ($status == 1)?"failed":(
                            ($status == 2)?"void":(
                                ($status == 3)?"unpaid":(
                                    ($status == 4)?"expired":(
                                        ($status == 5)?"readyToPaid":(
                                            ($status == 9)?"Initialization / Reversal":"What method?"
                                        )
                                    )
                                )
                            )
                        )
                    );

        // $payment = Payment::where('id_transaksi', $referenceNo)->first();
        if($status == "paid"){
            $orderdata  = Order::where('id_order',$referenceNo)->get();
            // $kontak     = Kontak::where('id', $payment['id_kontak'])->first();
            
            $to_address = trim($payment['email']);
            $nama       = $payment['nama_customer'];
            $alamat     = $payment['alamat'];
            $email      = trim($payment['email']); 
            $hp         = $payment['hp'];
            $parent_id  = $paymeth['parent_id'];
            
            if ($parent_id == 2 ) {
                $title  = "Virtual Account :";
                $number = (isset($req['vacctNo']))?$req['vacctNo']:"";
              } elseif ($parent_id == 3) {
                $title  = "Kode Pembayaran :";
                $number = (isset($req['payNo']))?$req['payNo']:"";
              } else{
                $title = "No.Rekening :";
                $number = DB::table('ra_bank_rek')->where('id_payment_method',$request->input('id_payment'))->where('id_kantor',$request->input('id_kantor'))->value('id_rekening');
              }

            $hasil = Mail::send(
                    (new Notification($to_address, $payment, $orderdata, $nama, $alamat, $email, $parent_id,$hp,$title,$number))->build()
                );
        }

        if($payment){
            $payment->status = $status;
            $payment->save();
            $msg = array("status"=>"true","msg"=>"Berhasil Update Data Transaksi");
        }else{
            $msg = array("status"=>"false","msg"=>"No Transaction Available");
        }
        echo json_encode($msg);
    }

    public function TestRegistration(Request $request){
        $req = $request->all();
        $this->passed = $req;
        $id_unique = $this->getRandomString(10);
        $this->npRegistration($id_unique);
    }

    public function npInuqiry(Request $request){
        $req = $request->all();

        $timestamp      = date("YmdHis");
        $referenceNo    = $req['referenceNo'];
        $tXid           = $req['tXid'];
        $amt            = $req['amt'];

        $payment    = Payment::where('id_transaksi',$referenceNo)->first();
        $paymeth    = Paymeth::find($payment['id_payment_method']);
        $merData    = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];

        $nicepay = new Nicepay;

        $merchantToken  = $nicepay->merchantToken($timestamp,$iMid,$referenceNo,$amt,$merKey);

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>$tXid,
                "iMid"          =>$iMid,
                "referenceNo"   =>$referenceNo,
                "amt"           =>$amt,
                "merchantToken" =>$merchantToken
            );
        $detailTrans =json_encode($detailTrans);
        
        $transaksiAPI   = $nicepay->nicepayApi("nicepay/direct/v2/inquiry",$detailTrans); 
        $response       = json_decode($transaksiAPI);
        $msg            = $response->resultMsg;
        
        $nicepayLog    = new Nicepaylog;
        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->request  = addslashes($detailTrans);
        $nicepayLog->response = addslashes($transaksiAPI);
        $nicepayLog->status   = addslashes($msg);
        $nicepayLog->action   = "Inquiry";
        $nicepayLog->id_entitas = $paymeth['id_entitas'];
        $nicepayLog->save();

        return $transaksiAPI;
    }

    public function npCancelVA(Request $request){
        $req = $request->all();

        $timestamp      = date("YmdHis");
        $referenceNo    = $req['referenceNo'];
        $tXid           = $req['tXid'];
        $amt            = $req['amt'];
        
        $payment    = Payment::where("id_transaksi",$referenceNo)->first();
        $paymeth    = Paymeth::find($payment['id_payment_method']);
        $merData    = AdminEntitas::where('id_entitas',$paymeth['id_entitas'])->first();
        $iMid       = Nicepay::$isProduction ? $merData['merchant_id']:$merData['mid_sand'];
        $merKey     = Nicepay::$isProduction ? $merData['merchant_key']:$merData['merkey_sand'];
        
        $payMeth    = sprintf("%02d", $payment["id_payment_method"]);


        $nicepay = new Nicepay;

        $merchantToken  = $nicepay->merchantToken($timestamp,$iMid,$tXid,$amt,$merKey);

        $detailTrans = array(
                "timeStamp"     =>$timestamp,
                "tXid"          =>$tXid,
                "iMid"          =>$iMid,
                "amt"           =>$amt,
                "merchantToken" =>$merchantToken,
                "payMethod"     =>$payMeth,
                "preauthToken"  =>"",
                "worker"        =>"",
                "cancelType"    =>1,
                "cancelMsg"     =>"Need To Be Canceled",
                "cancelServerIp"=>"",
                "cancelUserId"  =>"",
                "cancelUserInfo"=>"",
                "cancelRetryCnt"=>""
            );
        // echo
        $detailTrans =json_encode($detailTrans);
        
        $transaksiAPI   = $nicepay->nicepayApi("nicepay/direct/v2/cancel",$detailTrans); 
        $response       = json_decode($transaksiAPI);
        $msg            = $response->resultMsg;
        
        $nicepayLog    = new Nicepaylog;
        $nicepayLog->id_order = $referenceNo;
        $nicepayLog->txid     = $tXid;
        $nicepayLog->update   = Carbon::now();
        $nicepayLog->request  = addslashes($detailTrans);
        $nicepayLog->response = addslashes($transaksiAPI);
        $nicepayLog->status   = addslashes($msg);
        $nicepayLog->action   = "CancelVA";
        $nicepayLog->id_entitas = $paymeth['id_entitas'];
        
        $nicepayLog->save();

        return $transaksiAPI;
    }

    function getRandomString($n) { 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = ''; 
  
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
      
        return $randomString; 
    }

}
