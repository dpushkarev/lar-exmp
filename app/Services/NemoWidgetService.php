<?php


namespace App\Services;


use App\Dto\FlightsSearchRequestDto;
use App\Models\Airline;
use App\Models\Country;
use App\Models\VocabularyName;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;

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

        return $result;
    }

    /**
     * @return mixed
     */
    public function airlinesAll()
    {
        return Airline::cacheStatic('getAll');
    }

    /**
     * @return mixed
     */
    public function countriesAll()
    {
        return Country::cacheStatic('getAll');
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

    public function flightsSearchRequest(FlightsSearchRequestDto $dto)
    {
        $fsrModel = FlightsSearchRequestModel::forceCreate([
            'data' => $dto
        ]);

        $dto->setRequestId($fsrModel->id);
    }

}