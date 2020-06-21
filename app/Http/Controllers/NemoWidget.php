<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\TravelPortException;
use App\Http\Requests\FlightsSearchRequest;
use App\Http\Resources\NemoWidget\AirlinesAll;
use App\Http\Resources\NemoWidget\Autocomplete;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Services\NemoWidgetService;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Resources\NemoWidget\FlightsSearchRequest as FlightsSearchRequestResource;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;

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
        $flightsSearchRequestAdapt = $service->flightsSearchRequest($dto);

        return new FlightsSearchRequestResource($flightsSearchRequestAdapt);
    }

    /**
     * @param int $id
     * @param NemoWidgetService $service
     * @return FlightsSearchResults
     * @throws ApiException
     * @throws TravelPortException
     */
    public function flightsSearchResult(int $id, NemoWidgetService $service)
    {
        $FlightsSearchRequestModel = FlightsSearchRequestModel::find($id);

        if(null === $FlightsSearchRequestModel) {
            throw ApiException::getInstanceInvalidId($id);
        }
        $flightsSearchResults = $service->flightsSearchResult($FlightsSearchRequestModel);

        return new FlightsSearchResults($flightsSearchResults);
    }
}