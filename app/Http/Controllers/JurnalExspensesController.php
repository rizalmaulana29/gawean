<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Paymeth;
use App\AdminEntitas;
use App\Expenses;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JurnalExspensesController extends Controller
{
    public function InsertExpenses(){

      $entitas = [1,2,3,4,5,6,7,8,9,10];

      foreach ($entitas as $key => $id) {
        $jurnal_con = $this->Entitas($id);
        // var_dump($jurnal_con);
        if ($jurnal_con['status'] == true) {
          $dataExpenses = $this->getExpenses($jurnal_con['message']);
          // dd($dataExpenses);
          if ($dataExpenses['status'] == true) {
            $expenses = json_decode($dataExpenses);

            foreach ($$expenses as $key => $expens) {
              $expensLast = Expense::orderBy('sort', 'asc')->first();
              if ($expensLast == null || $expenses->expense->id != $expensLast->expenses_id ) {
                $insertToTable = new Expenses;
                $insertToTable->expenses_id = $expenses->expense->id;
                $insertToTable->expenses_transaction_no = $expenses->expense->transaction_no;
                $insertToTable->expenses_transaction_date = $expenses->expense->transaction_date;

                foreach ($expenses->expense->transaction_account_lines_attributes as $key => $atribute) {
                  $insertToTable->expenses_transaction_account_lines_attributes__account__number= $atribute->account->number;
                  $insertToTable->expenses_transaction_account_lines_attributes__account__name = $atribute->account->name;
                  $insertToTable->expenses_transaction_account_lines_attributes__description = $atribute->description;
                  $insertToTable->expenses_transaction_account_lines_attributes__debit =  $atribute->debit;
                }
                $insertToTable->save();
              } else {
                continue;
              }
              
            }
            
            if ($insertToTable) {
              $response = array("status" => true,
                                "id"     => $expenses->expense->id,
                                "message"=> $expenses);
            }else{
              $response = array("status" => false,
                                "message"=> $expenses);
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


    private function getExpenses($jurnalKoneksi){
      

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

