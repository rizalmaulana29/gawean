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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'api'], function () use ($router) {
	$router->get('harga', 	['uses' =>'HargaController@show']);
	$router->get('code',	['uses' =>'CodeController@unicCode']);
	$router->post('cart', 	['uses' => 'CartController@cart']);

	$router->post('cartDev', 	['uses' => 'CartDevController@cart']);
	$router->post('TestWA', 	['uses' => 'CartDevController@sendWa']);
	
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
