<?php

use Bigly\Dropship\Framework\Route;

Route::ajax('access-token', 'CredentialController@getAccessToken');
Route::ajax('sync', 'CredentialController@synProducts');

Route::register();
