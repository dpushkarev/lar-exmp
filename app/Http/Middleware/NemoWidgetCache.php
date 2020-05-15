<?php

namespace App\Http\Middleware;

use App\Http\Resources\NemoWidgetSystem;
use App\Services\NemoWidgetService;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class NemoWidgetCache
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
        $routeName = Route::getCurrentRoute()->getName();

        switch ($routeName) {
            case 'autocomplete';
                $cacheKey = NemoWidgetService::getCacheKey($routeName, $request->q, App::getLocale());
                break;
            case 'airlinesAll';
                $cacheKey = NemoWidgetService::getCacheKey($routeName, App::getLocale());
                break;
            default:
                $cacheKey = null;
        }

        $cache = Cache::get($cacheKey, null);

        if(null !== $cache) {
            $cache = json_decode($cache, true);
            $cache['system'] = (new NemoWidgetSystem([]))->toArray($request);

            return response()->json($cache)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        $response = $next($request);

        if(null !== $cacheKey && null === $response->exception) {
            Cache::put($cacheKey, $response->getContent());
        }

        return $response;
    }
}
