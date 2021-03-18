<?php

Route::group('thirdparty', function(){
	Route::group('/v1/agent',function(){
		Route::get('', '\app\Controller\Agent\V1\AgentController@index');
		Route::post('','\app\Controller\Agent\V1\AgentController@store');
		Route::put('','\app\Controller\Agent/V1\AgentController@update');
		Route::delete('','\app\Controller\Agent\V1\AgentController@destroy');
		Route::get('show', '\app/Controller\Agent\V1\AgentController@show');
	});

});


return [

];
