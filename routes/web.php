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

	$router->post('create/customer', 		['uses' => 'JurnalController@CreateCustomer']);
	$router->post('create/salesorder', 		['uses' => 'JurnalController@SalesOrder']);
	$router->get('filter', 					['uses' => 'JurnalController@Filtering']);
	$router->get('filter/adjustment', 		['uses' => 'JurnalController@AdjustmentTransaksi']);
	$router->get('FilteringCicilan', 		['uses' => 'JurnalCicPelController@FilteringCicilan']);
	$router->get('filter/transaksiCiPel', 	['uses' => 'JurnalCicPelController@transaksiCiPel']);
	$router->get('filter/PO', 				['uses' => 'JurnalPOController@FilteringPO']);
	$router->post('jurnal/transaksi/delete',['uses' => 'JurnalDeleteController@DeleteDataJurnal']);

	$router->post('create/customer', 	['uses' => 'JurnalDevController@CreateCustomer']);
	$router->post('create/salesorder', 	['uses' => 'JurnalDevController@SalesOrder']);

	$router->get('filterDev', 	['uses' => 'JurnalDevController@Filtering']);
	$router->get('filterDev/bedabulan', 	['uses' => 'JurnalDevController@transaksiBedaBulan']);
	
	$router->post('cartDev', 	['uses' => 'CartDevController@cart']);
	$router->post('TestWA', 	['uses' => 'CartDevController@sendWa']);

	$router->post('TestNotif', 	['uses' => 'CartDevController@notifTransaksi']);
	$router->get('testing', 	['uses' => 'CartDevController@testing_aja']);
	
	$router->post('stockTool', 	['uses' => 'ToolsController@toolsMath']);

	$router->group(['middleware' => 'all.cors'], function () use ($router) {
		$router->post('check/number', 	['uses' => 'CartController@checkNumber']);
		$router->options('check/number', 	['uses' => 'CartController@checkNumber']);
	});

	$router->post('notifications', 		['uses' => 'NotificationsController@dbProcess']);
	$router->post('testRegistration', 	['uses' => 'NotificationsController@TestRegistration']);
	$router->post('inquiry', 			['uses' => 'NotificationsController@npInuqiry']);
	$router->post('cancelVA', 			['uses' => 'NotificationsController@npCancelVA']);
	$router->get('mail',  ['uses' => 'CartController@sendemail']);
	
	$router->post('cronChecker', 		['uses' => 'NotificationsController@cronNPCheker']);
});

$router->group(['prefix' => 'uploads'], function() use ($router){
    $router->get('online/{imageName}', ['uses' => 'CartController@image']);
});
