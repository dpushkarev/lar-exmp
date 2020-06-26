<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Exceptions\TravelPortException;
use App\Http\Requests\FlightsSearchRequest;
use App\Http\Resources\NemoWidget\AirlinesAll;
use App\Http\Resources\NemoWidget\Autocomplete;
use App\Http\Resources\NemoWidget\ErrorLog;
use App\Http\Resources\NemoWidget\ErrorSearchId;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Http\Resources\NemoWidget\History;
use App\Models\Error;
use App\Services\NemoWidgetService;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Http\Resources\NemoWidget\FlightsSearchRequest as FlightsSearchRequestResource;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;
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
     * @return ErrorSearchId|FlightsSearchResults
     * @throws TravelPortException
     */
    public function flightsSearchResult(int $id, NemoWidgetService $service)
    {
        $FlightsSearchRequestModel = FlightsSearchRequestModel::find($id);

        if(null === $FlightsSearchRequestModel) {
            return new ErrorSearchId(null);
        }
        $flightsSearchResults = $service->flightsSearchResult($FlightsSearchRequestModel);

        return new FlightsSearchResults($flightsSearchResults);
    }

    public function ErrorLog(Request $request)
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

    public function history()
    {
        return new History(null);
    }

}