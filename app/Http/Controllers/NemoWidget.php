<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\TravelPortException;
use App\Http\Requests\FlightsSearchRequest;
use App\Http\Resources\NemoWidget\AirlinesAll;
use App\Http\Resources\NemoWidget\Autocomplete;
use App\Http\Resources\NemoWidget\Common\Guide;
use App\Http\Resources\NemoWidget\ErrorLog;
use App\Http\Resources\NemoWidget\ErrorSearchId;
use App\Http\Resources\NemoWidget\FareRules;
use App\Http\Resources\NemoWidget\FlightsSearchFlightInfo;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Http\Resources\NemoWidget\History;
use App\Models\Airport;
use App\Models\Error;
use App\Models\FlightsSearchResult;
use App\Services\NemoWidgetService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Resources\NemoWidget\FlightsSearchRequest as FlightsSearchRequestResource;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Class NemoWidget
 * @package App\Http\Controllers
 */
class NemoWidget extends BaseController
{

    use ValidatesRequests;

    /**
     * @param NemoWidgetService $service
     * @param $q
     * @param null $iataCode
     * @return Guide
     */
    public function autocomplete(NemoWidgetService $service, $q, $iataCode = null)
    {
        $result = $service->autocomplete($q);

        return new Guide($result);
    }

    public function airlinesAll(NemoWidgetService $service)
    {
        $airlines = $service->airlinesAll();
        $airlines->put("", null);

        $countries = $service->countriesAll();

        $result = collect(['countries' => $countries, 'airlines' => $airlines]);

        return new AirlinesAll($result);
    }

    /**
     * @param $iataCode
     * @return Guide
     * @throws ApiException
     */
    public function airport($iataCode)
    {
        $airline = Cache::rememberForever('airport' . $iataCode, function () use ($iataCode) {
            return Airport::whereCode($iataCode)->first();
        });

        if (!$airline) {
            throw ApiException::getInstanceInvalidId($iataCode);
        }

        $result = collect(['countries' => [$airline->country], 'airlines' => [$airline], 'cities' => [$airline->city]]);

        return new Guide($result);
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
     * @return ErrorSearchId|FlightsSearchResults
     * @throws TravelPortException
     */
    public function flightsSearchResult(int $id, NemoWidgetService $service)
    {
        $FlightsSearchRequestModel = FlightsSearchRequestModel::find($id);

        if (null === $FlightsSearchRequestModel) {
            return new ErrorSearchId(collect());
        }
        $flightsSearchResults = $service->flightsSearchResult($FlightsSearchRequestModel);

        return new FlightsSearchResults($flightsSearchResults);
    }

    public function flightsSearchResultExpired(int $id, NemoWidgetService $service)
    {
        $FlightsSearchRequestModel = FlightsSearchRequestModel::find($id);

        if (null === $FlightsSearchRequestModel) {
            return new ErrorSearchId(collect());
        }

        $flightsSearchResults = $service->flightsSearchResultExpired($FlightsSearchRequestModel);

        return new ErrorSearchId($flightsSearchResults);
    }

    public function errorLog(Request $request)
    {
        try {
            $this->validate($request, [
                'searchId' => 'required',
                'error' => 'required|array',
            ]);
        } catch (ValidationException $validationException) {
            throw ApiException::getInstance($validationException->getMessage(), $validationException->getCode());
        }

        Error::forceCreate([
            'searchId' => $request->get('searchId'),
            'error' => $request->get('error'),
        ]);

        return new ErrorLog("f9ed00a9");
    }

    /**
     * @param $resultId
     * @param NemoWidgetService $service
     * @return ErrorLog|FlightsSearchFlightInfo
     * @throws \App\Exceptions\NemoWidgetServiceException
     */
    public function flightInfo($resultId, NemoWidgetService $service)
    {
        $result = FlightsSearchResult::find($resultId);

        if (null === $result) {
            return new ErrorLog("f9ed00a9");
        }

        $flightInfo = $service->getFlightInfo($result);

        return new FlightsSearchFlightInfo($flightInfo);
    }

    /**
     * @param $id
     * @param NemoWidgetService $service
     * @return ErrorLog|FareRules
     * @throws \App\Exceptions\NemoWidgetServiceException
     */
    public function fareRules($id, NemoWidgetService $service)
    {
        $result = FlightsSearchResult::find($id);

        if (null === $result) {
            return new ErrorLog("f9ed00a9");
        }

        $fareRules = $service->getFareRule($result);

        return new FareRules($fareRules);
    }

    public function history()
    {
        return new History(null);
    }

}