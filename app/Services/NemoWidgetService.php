<?php


namespace App\Services;


use App\Models\Airport;
use App\Models\City;
use App\Models\VocabularyName;

class NemoWidgetService
{
    public function autocomplete($q, $iataCode = null)
    {
        $result = VocabularyName::cacheStatic('getByName', $q);

        if (null !== $iataCode) {
            $result = $result->reject(function ($element) use ($iataCode) {
                return $element->nameable->code === $iataCode;
            });
        }

        $resultGroup = $result->groupBy('nameable_type');

        return $resultGroup->has(Airport::class) ?
            $resultGroup->get(Airport::class) :
            $resultGroup->get(City::class);
    }

    /**
     * @param mixed ...$params
     * @return string
     */
    static public function getAutocompleteCacheKey(...$params): string
    {
        $hash = md5(serialize($params));
        $key = substr(strrchr(__CLASS__, "\\"), 1);   ;

        return $key . '_' . $hash;
    }
}