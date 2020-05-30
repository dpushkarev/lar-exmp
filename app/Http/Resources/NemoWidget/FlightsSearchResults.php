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
use App\Services\TravelPortService;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPricingInfo;
use FilippoToso\Travelport\Air\BookingInfo;
use FilippoToso\Travelport\Air\FareInfo;
use FilippoToso\Travelport\Air\FareInfoRef;
use FilippoToso\Travelport\Air\FlightDetails;
use FilippoToso\Travelport\Air\FlightDetailsRef;
use FilippoToso\Travelport\Air\FlightOption;
use FilippoToso\Travelport\Air\LowFareSearchAsynchRsp;
use FilippoToso\Travelport\Air\Option;
use FilippoToso\Travelport\Air\PassengerType;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use FilippoToso\Travelport\Air\typeTaxInfo;

class FlightsSearchResults extends AbstractResource
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
        $groupsData = collect();
        $airSegmentCollection = collect();
        $airPriceCollection = collect();
        $airSegmentMap = collect();

//        echo "<pre>";
//        print_r($results);die;

        foreach ($results->getAirSegmentList()->getAirSegment() as $key => $airSegment) {
            $origin = $airSegment->getOrigin();
            $destination = $airSegment->getDestination();
            $carrier = $airSegment->getCarrier();
            $airSegmentKey = sprintf('S%d', $key + 1);
            $airSegmentMap->put($airSegment->getKey(), $airSegmentKey);

            $airSegmentData = [
                'aircraftType' => $airSegment->getEquipment(),
                'arrAirp' => $destination,
                'arrDateTime' => $airSegment->getArrivalTime(),
                'depAirp' => $origin,
                'depDateTime' => $airSegment->getDepartureTime(),
                'eTicket' => $airSegment->getETicketability(),
                'flightNumber' => $airSegment->getFlightNumber(),
                'flightTime' => $airSegment->getFlightTime(),
                'id' => $airSegmentKey,
                'isCharter' => false,
                'isLowCost' => false,
                'marketingCompany' => null,
                'number' => 0,
                'operatingCompany' => $carrier,
                'routeNumber' => 0,
                'stopPoints' => null
            ];


            if (!$airports->has($origin)) {
                $airports->put($origin, Airport::whereCode($origin)->first());
            }

            if (!$airports->has($destination)) {
                $airports->put($destination, Airport::whereCode($destination)->first());
            }

            if (!$airLines->has($carrier)) {
                $airLines->put($carrier, Airline::whereCode($carrier)->first());
            }

            /** @var  $flightDetail  FlightDetails */
            /** @var  $flightDetailRef  FlightDetailsRef */
            foreach ($airSegment->getFlightDetailsRef() as $flightDetailRef) {
                foreach ($results->getFlightDetailsList()->getFlightDetails() as $flightDetail) {
                    if ($flightDetailRef->getKey() === $flightDetail->getKey()) {
                        $airSegmentData['arrTerminal'] = $flightDetail->getDestinationTerminal();
                        $airSegmentData['depTerminal'] = $flightDetail->getOriginTerminal();
                    }
                }
            }

            $airSegmentCollection->put($airSegmentKey, $airSegmentData);
        }

        /** @var $airPricePoint AirPricePoint */
        foreach ($results->getAirPricePointList()->getAirPricePoint() as $key => $airPricePoint) {
            $airPricePointKey = sprintf('P%d', $key + 1);
            $airPricePointData = [
                'agencyCharge' => [
                    'amount' => 0,
                    'currency' => $results->getCurrencyType()
                ],
                'avlSeatsMin' => '?',
                'flightPrice' => [
                    'amount' => '?',
                    'currency' => '?',
                ],
                'id' => $airPricePointKey,
                'originalCurrency' => $results->getCurrencyType(),
                'priceWithoutPromocode' => '?',
                'privateFareInd' => '?',
                'refundable' => '?',
                'service' => TravelPortService::GALILEO_PROVIDER_ID,
                'tariffsLink' => '?',
                'totalPrice' => [
                    'amount' => substr($airPricePoint->getTotalPrice(), 3),
                    'currency' => substr($airPricePoint->getTotalPrice(), 0, 3),
                ],
                'validatingCompany' => '?',
                'warnings' => []
            ];

            $segmentInfo = [];
            $fareInfoKeys = [];
            /** @var  $airPricingInfo AirPricingInfo */
            foreach ($airPricePoint->getAirPricingInfo() as $airPricingInfo) {
                $passengerFares = [];
                $passengerFares['count'] = count($airPricingInfo->getPassengerType());
                $passengerFares['type'] = $airPricingInfo->getPassengerType()[0]->Code;

                /** @var  $passengerType PassengerType */
                foreach ($airPricingInfo->getPassengerType() as $passengerType) {
                    continue;
                }

                /** @var  $fateInfoRef FareInfoRef */
                foreach ($airPricingInfo->getFareInfoRef() as $fateInfoRef) {
                    /** @var  $fareInfo FareInfo */
                    foreach ($results->getFareInfoList()->getFareInfo() as $fareInfo) {
                        if ($fareInfo->getKey() === $fateInfoRef->getKey()) {
                            $passengerFares['baseFare']['amount'] = substr($fareInfo->getAmount(), 3);
                            $passengerFares['baseFare']['currency'] = substr($results->getCurrencyType(), 0, 3);
                        }
                    }
                }

                $passengerFares['equivFare']['amount'] = substr($airPricingInfo->getEquivalentBasePrice(), 3);
                $passengerFares['equivFare']['currency'] = substr($airPricingInfo->getEquivalentBasePrice(), 0, 3);

                /** @var  $typeTaxInfo typeTaxInfo */
                $passengerFares['taxes'] = [];
                if ($airPricingInfo->getTaxInfo()) {
                    foreach ($airPricingInfo->getTaxInfo() as $typeTaxInfo) {
                        $passengerFares['taxes'][] = [
                            $typeTaxInfo->getCategory() => [
                                'amount' => substr($typeTaxInfo->getAmount(), 3),
                                'currency' => substr($typeTaxInfo->getAmount(), 0, 3)
                            ]
                        ];
                    }
                }

                $passengerFares['tariffs'] = [];
                /** @var  $flightOption FlightOption */
                foreach ($airPricingInfo->getFlightOptionsList()->getFlightOption() as $flightOption) {
                    /** @var  $option Option */
                    foreach ($flightOption->getOption() as $option) {
                        /** @var  $bookingInfo BookingInfo */
                        foreach ($option->getBookingInfo() as $bookingInfo) {
                            $passengerFares['tariffs'][] = [
                                "code" => $bookingInfo->getBookingCode(),
                                "features" => [],
                                "segNum" => $airSegmentMap->get($bookingInfo->getSegmentRef()),
                                "routeNumber" => '?',
                                "serviceClass" => $bookingInfo->getCabinClass()
                            ];
                            $fareSegmentKey = md5($bookingInfo->getFareInfoRef().$bookingInfo->getSegmentRef());
                            if (!isset($fareInfoKeys[$fareSegmentKey])) {
                                /** @var  $fateInfoRef FareInfoRef */
                                foreach ($results->getFareInfoList()->getFareInfo() as $fareInfo) {
                                    if ($fareInfo->getKey() === $bookingInfo->getFareInfoRef()) {
                                        $segmentInfo[] = [
                                            "segNum" => $airSegmentMap->get($bookingInfo->getSegmentRef()),
                                            "routeNumber" => '?',
                                            "bookingClass" => "?",
                                            "serviceClass" => $bookingInfo->getCabinClass(),
                                            "avlSeats" => $bookingInfo->getBookingCount(),
                                            "freeBaggage" => [
                                                [
                                                    "passtype" => $fareInfo->getPassengerTypeCode(),
                                                    "value" => $fareInfo->getBaggageAllowance()->getMaxWeight()->getValue(),
                                                    "measurement" =>  $fareInfo->getBaggageAllowance()->getMaxWeight()->getUnit()
                                                ]
                                            ],
                                            "minBaggage" => ['?']
                                        ];
                                        $fareInfoKeys[$fareSegmentKey] = $fareSegmentKey;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                $airPricePointData['passengerFares'][] = $passengerFares;
            }

            $airPricePointData['segmentInfo'] = $segmentInfo;
            $airPriceCollection->put($airPricePointKey, $airPricePointData);

        }

        $groupsData->put('segments', $airSegmentCollection);
        $groupsData->put('prices', $airPriceCollection);
        $response = collect(['groupsData' => $groupsData]);

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
        ];
    }
}