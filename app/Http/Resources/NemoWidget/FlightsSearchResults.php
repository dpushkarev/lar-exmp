<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirlineList;
use App\Http\Resources\NemoWidget\Common\AirportList;
use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Http\Resources\NemoWidget\Common\FormData;
use App\Http\Resources\NemoWidget\Common\Request;
use App\Http\Resources\NemoWidget\Common\ResultData;
use App\Http\Resources\NemoWidget\Common\Results;
use App\Models\Airline;
use App\Models\Airport;
use FilippoToso\Travelport\Air\LowFareSearchAsynchRsp;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use Illuminate\Http\Resources\Json\JsonResource;


class FlightsSearchResults extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var  $airSegment typeBaseAirSegment */
        /** @var  $results LowFareSearchAsynchRsp */

        $request = $this->resource->getRequest();
        $results = $this->resource->getResults();
        $countries = collect();
        $cities = collect();
        $airports = collect();
        $airLines = collect();
        $response = collect();

        foreach ($results->getAirSegmentList()->getAirSegment() as $airSegment) {
            $origin = $airSegment->getOrigin();
            $destination = $airSegment->getDestination();
            $carrier = $airSegment->getCarrier();
            $airSegmentData = [
                'FlightNumber' => $airSegment->getFlightNumber(),
            ];

            if(!$airports->has($origin)) {
                $airports->put($origin, Airport::whereCode($origin)->first());
            }

            if(!$airports->has($destination)) {
                $airports->put($destination, Airport::whereCode($destination)->first());
            }

            if(!$airLines->has($carrier)) {
                $airLines->put($carrier, Airline::whereCode($carrier)->first());
            }

            foreach ($airSegment->getFlightDetailsRef() as $flightDetailRef) {
                foreach ($results->getFlightDetailsList()->getFlightDetails() as $flightDetail) {
                    if($flightDetailRef->getKey() === $flightDetail->getKey()) {
                        $airSegmentData['FlightDetails'][] = [
                            'Equipment' => $flightDetail->getEquipment(),
                            'OriginTerminal' => $flightDetail->getOriginTerminal()
                        ];
                    }
                }
            }
            $response->push($airSegmentData);
        }

        foreach ($airports as $airport) {
            $countries = $countries->merge(new Country($airport->country));
            $cities[$airport->city->id] = new City($airport->city);
        }

        return [
            'flights' => [
                'search' => [
                    'formData' => new FormData($request),
                    'request' => new Request($request),
                    'results' => new Results(collect(['results' => $response, 'request' => $request])),
                    'resultData' => new ResultData([])
                ]
            ],
            'guide' => [
                'airlines' => new AirlineList($airLines),
                'airports' => new AirportList($airports),
                'cities' => $cities,
                'countries' => $countries
            ],
            'system' => new System([])
        ];
    }
}