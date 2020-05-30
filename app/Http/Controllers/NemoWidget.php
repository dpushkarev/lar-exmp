<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\FlightsSearchRequest;
use App\Http\Resources\NemoWidget\AirlinesAll;
use App\Http\Resources\NemoWidget\Autocomplete;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Services\NemoWidgetService;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Resources\NemoWidget\FlightsSearchRequest as FlightsSearchRequestResource;
use Illuminate\Support\Facades\Cache;

/**
 * Class NemoWidget
 * @package App\Http\Controllers
 */
class NemoWidget extends BaseController
{

    /**
     * @param NemoWidgetService $service
     * @param $q
     * @param null $iataCode
     * @return Autocomplete
     */
    public function autocomplete(NemoWidgetService $service, $q, $iataCode = null)
    {
        $result = $service->autocomplete($q);

        return new Autocomplete($result);
    }

    public function airlinesAll(NemoWidgetService $service)
    {
        $airlines = $service->airlinesAll();
        $countries = $service->countriesAll();

        $result = collect(['countries' => $countries, 'airlines' => $airlines]);

        return new AirlinesAll($result);
    }

    public function flightsSearchRequest(FlightsSearchRequest $request, NemoWidgetService $service)
    {
        $dto = $request->getFlightsSearchRequestDto();
        $service->flightsSearchRequest($dto);

        return new FlightsSearchRequestResource($dto);
    }

    /**
     * @param int $id
     * @param NemoWidgetService $service
     * @return FlightsSearchResults
     * @throws ApiException
     * @throws \App\Exceptions\TravelPortException
     */
    public function flightsSearchResult(int $id, NemoWidgetService $service)
    {
        try {
            $results = Cache::rememberForever('result'. $id, function () use ($service, $id) {
                return $service->flightsSearchResult($id);
            });
            return new FlightsSearchResults($results);
        } catch (ApiException $exception) {
            throw $exception;
        }
    }
}