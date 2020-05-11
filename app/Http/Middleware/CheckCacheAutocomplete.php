<?php

namespace App\Http\Middleware;

use App\Http\Resources\NemoWidgetSystem;
use App\Services\NemoWidgetService;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\App;

class CheckCacheAutocomplete
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
        $key = NemoWidgetService::getAutocompleteCacheKey($request->q, App::getLocale());
        $cache = Cache::get($key, null);

        if(null !== $cache) {
            $cache = json_decode($cache, true);
            $cache['system'] = (new NemoWidgetSystem([]))->toArray($request);

            return response()->json($cache)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        return $next($request);
    }
}
