<?php


namespace App\Adapters;


use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\FlightsSearchResult;
use App\Services\TravelPortService;
use Carbon\Carbon;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPricingInfo;
use FilippoToso\Travelport\Air\BookingInfo;
use FilippoToso\Travelport\Air\FlightDetails;
use FilippoToso\Travelport\Air\FlightDetailsRef;
use FilippoToso\Travelport\Air\FlightOption;
use FilippoToso\Travelport\Air\LowFareSearchAsynchRsp;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use FilippoToso\Travelport\Air\Option;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use FilippoToso\Travelport\Air\typeTaxInfo;
use Illuminate\Support\Collection;

class TravelPortAdapter
{
    public function LowFareSearch(LowFareSearchRsp $searchRsp): Collection
    {
        /** @var  $airSegment typeBaseAirSegment */
        /** @var  $results LowFareSearchAsynchRsp */

        $countries = collect();
        $cities = collect();
        $airports = collect();
        $airLines = collect();
        $groupsData = collect();
        $airSegmentCollection = collect();
        $airPriceCollection = collect();
        $airSegmentMap = collect();
        $flightGroupsCollection = collect();
        $flightGroups = collect();

        foreach ($searchRsp->getAirSegmentList()->getAirSegment() as $key => $airSegment) {
            $origin = $airSegment->getOrigin();
            $destination = $airSegment->getDestination();
            $carrier = $airSegment->getCarrier();
            $airSegmentKey = sprintf('S%d', $key + 1);
            $airSegmentMap->put($airSegment->getKey(), $airSegmentKey);

            $airSegmentData = [
                'aircraftType' => $airSegment->getEquipment(),
                'arrAirp' => $destination,
                'arrDateTime' => Carbon::parse($airSegment->getArrivalTime())->format('Y-m-d\Th:i:s'),
                'depAirp' => $origin,
                'depDateTime' => Carbon::parse($airSegment->getDepartureTime())->format('Y-m-d\Th:i:s'),
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
                foreach ($searchRsp->getFlightDetailsList()->getFlightDetails() as $flightDetail) {
                    if ($flightDetailRef->getKey() === $flightDetail->getKey()) {
                        $airSegmentData['arrTerminal'] = $flightDetail->getDestinationTerminal();
                        $airSegmentData['depTerminal'] = $flightDetail->getOriginTerminal();
                    }
                }
            }

            $airSegmentCollection->put($airSegmentKey, $airSegmentData);
        }

        /** @var $airPricePoint AirPricePoint */
        foreach ($searchRsp->getAirPricePointList()->getAirPricePoint() as $key => $airPricePoint) {
            $segmentsGroup = [];
            $airPricePointKey = sprintf('P%d', $key + 1);
            $agencyChargeAmount = 100.5;
            $agencyChargeCurrency = $searchRsp->getCurrencyType();

            $airPricePointData = [
                'agencyCharge' => [
                    'amount' => $agencyChargeAmount,
                    'currency' => $agencyChargeCurrency
                ],
                'avlSeatsMin' => '?',
                'flightPrice' => [
                    'amount' => (float)substr($airPricePoint->getTotalPrice(), 3),
                    'currency' => substr($airPricePoint->getTotalPrice(), 0, 3),
                ],
                'id' => $airPricePointKey,
                'originalCurrency' => $searchRsp->getCurrencyType(),
                'priceWithoutPromocode' => '?',
                'privateFareInd' => '?',
                'refundable' => '?',
                'service' => TravelPortService::APPLICATION,
                'tariffsLink' => '?',
                'totalPrice' => [
                    'amount' => substr($airPricePoint->getTotalPrice(), 3) + $agencyChargeAmount,
                    'currency' => substr($airPricePoint->getTotalPrice(), 0, 3),
                ],
                'validatingCompany' => '?',
                'warnings' => []
            ];

            $passengerFares = [];
            /** @var  $airPricingInfo AirPricingInfo */
            foreach ($airPricePoint->getAirPricingInfo() as $airPricingInfo) {
                $passengerFares['count'] = count($airPricingInfo->getPassengerType());
                $passengerFares['type'] = $airPricingInfo->getPassengerType()[0]->Code;

                $passengerFares['baseFare'] = [
                    'amount' => substr($airPricingInfo->getBasePrice(), 3),
                    'currency' => substr($airPricingInfo->getBasePrice(), 0, 3),
                ];

                $passengerFares['equivFare'] = [
                    'amount' => substr($airPricingInfo->getEquivalentBasePrice(), 3),
                    'currency' => substr($airPricingInfo->getEquivalentBasePrice(), 0, 3),
                ];

                $passengerFares['equivFare'] = [
                    'amount' => substr($airPricingInfo->getEquivalentBasePrice(), 3),
                    'currency' => substr($airPricingInfo->getEquivalentBasePrice(), 0, 3),
                ];

                $passengerFares['totalFare'] = [
                    'amount' => substr($airPricingInfo->getTotalPrice(), 3),
                    'currency' => substr($airPricingInfo->getTotalPrice(), 0, 3),
                ];

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

                $passengerFares['tariffs'] = ['?'];
                /** @var  $flightOption FlightOption */
                foreach ($airPricingInfo->getFlightOptionsList()->getFlightOption() as $flightOption) {
                    $legRefKey = (string)$flightOption->getLegRef();
                    /** @var  $option Option */
                    foreach ($flightOption->getOption() as $option) {
                        $optionKey = (string)$option->getKey();
                        /** @var  $bookingInfo BookingInfo */
                        foreach ($option->getBookingInfo() as $bookingInfo) {
                            if (!isset($airPricePointData['passengerFares'])) {
                                $segmentsGroup[$legRefKey][$optionKey][] = $airSegmentMap->get($bookingInfo->getSegmentRef());
                            }
                            $airPricePointData['segmentInfo'][] = [
                                "segNum" => '?',
                                "routeNumber" => '?',
                                "bookingClass" => $bookingInfo->getBookingCode(),
                                "serviceClass" => $bookingInfo->getCabinClass(),
                                "avlSeats" => $bookingInfo->getBookingCount(),
                                "freeBaggage" => ['?'],
                                "minBaggage" => ['?']
                            ];
                        }
                    }
                }

                $airPricePointData['passengerFares'][] = $passengerFares;
            }

            $airPriceCollection->put($airPricePointKey, $airPricePointData);
            $groups = collect();
            foreach (cartesianArray($segmentsGroup) as $group) {
                $groups->add($group);
            }

            $flightGroupsCollection->put($airPricePointKey, $groups);

        }

        foreach ($flightGroupsCollection as $p => $groups) {
            foreach ($groups as  $key => $group) {
                $segmentsCollection = collect();
                foreach ($group as $segments) {
                    $segmentsCollection = $segmentsCollection->merge($segments);
                }

                $flightsSearchResult = FlightsSearchResult::forceCreate([
                    'transaction_id' => $searchRsp->getTransactionId(),
                    'price' => $p,
                    'segments' => $segmentsCollection->toArray(),
                ]);
                $flightGroups->add($flightsSearchResult);
            }

        }

        $groupsData->put('segments', $airSegmentCollection);
        $groupsData->put('prices', $airPriceCollection);
        $results = collect(['groupsData' => $groupsData, 'flightGroups' => $flightGroups]);

        foreach ($airports as $airport) {
            $countries = $countries->merge(new Country($airport->country));
            $cities[$airport->city->id] = new City($airport->city);
        }

        return collect([
            'airlines' => $airLines,
            'airports' => $airports,
            'cities' => $cities,
            'countries' => $countries,
            'results' => $results
        ]);
    }
}



