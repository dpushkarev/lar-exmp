<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirportList;
use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Http\Resources\NemoWidget\Common\Flights;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\City as CityModel;
use App\Models\Airport as AirportModel;

class FlightsSearchRequest extends JsonResource
{

    public static $wrap;

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
            'flights' => new Flights($this->resource),
            'guide' => [
                'airports' => new AirportList($airports),
                'cities' => $cities,
                'countries' => $countries
            ],
            'system' => new System([])
        ];
    }
    public function withResponse($request, $response)
    {
        $response->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        $response->requestId = $this->resource->getRequestId();
    }

}