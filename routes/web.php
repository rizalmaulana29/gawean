<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
// header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
	$router->get('harga', 	['uses' =>'HargaController@show']);
	$router->get('code',	['uses' =>'CodeController@unicCode']);
	$router->post('cart', 	['uses' => 'CartController@cart']);

	$router->post('signup/agen', ['uses' =>'AgenController@signup']);

	$router->post('create/customer', 		['uses' => 'JurnalController@CreateCustomer']);
	$router->post('create/salesorder', 		['uses' => 'JurnalController@SalesOrder']);
	$router->get('filter', 					['uses' => 'JurnalController@Filtering']);
	$router->get('filter/adjustment', 		['uses' => 'JurnalController@AdjustmentTransaksi']);
	$router->get('FilteringCicilan', 		['uses' => 'JurnalCicPelController@FilteringCicilan']);
	$router->get('filter/transaksiCiPel', 	['uses' => 'JurnalCicPelController@transaksiCiPel']);
	$router->get('filter/PO', 				['uses' => 'JurnalPOController@FilteringPO']);
	$router->post('jurnal/transaksi/delete',['uses' => 'JurnalDeleteController@DeleteDataJurnal']);

	//Devel new concept
	$router->get('filter/edit', 					['uses' => 'JurnalDevNewController@FilteringEdit']);
	$router->get('filter/devNew', 					['uses' => 'JurnalDevNewController@Filtering']);
	$router->get('filter/paid', 					['uses' => 'JurnalDevNewController@paidTriger']);
	$router->get('filter/adjustment/invoice/devNew',['uses' => 'JurnalDevNewController@AdjustmentToInvoice']);
	$router->get('filter/adjustment/devNew', 		['uses' => 'JurnalDevNewController@AdjustmentTransaksi']);
	$router->get('filter/delivery/dev', 			['uses' => 'JurnalDeliveryController@Filtering']);
	$router->get('filter/POnew', 					['uses' => 'JurnalPODevNewController@FilteringPO']);
	$router->get('filter/PO/invoice', 				['uses' => 'JurnalPODevNewController@FilteringPOtoInvoice']);
	$router->get('filter/PO/Payment', 				['uses' => 'JurnalPODevNewController@FilteringPayment']);

	$router->get('expenses',		 				['uses' => 'JurnalExspensesController@InsertExpenses']);

	$router->post('create/customer', 	['uses' => 'JurnalDevController@CreateCustomer']);
	$router->post('create/salesorder', 	['uses' => 'JurnalDevController@SalesOrder']);

	$router->get('filterDev', 					['uses' => 'JurnalDevController@Filtering']);
	$router->get('filterDev/bedabulan', 		['uses' => 'JurnalDevController@transaksiBedaBulan']);
	$router->get('filter/transaksiCiPelDev', 	['uses' => 'JurnalDevController@transaksiCiPelDev']);
	$router->get('settlementNP', 				['uses' => 'SettlementController@MIDDate']);
	
	$router->get('filter/adjustmentDev',['uses' => 'JurnalDevController@AdjustmentTransaksi']);
	$router->post('cartDev', 			['uses' => 'CartDevController@cart']);
	$router->post('TestWA', 			['uses' => 'CartDevController@sendWa']);

	$router->post('TestNotif', 	['uses' => 'CartDevController@notifTransaksi']);
	$router->post('testing', 	['uses' => 'CartDevController@testing_aja']);
	
	$router->post('stockTool', 	['uses' => 'ToolsController@toolsMath']);

	$router->group(['middleware' => 'all.cors'], function () use ($router) {
		$router->post('check/number', 	['uses' => 'CartController@checkNumber']);
		$router->options('check/number',['uses' => 'CartController@checkNumber']);
	});

	$router->post('notifications', 		['uses' => 'NotificationsController@dbProcess']);
	$router->post('testRegistration', 	['uses' => 'NotificationsController@TestRegistration']);
	$router->post('inquiry', 			['uses' => 'NotificationsController@npInuqiry']);
	$router->post('cancelVA', 			['uses' => 'NotificationsController@npCancelVA']);
	$router->get('mail',  ['uses' => 'CartController@sendemail']);
	
	$router->post('cronChecker', 		['uses' => 'NotificationsController@cronNPCheker']);
	
	$router->get('cron/keagenan', 		['uses' => 'KeagenanController@cronKeagenan']);
	// $router->post('cron/keagenan', 		['uses' => 'KeagenanController@cronNPCheker']);

	$router->group(['prefix' => 'auth', 'middleware' => 'all.cors'], function () use ($router) {
		$router->options('/login', ['uses' => 'Auth\AuthController@authenticate']);
        $router->options('/login/kuma-kami/ini-mah', ['uses' => 'Auth\AuthController@sakBabyPass']);
        
		$router->post('/login', ['uses' => 'Auth\AuthController@authenticate']);
        $router->post('/login/kuma-kami/ini-mah', ['uses' => 'Auth\AuthController@sakBabyPass']);
	});

	$router->group(['prefix' => 'signed','middleware' => ['jwt.auth','all.cors']], function () use ($router) {
		$router->group(['prefix'=>'dashboard'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\DashboardController@index']);
		});
		$router->group(['prefix'=>'profile'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\ProfileController@index']);
			$router->get('/referral', ['uses' => 'Signed\ProfileController@referralLink']);
		});
		$router->group(['prefix'=>'affiliate'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\AffiliateController@index']);
		});
		$router->group(['prefix'=>'withdraw'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\WithdrawController@index']);
			$router->get('/payout', ['uses' => 'Signed\WithdrawController@payout']);
		});
	});

	
});

$router->group(['prefix' => 'uploads'], function() use ($router){
    $router->get('online/{imageName}', ['uses' => 'CartController@image']);
});
