<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirportList;
use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Http\Resources\NemoWidget\Common\Flights;
use App\Http\Resources\NemoWidget\Common\FormData;
use App\Http\Resources\NemoWidget\Common\Request;
use App\Http\Resources\NemoWidget\Common\Results;
use App\Models\City as CityModel;
use App\Models\Airport as AirportModel;

class FlightsSearchRequest extends AbstractResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $countries = collect();
        $cities = collect();
        $airports = collect();

        foreach ($this->resource->getSegments() as $segment) {
            $departure = $segment['departure'] ?? null;
            $iataCodeArr = $segment['arrival'] ?? null;

            if (!is_null($departure) && !$airports->has($departure['IATA'])) {
                $model = $departure['isCity'] ? CityModel::class : AirportModel::class;
                $airports->put($departure['IATA'], $model::whereCode($departure['IATA'])->first());
            }

            if (!is_null($iataCodeArr) && !$airports->has($iataCodeArr['IATA'])) {
                $model = $iataCodeArr['isCity'] ? CityModel::class : AirportModel::class;
                $airports->put($iataCodeArr['IATA'], $model::whereCode($iataCodeArr['IATA'])->with(['city', 'country'])->first());
            }
        }

        foreach ($airports as $airport) {
            $countries = $countries->merge(new Country($airport->country));
            $cities[$airport->city->id] = new City($airport->city);
        }

        return [
            'flights' => [
                'search' => [
                    'formData' => new FormData($this->resource),
                    'request' => new Request($this->resource),
                    'results' => new Results(collect(['request' => $this->resource]))
                ]
            ],
            'guide' => [
                'airports' => new AirportList($airports),
                'cities' => $cities,
                'countries' => $countries
            ],
        ];
    }

    public function withResponse($request, $response)
    {
        parent::withResponse($request, $response);
        $response->requestId = $this->resource->getRequestId();
    }

}