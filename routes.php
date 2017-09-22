<?php

use Bigly\Dropship\Framework\Route;

Route::ajax('access-token', 'CredentialController@getAccessToken');
Route::ajax('sync', 'SyncController@sync');
Route::ajax('test', 'SyncController@test');

Route::register();
