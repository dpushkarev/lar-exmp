<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Traits\ResponseCache;
use App\Http\Resources\NemoWidget\System;
use Closure;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Response;

class NemoWidgetCache
{

    const AUTOCOMPLETE = 'autocomplete';
    const AIRLINES_ALL = 'airlines.all';
    const AIRPORT_BY_CODE = 'airport';
    const FLIGHTS_SEARCH_POST_REQUEST = 'flights.search.post.request';
    const FLIGHTS_SEARCH_GET_REQUEST = 'flights.search.get.request';
    const FLIGHTS_SEARCH_GET_FORM_DATA = 'flights.search.get.formData';
    const FLIGHTS_SEARCH_POST_RESULTS = 'flights.search.post.results';
    const FLIGHTS_SEARCH_GET_RESULTS = 'flights.search.get.results';
    const FLIGHTS_INFO = 'flights.info';
    const FLIGHTS_RULES = 'flights.rules';
    const CHECKOUT = 'checkout';

    use ResponseCache;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $routeName = Route::getCurrentRoute()->getName();
        list($getMethod, $setMethod) = $this->getMethods($routeName);
        $cache = null;

        if(method_exists($this, $getMethod)) {
            $cache = $this->{$getMethod}($request);
        }

        if (null !== $cache) {
            $cache = json_decode($cache, true);
            $cache['system'] = (new System([]))->toArray($request);

            return response()->json($cache)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        $response = $next($request);

        if(method_exists($this, $setMethod) && $response->getStatusCode() === Response::HTTP_OK) {
            $this->{$setMethod}($request, $response);
        }

        return $response;
    }
}
