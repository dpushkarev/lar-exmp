<?php

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

Route::post('/flights/search/request', 'TravelPort@search');

Route::middleware(['check.cache.autocomplete', 'set.cache.autocomplete'])->group(function () {
    Route::get('/guide/autocomplete/iata/{q}/dep', 'NemoWidget@autocomplete')->where('q', '.*');
    Route::get('/guide/autocomplete/iata/{q}/dep/{iataCode}', 'NemoWidget@autocomplete')->where('iataCode', '[A-Z]{3}');
    Route::get('/guide/autocomplete/iata/{q}/arr', 'NemoWidget@autocomplete')->where('q', '.*');
    Route::get('/guide/autocomplete/iata/{q}/arr/{iataCode}', 'NemoWidget@autocomplete')->where('iataCode', '[A-Z]{3}');
});


