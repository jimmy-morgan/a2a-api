<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/', 'API\UserController@default');

Route::group(['middleware' => ['api']], function () {
    Route::post('auth/login', 'API\UserController@login');
    Route::post('auth/logout', 'API\UserController@logout');

    Route::group(['middleware' => ['verify.session']], function () {
        Route::get('theater', 'API\TheaterController@index');
        Route::get('theater/{uuid}', 'API\TheaterController@show');
        Route::post('theater', 'API\TheaterController@store');
        Route::put('theater/{uuid}', 'API\TheaterController@update');
        Route::delete('theater/{uuid}', 'API\TheaterController@destroy');

        Route::get('movie', 'API\MovieController@index');
        Route::get('movie/{uuid}', 'API\MovieController@show');
        Route::post('movie', 'API\MovieController@store');
        Route::put('movie/{uuid}', 'API\MovieController@update');
        Route::delete('movie/{uuid}', 'API\MovieController@destroy');

        Route::get('movie-sale', 'API\MovieSaleController@index');
        Route::get('movie-sale/{uuid}', 'API\MovieSaleController@show');
        Route::post('movie-sale', 'API\MovieSaleController@store');
        Route::put('movie-sale/{uuid}', 'API\MovieSaleController@update');
        Route::delete('movie-sale/{uuid}', 'API\MovieSaleController@destroy');
    });
});
