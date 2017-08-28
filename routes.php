<?php

use Bigly\Dropship\Framework\Route;

Route::get('credentials', 'CredentialController@index')
    ->icon('settings')->name('Manage Credential')->title('Manage');

Route::post('credentials', 'CredentialController@store');

Route::register();
