<?php

//------BEGIN API---------//

Route::get('import-third-party-data','ExternalAPIController@index');
Route::post('user/register','UserController@register');
Route::post('user/auth','UserController@authenticate');
Route::post('user/forgot-password','UserController@forgotPassword');
Route::post('user/confirm-password','UserController@confirmPassword');

Route::get('test','ExternalAPIController@teams');




