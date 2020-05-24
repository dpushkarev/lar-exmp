<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\ApiException;

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

//Route::post('/flights/search/request', 'TravelPort@search');
Route::middleware(['nemo.widget.request.cache'])->post('/flights/search/request', 'NemoWidget@flightsSearchRequest')->name('flights.search.request');

Route::middleware(['nemo.widget.cache'])->group(function () {
    Route::name('autocomplete')->group(function () {
        Route::get('/guide/autocomplete/iata/{q}/dep', 'NemoWidget@autocomplete')->where('q', '.*');
        Route::get('/guide/autocomplete/iata/{q}/dep/{iataCode}', 'NemoWidget@autocomplete')->where('iataCode', '[A-Z]{3}');
        Route::get('/guide/autocomplete/iata/{q}/arr', 'NemoWidget@autocomplete')->where('q', '.*');
        Route::get('/guide/autocomplete/iata/{q}/arr/{iataCode}', 'NemoWidget@autocomplete')->where('iataCode', '[A-Z]{3}');
    });

    Route::get('/flights/search/formData/{id}', function($id){
        throw ApiException::getInstanceInvalidId($id);
    })->where('id', '\d+')->name('flights.search.get.formData');

    Route::get('/flights/search/request/{id}', function($id){
        throw ApiException::getInstanceInvalidId($id);
    })->where('id', '\d+')->name('flights.search.get.request');

    Route::post('/flights/search/results/{id}', 'NemoWidget@flightsSearchResult')->where('id', '\d+')->name('flights.search.results');


    Route::get('/guide/airlines/all', 'NemoWidget@airlinesAll')->name('airlinesAll');
    Route::get('/guide/airports/nearest', function () {
        return '{
           "guide":{
              "countries":{
                 "RS":{
                    "code":"RS",
                    "name":"Serbia",
                    "nameEn":"Serbia"
                 }
              },
              "cities":{
                 "350":{
                    "IATA":"BEG",
                    "name":"Belgrade",
                    "nameEn":"Belgrade",
                    "countryCode":"RS",
                    "id":350
                 }
              },
              "airports":{
                 "BEG":{
                    "IATA":"BEG",
                    "cityId":350,
                    "isAggregation":true,
                    "airportRating":"0",
                    "baseType":"airport",
                    "properNameEn":null,
                    "properName":null,
                    "name":"Beograd",
                    "nameEn":"Belgrade",
                    "countryCode":"RS"
                 }
              },
              "nearestAirport":"BEG"
           },
           "system":{
              "info":{
                 "response":{
                    "timestamp":1589398827.693,
                    "responseTime":0.001
                 },
                 "user":{
                    "userID":1,
                    "agencyID":1,
                    "status":"guest",
                    "isB2B":false,
                    "settings":{
                       "currentLanguage":"en",
                       "currentCurrency":"EUR",
                       "agencyCurrency":"EUR",
                       "agencyCountry":"RS",
                       "googleMapsApiKey":"",
                       "googleMapsClientId":"",
                       "showFullFlightsResults":"false"
                    }
                 }
              }
           }
        }';
    });

    Route::any('{some}', function () {
        throw new NotFoundHttpException('Api not found');
    })->where('some', '.*');
});