//
//$testArray = [
//    'P1' => [
//        'Mos-Milan' => Array
//        (
//            0 => Array
//            (
//                0 => 'Mos-Milan 1.1',
//                1 => 'Mos-Milan 1.2',
//            ),
//            1 => Array
//            (
//                0 => 'Mos-Milan 2.1',
//                1 => 'Mos-Milan 2.2',
//                2 => 'Mos-Milan 2.3',
//            ),
//            2 => Array
//            (
//                0 => 'Mos-Milan 3',
//            ),
//            3 => Array
//            (
//                0 => 'Mos-Milan 4',
//            ),
//
//        ),
//        'Milan-Bel' => Array
//        (
//            0 => Array
//            (
//                0 => 'Milan-Bel 1'
//            ),
//            1 => Array
//            (
//                0 => 'Milan-Bel 2.1',
//                1 => 'Milan-Bel 2.2',
//            ),
//            2 => Array
//            (
//                0 => 'Milan-Bel 3.1',
//                1 => 'Milan-Bel 3.2',
//                2 => 'Milan-Bel 3.3',
//                3 => 'Milan-Bel 3.4',
//            )
//        ),
//        'Bel-Milan' => Array
//        (
//            0 => Array
//            (
//                0 => 'Bel-Milan 1.1',
//                1 => 'Bel-Milan 1.2'
//            ),
//            1 => Array
//            (
//                0 => 'Bel-Milan 2',
//            ),
//            2 => Array
//            (
//                0 => 'Bel-Milan 3.1',
//                3 => 'Bel-Milan 3.2',
//            )
//        ),
//        'Milan-Mos' => Array
//        (
//            0 => Array
//            (
//                0 => 'Milan-Mos 1',
//            ),
//            1 => Array
//            (
//                0 => 'Milan-Mos 2.1',
//                1 => 'Milan-Mos 2.2',
//                2 => 'Milan-Mos 2.3',
//            ),
//        ),
//    ],
//];