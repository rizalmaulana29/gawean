<?php
use App\Http\Controllers\JurnalController;

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


 //header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
 //header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');
//$router->get('/test', function () {
  //  return response()->json(['message' => 'Test route is working']);
//});
$router->group(['prefix' => 'api'], function () use ($router) {
    $router->get('customers', 'JurnalController@getCustomers');
});

$router->get('/test', function () {
    return response()->json(['message' => 'Route is working!']);
});

$router->group(['prefix' => 'api'], function () use ($router) {
    // Route untuk get customers
    $router->get('customers', 'JurnalController@getCustomers');
});

// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
// header('Access-Control-Allow-Headers: Content-Type, X-Auth-Token, Origin, Authorization');
// routes/web.php atau routes/api.php
$router->get('/test-jurnal', function () {
    $jurnalApi = app(App\Services\JurnalApi::class);
    return response()->json(['status' => 'success', 'message' => 'JurnalApi instance created']);
});



$router->get('/', function () use ($router) {
	return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
	

	$router->get('harga', 	['uses' => 'HargaController@show']);
	$router->get('code',	['uses' => 'CodeController@unicCode']);
	$router->post('cart', 	['uses' => 'CartController@cart']);
	$router->get('transaction', ["middleware" => "jwt.auth", 'uses' => 'TransactionController@detailTrx']);
	$router->post('transaction/detail', ['uses' => 'TransactionController@detailTrxPublic']);
	$router->get('/customers', [JurnalController::class, 'getCustomers']);

	$router->get('order', ['uses' => 'OrderController@order']);
	$router->get('vendor', ['uses' => 'VendorController@vendor']);
	$router->get('getpendapatan', ['uses' => 'PendapatanController@getpendapatan']);
	$router->get('getAnak',['uses'=> 'PendapatanController@getAnak']);
	$router->post('updateIdAnak',['uses'=> 'PendapatanController@updateIdAnak']);

	$router->post('cekTransaction', ['uses' => 'TransactionController@checkTrx']);

	$router->post('signup/agen', ['uses' => 'AgenController@signup']);
	$router->get('email/verify', ['uses' => 'AgenController@verifyEmail']);

	//testing get customer from jurnal with new configuration
	

	//$router->get('customers', ['uses' => 'getCustomers']);


	$router->post('create/customer', 		['uses' => 'JurnalController@CreateCustomer']);
	$router->post('create/salesorder', 		['uses' => 'JurnalController@SalesOrder']);
	$router->get('filter', 					['uses' => 'JurnalController@Filtering']);
	$router->get('filter/adjustment', 		['uses' => 'JurnalController@AdjustmentTransaksi']);
	$router->get('FilteringCicilan', 		['uses' => 'JurnalCicPelController@FilteringCicilan']);
	$router->get('filter/transaksiCiPel', 	['uses' => 'JurnalCicPelController@transaksiCiPel']);
	$router->get('filter/PO', 				['uses' => 'JurnalPOController@FilteringPO']);
	$router->post('jurnal/transaksi/delete', ['uses' => 'JurnalDeleteController@DeleteDataJurnal']);

	//Devel new concept
	$router->get('filter/edit', 					['uses' => 'JurnalDevNewController@FilteringEdit']);
	$router->get('filter/devNew', 					['uses' => 'JurnalDevNewController@Filtering']);
	$router->get('filter/paid', 					['uses' => 'JurnalDevNewController@paidTriger']);
	$router->get('filter/adjustment/invoice/devNew', ['uses' => 'JurnalDevNewController@AdjustmentToInvoice']);
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

	$router->get('filter/adjustmentDev', ['uses' => 'JurnalDevController@AdjustmentTransaksi']);
	$router->post('cartDev', 			['uses' => 'CartDevController@cart']);
	$router->post('TestWA', 			['uses' => 'CartDevController@sendWa']);

	$router->post('TestNotif', 	['uses' => 'CartDevController@notifTransaksi']);
	$router->post('testing', 	['uses' => 'CartDevController@testing_aja']);

	$router->post('stockTool', 	['uses' => 'ToolsController@toolsMath']);

	$router->group(['middleware' => 'all.cors'], function () use ($router) {
		$router->post('check/number', 	['uses' => 'CartController@checkNumber']);
		$router->options('check/number', ['uses' => 'CartController@checkNumber']);
	});

	$router->post('notifications', 		['uses' => 'NotificationsController@dbProcess']);
	$router->post('testRegistration', 	['uses' => 'NotificationsController@TestRegistration']);
	$router->post('inquiry', 			['uses' => 'NotificationsController@npInuqiry']);
	$router->post('cancelVA', 			['uses' => 'NotificationsController@npCancelVA']);
	$router->get('mail',  ['uses' => 'CartController@sendemail']);

	$router->post('cronChecker', 		['uses' => 'NotificationsController@cronNPCheker']);

	$router->get('cron/keagenan', 		['uses' => 'KeagenanController@cronKeagenan']);
	// $router->post('cron/keagenan', 		['uses' => 'KeagenanController@cronNPCheker']);

	$router->post('wanotifsembelih', ['uses' => 'SendWAController@sendWhatsapp']);
	$router->post('sendWAManual', ['uses' => 'SendWAController@sendWhatsappManual']);
	$router->get('sendWAVOC', ['uses' => 'SendWAController@sendWhatsappVOC']);
	
	$router->group(['prefix' => 'auth', 'middleware' => 'all.cors'], function () use ($router) {
		$router->options('/login', ['uses' => 'Auth\AuthController@authenticate']);
		$router->options('/login/kuma-kami/ini-mah', ['uses' => 'Auth\AuthController@sakBabyPass']);

		$router->post('/login', ['uses' => 'Auth\AuthController@authenticate']);
		$router->post('/login/kuma-kami/ini-mah', ['uses' => 'Auth\AuthController@sakBabyPass']);
	});

	$router->group(['prefix' => 'signed', 'middleware' => ['jwt.auth', 'all.cors']], function () use ($router) {

		$router->group(['prefix' => 'dashboard'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\DashboardController@index']);
		});
		$router->group(['prefix' => 'profile'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\ProfileController@index']);
			$router->get('/referral', ['uses' => 'Signed\ProfileController@referralLink']);
			$router->post('/update', ['uses' => 'Signed\ProfileController@update']);
		});
		$router->group(['prefix' => 'affiliate'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\AffiliateController@index']);
			$router->get('/listReseller', ['uses' => 'Signed\AffiliateController@listReseller']);
		});
		$router->group(['prefix' => 'withdraw'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\WithdrawController@index']);
			$router->post('/payout', ['uses' => 'Signed\WithdrawController@payout']);
		});
		$router->group(['prefix' => 'shopping'],  function () use ($router) {
			$router->get('/', ['uses' => 'Signed\ShoppingController@index']);
			$router->get('/detail', ['uses' => 'Signed\ShoppingController@detailShopping']);
		});
	});
	$router->group(['prefix' => 'download/qurban'], function () use ($router) {
		$router->get('/report/{payloads}', ['uses' => 'Report\QurbanController@redirectReport']);
		$router->get('/certificate/{payloads}', ['uses' => 'Report\QurbanController@redirectCertificate']);
	});

	$router->get('test/encrypt/{message}', ['uses' => 'TransactionController@testEncrypt']);
	$router->get('test/decrypt/{message}', ['uses' => 'TransactionController@testDecrypt']);
	
	$router->group(['prefix' => 'survey', 'middleware' => ['all.cors']], function () use ($router) {
		$router->get('/', ['uses' => 'SurveyController@index']);
		$router->options('create', ['uses' => 'SurveyController@create']);
		$router->options('checkStatus', ['uses' => 'SurveyController@checkStatusSurvey']);
		
		$router->post('create', ['uses' => 'SurveyController@create']);
		$router->post('checkStatus', ['uses' => 'SurveyController@checkStatusSurvey']);
	});
});

$router->group(['prefix' => 'uploads'], function () use ($router) {
	$router->get('online/{imageName}', ['uses' => 'CartController@image']);
});
