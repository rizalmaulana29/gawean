<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Paymeth;
use App\AdminEntitas;
use App\Expenses;
use App\ExpensesAttribute;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JurnalTransaksiReportController extends Controller
{
    public function filteringAkun(){

      $entitas = [1,2,3,4,5,6,7,8,9,10];

      foreach ($entitas as $key => $id) {
        $jurnal_con = $this->Entitas($id);
        
        if ($jurnal_con['status'] == true) {
          $dataExpenses = $this->getExpenses($jurnal_con['message']);
          
          if ($dataExpenses['status'] == true) {

            $expenses = json_decode($dataExpenses['message']);

            foreach ($expenses->expenses as $key => $expens) {

              $expensLast = Expenses::where('id_entitas',$jurnal_con['message']['id_entitas'])
                                    ->where('expenses_id',$expens->id)
                                    ->first();

              if (!$expensLast || $expensLast == null ) {

                $insertToTable = new Expenses;
                $insertToTable->expenses_id               = $expens->id;
                $insertToTable->expenses_transaction_no   = $expens->transaction_no;
                $insertToTable->expenses_transaction_date = $expens->transaction_date;

                foreach ($expens->transaction_account_lines_attributes as $key => $atribute) {
                  $insertAttribute = new ExpensesAttribute;
                  $insertAttribute->expenses_id     = $expens->id;
                  $insertAttribute->account__number = $atribute->account->number;
                  $insertAttribute->account__name   = $atribute->account->name;
                  $insertAttribute->description     = $atribute->description;
                  $insertAttribute->debit           = $atribute->debit;
                  $insertAttribute->save();
                }

                $insertToTable->cdt = Carbon::now();
                $insertToTable->id_entitas = $jurnal_con['message']['id_entitas'];
                $insertToTable->save();

              } else {

                continue;
              }
              
            }
            
            if (!$expensLast && $insertToTable) {
              $response = array("status" => true,
                                "message"=> $expenses);
            }else{
              $response = array("status" => false,
                                "message"=> "tidak ada data yg di inputkan");
            }
          } else {
            $response = array("status"=>false,"message"=> $dataExpenses);
          }

        } else {
          continue;
        }
        
      }
      
      return $response;
    }

    public function FilteringTransaksi(){

      $entitas = [1,2,3,4,5,6,7,8,9,10];

      foreach ($entitas as $key => $id) {
        $jurnal_con = $this->Entitas($id);
        
        if ($jurnal_con['status'] == true) {
          $dataExpenses = $this->getExpenses($jurnal_con['message']);
          
          if ($dataExpenses['status'] == true) {

            $expenses = json_decode($dataExpenses['message']);

            foreach ($expenses->expenses as $key => $expens) {

              $expensLast = Expenses::where('id_entitas',$jurnal_con['message']['id_entitas'])
                                    ->where('expenses_id',$expens->id)
                                    ->first();

              if (!$expensLast || $expensLast == null ) {

                $insertToTable = new Expenses;
                $insertToTable->expenses_id               = $expens->id;
                $insertToTable->expenses_transaction_no   = $expens->transaction_no;
                $insertToTable->expenses_transaction_date = $expens->transaction_date;

                foreach ($expens->transaction_account_lines_attributes as $key => $atribute) {
                  $insertAttribute = new ExpensesAttribute;
                  $insertAttribute->expenses_id     = $expens->id;
                  $insertAttribute->account__number = $atribute->account->number;
                  $insertAttribute->account__name   = $atribute->account->name;
                  $insertAttribute->description     = $atribute->description;
                  $insertAttribute->debit           = $atribute->debit;
                  $insertAttribute->save();
                }

                $insertToTable->cdt = Carbon::now();
                $insertToTable->id_entitas = $jurnal_con['message']['id_entitas'];
                $insertToTable->save();

              } else {

                continue;
              }
              
            }
            
            if (!$expensLast && $insertToTable) {
              $response = array("status" => true,
                                "message"=> $expenses);
            }else{
              $response = array("status" => false,
                                "message"=> "tidak ada data yg di inputkan");
            }
          } else {
            $response = array("status"=>false,"message"=> $dataExpenses);
          }

        } else {
          continue;
        }
        
      }
      
      return $response;
    }


    private function getAkun($jurnalKoneksi){
      

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.jurnal.id/core/api/v1/expenses",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "{}",
        CURLOPT_HTTPHEADER => array(
                                    "apikey: ".$jurnalKoneksi['jurnal_key'],
                                    "Authorization: ".$jurnalKoneksi['jurnal_auth'],
                                    "content-type: application/json"
        ),
      ));
      $response = curl_exec($curl);
      $err = curl_error($curl);

      $findString    = 'expense';
      $searchResponse = stripos($response, 'expense');

      if ($err) {
          $response = array("status"=>"failed","message"=>$err);
      } 
      else {
          if ($searchResponse == true){
              $dataResponse = $response;
              $response = array("status" => true,
                                "message"=> $dataResponse);
          }
          else{

              $response = array("status"=>false,"message"=> "Expenses ".$response);
          }
      }

      return $response;
    }


    private function Entitas($id_entitas){
      
      $getDataKoneksi = AdminEntitas::where('id',$id_entitas)->first();
      if ($getDataKoneksi['jurnal_key'] != '' && $getDataKoneksi['jurnal_key'] != null ) {
        $response = array("status"=>true,"message"=> $getDataKoneksi);
      } else {
        $response = array("status"=>false,"message"=> "belum ada key jurnal");
      }

      return $response;
    }

}

