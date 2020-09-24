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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('/register', 'API\AuthController@register');
        Route::post('/login', 'API\AuthController@login');
        Route::get('/logout', 'API\AuthController@logout')->middleware('auth:api');
        Route::get('/details', 'API\AuthController@details')->middleware('auth:api');
    });
    Route::group(['middleware' => ['auth:api']], function () {
        Route::group(['prefix' => 'board'], function () {
            //board
            Route::post('/', 'API\BoardController@store');
            Route::put('/{board_id}', 'API\BoardController@update');
            Route::delete('/{board_id}', 'API\BoardController@destroy');
            Route::get('/', 'API\BoardController@index');
            Route::get('/{board_id}', 'API\BoardController@show');
            Route::post('/{board_id}/member', 'API\BoardController@storemember');
            Route::delete('/{board_id}/member/{user_id}', 'API\BoardController@deletemember')->middleware('member');
            Route::group(['middleware' => ['boardmember']], function () {
                //list
                Route::post('/{board_id}/list', 'API\ListController@store');
                Route::put('/{board_id}/list/{list_id}', 'API\ListController@update');
                Route::delete('/{board_id}/list/{list_id}', 'API\ListController@destroy');
                Route::post('/{board_id}/list/{list_id}/right', 'API\ListController@right');
                Route::post('/{board_id}/list/{list_id}/left', 'API\ListController@left');
                //card
                Route::post('/{board_id}/list/{list_id}/card', 'API\CardController@store');
                Route::put('/{board_id}/list/{list_id}/card/{card_id}', 'API\CardController@update');
                Route::delete('/{board_id}/list/{list_id}/card/{card_id}', 'API\CardController@destroy');
            });
        });
        Route::group(['middleware' => ['boardmember']], function () {
            Route::post('/card/{card_id}/up', 'API\CardController@up');
            Route::post('/card/{card_id}/down', 'API\CardController@down');
            Route::post('/card/{card_id}/move/{list_id}', 'API\CardController@move');
        });
    });
});
