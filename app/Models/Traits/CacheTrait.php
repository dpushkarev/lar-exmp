<?php
namespace App\Models\Traits;

use App\Models\Airline;
use App\Models\VocabularyName;
use Illuminate\Support\Facades\Cache;

/**
 * Trait CacheTrait
 * @uses VocabularyName
 * @uses Airline
 * @package App\Models\Traits
 */
trait CacheTrait
{

    /**
     * @return array Tags
     */
    private static function getCacheTags()
    {
        return static::$cacheTags ?? [];
    }

    /**
     * @return mixed|null
     */
    private static function getCacheMinutes()
    {
        return static::$cacheMinutes ?? null;
    }

    /**
     * @param $method
     * @param $params
     * @return string
     */
    private static function getCacheKey($method, $params)
    {
        $key = str_replace('\\', '_', static::class) . '__' . $method;
        if ($params) {
            $hash = md5(serialize($params));
            $key .= '__' . $hash;
        }

        return $key;
    }

    /**
     * @param $method
     * @param mixed ...$params
     * @return mixed
     */
    public static function cacheStatic($method, ...$params)
    {
        $key = self::getCacheKey($method, $params);

        $ttl = self::getCacheMinutes() * 60;

        if (self::$cacheMinutes) {
            return Cache::remember($key, $ttl, function () use ($method, $params) {
                return static::$method(...$params);
           });
        }

        return Cache::rememberForever($key, function () use ($method, $params) {
            return static::$method(...$params);
        });
    }
}