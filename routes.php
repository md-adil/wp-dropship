<?php

use Bigly\Dropship\Framework\Route;

Route::ajax('store-credential', 'CredentialController@store');
Route::ajax('sync', 'CredentialController@synProducts');

Route::register();
