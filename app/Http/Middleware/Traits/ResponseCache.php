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
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AUTOCOMPLETE_ROUTE_NAME, $request->q, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function autocompleteSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AUTOCOMPLETE_ROUTE_NAME, $request->q, App::getLocale());
        Cache::put($cacheKey, $response->getContent());
    }

    protected function airlinesAllGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AIRLINES_ALL_ROUTE_NAME, App::getLocale());
        return Cache::get($cacheKey, null);
    }

    protected function airlinesAllSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::AIRLINES_ALL_ROUTE_NAME, App::getLocale());
        Cache::put($cacheKey, $response->getContent());
    }

    protected function flightsSearchPostRequestSetCache($request, $response)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_SEARCH_GET_REQUEST, $response->requestId, App::getLocale());
        Cache::put($cacheKey, $response->getContent());
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
        Cache::put($cacheKey, $response->getContent());
    }

    protected function flightsSearchGetResultsGetCache($request)
    {
        $cacheKey = NemoWidgetCache::getCacheKey(NemoWidgetCache::FLIGHTS_SEARCH_GET_RESULTS, (int)$request->id, App::getLocale());
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

//switch ($routeName) {
//    case 'autocomplete';
//        $cacheKey = NemoWidgetCache::getCacheKey($routeName, $request->q, App::getLocale());
//        break;
//    case 'airlinesAll';
//        $cacheKey = NemoWidgetCache::getCacheKey($routeName, App::getLocale());
//        break;
//    case 'flights.search.post.results';
//    case 'flights.search.get.results';
//        $cacheKey = NemoWidgetCache::getCacheKey($routeName, (int)$request->id, App::getLocale());
//        break;
//    case 'flights.search.get.request';
//    case 'flights.search.get.formData';
//        $cacheKey = NemoWidgetCache::getCacheKey('flights.search.request', (int)$request->id, App::getLocale());
//        break;
//    default:
//        $cacheKey = null;
//}