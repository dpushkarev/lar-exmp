<?php


namespace App\Http\Middleware\Traits;


use App\Http\Middleware\NemoWidgetCache;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Trait ResponseCache
 * @package App\Http\Middleware\Traits
 * @uses NemoWidgetCache
 */
trait ResponseCache
{
    protected $routeName;

    protected function getMethods($routeName)
    {
        $camelName = Str::camel(str_replace('.', '_', $routeName));
        return [sprintf('%sGetCache', $camelName), sprintf('%sSetCache', $camelName)];
    }

    protected function autocompleteGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AUTOCOMPLETE, $request->q, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function autocompleteSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AUTOCOMPLETE, $request->q, App::getLocale());
        Cache::put($cacheKey, $response->getContent());
    }

    protected function airlinesAllGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AIRLINES_ALL, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function airlinesAllSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AIRLINES_ALL, App::getLocale());
        Cache::put($cacheKey, $response->getContent());
    }

    protected function flightsSearchPostRequestSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_SEARCH_GET_REQUEST, $response->requestId, App::getLocale());
        Cache::put($cacheKey, $response->getContent(), 3600 * 3);
    }

    protected function flightsSearchGetRequestGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_SEARCH_GET_REQUEST, (int)$request->id, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function flightsSearchGetFormDataGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_SEARCH_GET_REQUEST, (int)$request->id, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function flightsSearchPostResultsSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_SEARCH_GET_RESULTS, (int)$request->id, App::getLocale());
        Cache::put($cacheKey, $response->getContent(), 3600 * 24);
    }

    protected function flightsSearchGetResultsGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_SEARCH_GET_RESULTS, (int)$request->id, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function flightsInfoSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_INFO, (int)$request->id, App::getLocale());
        Cache::put($cacheKey, $response->getContent(), 3600 * 24);
    }

    protected function flightsInfoGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_INFO, (int)$request->id, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function flightsRulesSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_RULES, (int)$request->id, App::getLocale());
        Cache::put($cacheKey, $response->getContent(), 3600 * 24);
    }

    protected function flightsRulesGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_RULES, (int)$request->id, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function checkoutSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::CHECKOUT, (int)$request->id, App::getLocale());
        Cache::put($cacheKey, $response->getContent(), 3600 * 3);
    }

    protected function checkoutGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::CHECKOUT, (int)$request->id, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    /**
     * @param $name
     * @param mixed ...$params
     * @return string
     */
    static public function getCacheKey($name, ...$params): string
    {
        $hash = md5(serialize($params));

        return $name . '_' . $hash;
    }
}
