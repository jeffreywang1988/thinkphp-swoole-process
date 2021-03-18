<?php
/**
 * 开发者模块，用于平台和平台之间交互
 */
Route::group('develop', function(){
	Route::group('v1',function(){
		Route::post('login','\app\Controller\Develop\V1\DevelopController@login');
		Route::post('data','\app\Controller\Develop\V1\DataController@store');
	});
})->middleware([
	app\Http\Middleware\ApiRequest::class,
	app\Http\Middleware\ApiResponse::class
]);


return [

];
