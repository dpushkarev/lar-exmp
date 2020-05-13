<?php

namespace App\Http\Middleware;

use App\Services\NemoWidgetService;
use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

class SetCacheAutocomplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * @var $response Response
         */
        $response = $next($request);

        if(null === $response->exception) {
            $key = NemoWidgetService::getAutocompleteCacheKey($request->q, App::getLocale());
            Cache::put($key, $response->getContent());
        }

        return $response;
    }
}
