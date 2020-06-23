<?php


namespace App\Adapters;


use App\Http\Resources\NemoWidget\Common\City;
use App\Http\Resources\NemoWidget\Common\Country;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\FlightsSearchResult;
use App\Services\TravelPortService;
use Carbon\Carbon;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPricingInfo;
use FilippoToso\Travelport\Air\BookingInfo;
use FilippoToso\Travelport\Air\FareInfo;
use FilippoToso\Travelport\Air\FlightDetails;
use FilippoToso\Travelport\Air\FlightDetailsRef;
use FilippoToso\Travelport\Air\FlightOption;
use FilippoToso\Travelport\Air\LowFareSearchAsynchRsp;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use FilippoToso\Travelport\Air\Option;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use FilippoToso\Travelport\Air\typeTaxInfo;
use Illuminate\Support\Collection;

class TravelPortAdapter extends NemoWidgetAbstractAdapter
{
    public function LowFareSearchAdapt(LowFareSearchRsp $searchRsp): Collection
    {
        /** @var  $airSegment typeBaseAirSegment */
        /** @var  $results LowFareSearchRsp */

        $countries = collect();
        $cities = collect();
        $airports = collect();
        $airLines = collect();
        $aircrafts = collect();
        $groupsData = collect();
        $airSegmentCollection = collect();
        $airPriceCollection = collect();
        $airSegmentMap = collect();
        $flightGroupsCollection = collect();
        $flightGroups = collect();
        $fareInfoMap = collect();

        foreach ($searchRsp->getAirSegmentList()->getAirSegment() as $key => $airSegment) {
            $origin = $airSegment->getOrigin();
            $destination = $airSegment->getDestination();
            $carrier = $airSegment->getCarrier();
            $aircraftType = $airSegment->getEquipment();
            $airSegmentKey = sprintf('S%d', $key + 1);
            $airSegmentMap->put($airSegment->getKey(), collect([
                'segmentKey' => $airSegmentKey,
                'segment' => $airSegment,
            ]));

            $airSegmentData = [
                'aircraftType' => $aircraftType,
                'arrAirp' => $destination,
                'arrDateTime' => Carbon::parse($airSegment->getArrivalTime())->format('Y-m-d\TH:i:s'),
                'depAirp' => $origin,
                'depDateTime' => Carbon::parse($airSegment->getDepartureTime())->format('Y-m-d\TH:i:s'),
                'eTicket' => $airSegment->getETicketability() === 'Yes' ? true : false,
                'flightNumber' => $airSegment->getFlightNumber(),
                'flightTime' => $airSegment->getFlightTime(),
                'id' => $airSegmentKey,
                'isCharter' => false,
                'isLowCost' => false,
                'marketingCompany' => $carrier,
                'number' => '?',
                'operatingCompany' => (null !== $airSegment->getCodeshareInfo()) ? $airSegment->getCodeshareInfo()->getOperatingCarrier() : null,
                'routeNumber' => $airSegment->getGroup(),
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

            if (!$aircrafts->has($aircraftType)) {
                $aircrafts->put($aircraftType, Aircraft::whereCode($aircraftType)->first());
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

        /** @var  $fareInfo FareInfo */
        foreach ($searchRsp->getFareInfoList()->getFareInfo() as $fareInfo) {
            $fareInfoMap->put($fareInfo->getKey(), $fareInfo);
        }

        /** @var $airPricePoint AirPricePoint */
        foreach ($searchRsp->getAirPricePointList()->getAirPricePoint() as $key => $airPricePoint) {
            $segmentsGroup = [];
            $segmentFareMap = [];
            $airPricePointKey = sprintf('P%d', $key + 1);
            $agencyChargeAmount = 100.5;
            $agencyChargeCurrency = $searchRsp->getCurrencyType();

            $airPricePointData = [
                'agencyCharge' => [
                    'amount' => $agencyChargeAmount,
                    'currency' => $agencyChargeCurrency
                ],
                'flightPrice' => [
                    'amount' => (float)substr($airPricePoint->getTotalPrice(), 3),
                    'currency' => substr($airPricePoint->getTotalPrice(), 0, 3),
                ],
                'id' => $airPricePointKey,
                'originalCurrency' => $searchRsp->getCurrencyType(),
                'priceWithoutPromocode' => null,
                'privateFareInd' => false,
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
                            $segmentFareHash = md5($bookingInfo->getFareInfoRef() . $bookingInfo->getSegmentRef());
                            if (!isset($airPricePointData['passengerFares'])) {
                                $segmentsGroup[$legRefKey][$optionKey][] = $airSegmentMap->get($bookingInfo->getSegmentRef())->get('segmentKey');
                            }
                            if (!isset($segmentFareMap[$segmentFareHash])) {
                                $airPricePointData['segmentInfo'][] = [
                                    "segNum" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('segmentKey'),
                                    "routeNumber" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('segment')->getGroup(),
                                    "bookingClass" => $bookingInfo->getBookingCode(),
                                    "serviceClass" => $bookingInfo->getCabinClass(),
                                    "avlSeats" => $bookingInfo->getBookingCount(),
                                    "freeBaggage" => $bookingInfo->getFareInfoRef(),
                                    "minBaggage" => []
                                ];

                                $segmentFareMap[$segmentFareHash] = $segmentFareHash;
                            }
                        }
                    }
                }

                $airPricePointData['passengerFares'][] = $passengerFares;
            }

            $avlSeatsMin = [];
            foreach ($airPricePointData['segmentInfo'] as &$segmentInfo) {
                $avlSeatsMin[] = $segmentInfo['avlSeats'];
                $fareInfoRef = $segmentInfo['freeBaggage'];
                $segmentInfo['freeBaggage'] = [];
                $fareInfo = $fareInfoMap->get($fareInfoRef);
                foreach ($airPricePointData['passengerFares'] as $passengerFare) {
                    $segmentInfo['freeBaggage'][] = [
                        'passtype' => $passengerFare['type'],
                        'value' => ($fareInfo->getPassengerTypeCode() === $passengerFare['type']) ? $fareInfo->getBaggageAllowance()->getNumberOfPieces() : null,
                        "measurement" => "pc",
                    ];
                }
            }

            $airPricePointData['avlSeatsMin'] = min($avlSeatsMin);

            $airPriceCollection->put($airPricePointKey, $airPricePointData);
            $groups = collect();
            foreach (cartesianArray($segmentsGroup) as $group) {
                $groups->add($group);
            }

            $flightGroupsCollection->put($airPricePointKey, $groups);

        }

        foreach ($flightGroupsCollection as $p => $groups) {
            foreach ($groups as $key => $group) {
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
            'aircrafts' => $aircrafts,
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