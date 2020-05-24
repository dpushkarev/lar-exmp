<?php

namespace App\Http\Middleware;

use App\Services\NemoWidgetService;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class NemoWidgetRequestCache
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
        $response = $next($request);

        $routeName = Route::getCurrentRoute()->getName();
        $cacheKey = NemoWidgetService::getCacheKey($routeName, $response->requestId, App::getLocale());
        Cache::put($cacheKey, $response->getContent());
        return $response;
    }
}
