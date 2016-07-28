<?php

//------BEGIN API---------//

Route::get('import-third-party-data','ExternalAPIController@index');
Route::post('user/register','UserController@register');
Route::post('user/auth','UserController@authenticate');
Route::post('user/forgot-password','UserController@forgotPassword');
Route::post('user/confirm-password','UserController@confirmPassword');
Route::get('user/verify-forgot-password-token/{token}','UserController@verifyForgotPasswordToken');

Route::get('test','ExternalAPIController@teams');

Route::get('latestMatches','ScheduleController@upcomingMatches');




