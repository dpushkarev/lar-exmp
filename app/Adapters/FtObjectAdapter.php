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
use FilippoToso\Travelport\Air\AirPriceResult;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\AirPricingInfo;
use FilippoToso\Travelport\Air\AirPricingSolution;
use FilippoToso\Travelport\Air\BagDetails;
use FilippoToso\Travelport\Air\BaggageAllowanceInfo;
use FilippoToso\Travelport\Air\BaggageRestriction;
use FilippoToso\Travelport\Air\BookingInfo;
use FilippoToso\Travelport\Air\CarryOnAllowanceInfo;
use FilippoToso\Travelport\Air\CarryOnDetails;
use FilippoToso\Travelport\Air\FareInfo;
use FilippoToso\Travelport\Air\FareNote;
use FilippoToso\Travelport\Air\FareRule;
use FilippoToso\Travelport\Air\FareRuleLong;
use FilippoToso\Travelport\Air\FareSurcharge;
use FilippoToso\Travelport\Air\FlightDetails;
use FilippoToso\Travelport\Air\FlightDetailsRef;
use FilippoToso\Travelport\Air\FlightOption;
use FilippoToso\Travelport\Air\HostToken;
use FilippoToso\Travelport\Air\ImageLocation;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use FilippoToso\Travelport\Air\Option;
use FilippoToso\Travelport\Air\OptionalService;
use FilippoToso\Travelport\Air\ServiceData;
use FilippoToso\Travelport\Air\TextInfo;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use FilippoToso\Travelport\Air\typeFarePenalty;
use FilippoToso\Travelport\Air\typeTaxInfo;
use FilippoToso\Travelport\Air\typeTextElement;
use FilippoToso\Travelport\Air\URLInfo;
use Illuminate\Support\Collection;

class FtObjectAdapter extends NemoWidgetAbstractAdapter
{
    protected $FareAttributes = [
        1 => [
            'code' => 'baggage',
            'feature' => 'baggage',
        ],
        2 => [
            'code' => 'carry_on',
            'feature' => 'baggage',
        ],
        3 => [
            'code' => 'exchangeable',
            'feature' => 'refunds',
        ],
        4 => [
            'code' => 'refundable',
            'feature' => 'refunds',
        ],
        5 => [
            'code' => 'vip_service',
            'feature' => 'misc',
        ],
        6 => [
            'code' => 'vip_service',
            'feature' => 'misc',
        ],
        7 => [
            'code' => 'vip_service',
            'feature' => 'misc',
        ],
    ];

    protected $indicators = [
        'I' => 'Included in the fare',
        'A' => 'Available for a charge',
        'N' => 'Not offered',
    ];

    const AGENCY_CHARGE_AMOUNT = 495;
    const AGENCY_CHARGE_CURRENCY = 'RSD';

    const CACHE_AMOUNT = 795;
    const CACHE_CURRENCY = 'RSD';

    const INTESA_COMMISSION = 9;
    const PAYPAL_COMMISSION = 2.9;
    const PAYPAL_COMMISSION_FIX = 30;

    /**
     * @param LowFareSearchRsp $searchRsp
     * @param int $requestId
     * @return Collection
     */
    public function LowFareSearchAdapt(LowFareSearchRsp $searchRsp, int $requestId): Collection
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
                'key' => $key
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
            $countOfPassengers = 0;

            $airPricePointData = [
                'flightPrice' => [
                    'amount' => (float)substr($airPricePoint->getTotalPrice(), 3),
                    'currency' => substr($airPricePoint->getTotalPrice(), 0, 3),
                ],
                'id' => $airPricePointKey,
                'originalCurrency' => $searchRsp->getCurrencyType(),
                'priceWithoutPromocode' => null,
                'privateFareInd' => false,
                'service' => TravelPortService::APPLICATION,
                'tariffsLink' => '?',
                'validatingCompany' => '?',
                'warnings' => []
            ];

            $passengerFares = [];
            /** @var  $airPricingInfo AirPricingInfo */
            foreach ($airPricePoint->getAirPricingInfo() as $airPricingInfo) {
                $airPricePointData['refundable'] = $airPricingInfo->getRefundable();
                $passengerFares['count'] = count($airPricingInfo->getPassengerType());
                $passengerFares['type'] = $airPricingInfo->getPassengerType()[0]->Code;
                $countOfPassengers += $passengerFares['count'];

                $passengerFares['baseFare'] = [
                    'amount' => (float) substr($airPricingInfo->getBasePrice(), 3),
                    'currency' => substr($airPricingInfo->getBasePrice(), 0, 3),
                ];

                $passengerFares['equivFare'] = [
                    'amount' => (float) substr($airPricingInfo->getEquivalentBasePrice(), 3),
                    'currency' => substr($airPricingInfo->getEquivalentBasePrice(), 0, 3),
                ];

                $passengerFares['equivFare'] = [
                    'amount' => (float) substr($airPricingInfo->getEquivalentBasePrice(), 3),
                    'currency' => substr($airPricingInfo->getEquivalentBasePrice(), 0, 3),
                ];

                $passengerFares['totalFare'] = [
                    'amount' => (float) substr($airPricingInfo->getTotalPrice(), 3),
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
                                    "segNum" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('key'),
                                    "routeNumber" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('segment')->getGroup(),
                                    "bookingClass" => $bookingInfo->getBookingCode(),
                                    "serviceClass" => $bookingInfo->getCabinClass(),
                                    "avlSeats" => $bookingInfo->getBookingCount(),
                                    "freeBaggage" => $bookingInfo->getFareInfoRef(),
                                    "minBaggage" => []
                                ];

                                $features = [];
                                if ($getFareAttributes = $fareInfoMap->get($bookingInfo->getFareInfoRef())->getFareAttributes()) {
                                    $getFareAttributesSplit = explode('|', $getFareAttributes);

                                    foreach ($getFareAttributesSplit as $getFareAttributeSplit) {
                                        list($priority, $indicator) = explode(',', $getFareAttributeSplit);

                                        $features[$this->FareAttributes[$priority]['code']][] = [
                                            'code' => $this->FareAttributes[$priority]['code'],
                                            'description' => ['?'],
                                            'markAsImportant' => '?',
                                            'needToPay' => $this->indicators[$indicator],
                                            'priority' => $priority
                                        ];
                                    }
                                }

                                $passengerFares['tariffs'][] = [
                                    "code" => $fareInfoMap->get($bookingInfo->getFareInfoRef())->getFareBasis(),
                                    "segNum" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('key'),
                                    "features" => $features,
                                    "routeNumber" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('segment')->getGroup(),
                                ];

                                $segmentFareMap[$segmentFareHash] = $segmentFareHash;
                            }
                        }
                    }
                }

                $airPricePointData['passengerFares'][] = $passengerFares;
            }

            $agencyChargeAll = static::AGENCY_CHARGE_AMOUNT * $countOfPassengers;

            $airPricePointData['agencyCharge'] = [
                'amount' => $agencyChargeAll,
                'currency' => static::AGENCY_CHARGE_CURRENCY
            ];

            $airPricePointData['totalPrice'] = [
                'amount' =>  (float) substr($airPricePoint->getTotalPrice(), 3) + $agencyChargeAll,
                'currency' => substr($airPricePoint->getTotalPrice(), 0, 3),
            ];

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
                    'flight_search_request_id' => $requestId,
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

    public function AirPriceAdapt(AirPriceRsp $response, $oldTotalPrice)
    {
        $newTotalPrice = null;
        /** @var  $airPricingResult AirPriceResult */
        foreach ($response->getAirPriceResult() as $keyApr => $airPricingResult) {
            /** @var  $airPricingSolution AirPricingSolution */
            foreach ($airPricingResult->getAirPricingSolution() as $keyAps => $airPricingSolution) {
                if ($keyApr === 0 && $keyAps === 0) {
                    $newTotalPrice = $airPricingSolution->getTotalPrice();
                }

                if ($airPricingSolution->getTotalPrice() === $oldTotalPrice) {
                    $newTotalPrice = $airPricingSolution->getTotalPrice();
                    break 2;
                }
            }
        }

        return collect([
            "priceStatus" => [
                "changed" => (bool)($oldTotalPrice !== $newTotalPrice),
                "oldValue" => [
                    'amount' => (int)substr($oldTotalPrice, 3),
                    'currency' => substr($oldTotalPrice, 0, 3)
                ],
                "newValue" => [
                    'amount' => (int)substr($newTotalPrice, 3),
                    'currency' => substr($newTotalPrice, 0, 3)
                ]
            ],
            "hasAltFlights" => '?', // ?
            "tariffRules" => ['?'], //?
            "isAvail" => '?', // ?
        ]);
    }

    public function AirPriceAdaptCheckout(AirPriceRsp $response)
    {
        $countries = collect();
        $cities = collect();
        $airports = collect();
        $airLines = collect();
        $aircrafts = collect();
        $groupsData = collect();
        $airSegmentCollection = collect();
        $airPriceResultCollection = collect();
        $airSegmentMap = collect();

        /** @var typeBaseAirSegment $airSegment */
        foreach ($response->getAirItinerary()->getAirSegment() as $key => $airSegment) {
            $origin = $airSegment->getOrigin();
            $destination = $airSegment->getDestination();
            $carrier = $airSegment->getCarrier();
            $aircraftType = $airSegment->getEquipment();
            $airSegmentKey = sprintf('S%d', $key + 1);
            $airSegmentMap->put($airSegment->getKey(), collect([
                'segmentKey' => $airSegmentKey,
                'segment' => $airSegment,
                'key' => $key
            ]));

            $airSegmentData = [
                'key' => $airSegment->getKey(),
                'group' => $airSegment->getGroup(),
                'carrier' => $carrier,
                'flightNumber' => $airSegment->getFlightNumber(),
                'providerCode' => $airSegment->getProviderCode(),
                'depAirp' => $origin,
                'arrAirp' => $destination,
                'depDateTime' => Carbon::parse($airSegment->getDepartureTime())->format('Y-m-d\TH:i:s'),
                'arrDateTime' => Carbon::parse($airSegment->getArrivalTime())->format('Y-m-d\TH:i:s'),
                'flightTime' => $airSegment->getFlightTime(),
                'travelTime' => $airSegment->getTravelTime(),
                'distance' => $airSegment->getDistance(),
                'classOfService' => $airSegment->getClassOfService(),
                'aircraftType' => $aircraftType,
                'optionalServicesIndicator' => $airSegment->getOptionalServicesIndicator(),
                'availabilitySource' => $airSegment->getAvailabilitySource(),
                'participantLevel' => $airSegment->getParticipantLevel(),
                'linkAvailability' => $airSegment->getLinkAvailability(),
                'polledAvailabilityOption' => $airSegment->getPolledAvailabilityOption(),
                'availabilityDisplayType' => $airSegment->getAvailabilityDisplayType(),
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

            $airSegmentCollection->put($airSegmentKey, $airSegmentData);
        }

        /** @var AirPriceResult $airPriceResult */
        foreach ($response->getAirPriceResult() as $airPriceResult) {
            $airPriceResultData = [];
            /** @var AirPricingSolution $airSolution */
            foreach ($airPriceResult->getAirPricingSolution() as $airSolution) {
                $countOfPassengers = 0;

                $airSolutionData = [
                    'key' => $airSolution->getKey(),
                ];

                /** @var AirPricingInfo $airPricingInfo */
                foreach ($airSolution->getAirPricingInfo() as $airPricingInfo) {
                    $airPricingInfoData = [
                        'count' => count($airPricingInfo->getPassengerType()),
                        'type' => $airPricingInfo->getPassengerType()[0]->Code,
                        'fareCalc' => $airPricingInfo->getFareCalc(),
                    ];
                    $countOfPassengers += $airPricingInfoData['count'];

                    $airPricingInfoData['baseFare'] = [
                        'amount' => (float) substr($airPricingInfo->getBasePrice(), 3),
                        'currency' => substr($airPricingInfo->getBasePrice(), 0, 3),
                    ];

                    $airPricingInfoData['equivFare'] = [
                        'amount' => (float) substr($airPricingInfo->getEquivalentBasePrice(), 3),
                        'currency' => substr($airPricingInfo->getEquivalentBasePrice(), 0, 3),
                    ];

                    $airPricingInfoData['totalFare'] = [
                        'amount' => (float) substr($airPricingInfo->getTotalPrice(), 3),
                        'currency' => substr($airPricingInfo->getTotalPrice(), 0, 3),
                    ];

                    /** @var  $typeTaxInfo typeTaxInfo */
                    if ($airPricingInfo->getTaxInfo()) {
                        foreach ($airPricingInfo->getTaxInfo() as $typeTaxInfo) {
                            $airPricingInfoData['taxes'][] = [
                                $typeTaxInfo->getCategory() => [
                                    'amount' => (float) substr($typeTaxInfo->getAmount(), 3),
                                    'currency' => substr($typeTaxInfo->getAmount(), 0, 3)
                                ]
                            ];
                        }
                    }

                    /** @var FareInfo $fareInfo */
                    foreach ($airPricingInfo->getFareInfo() as $fareInfo) {
                        $fareInfoData = [
                            "code" => $fareInfo->getFareBasis(),
                            'fareRuleKey' => [
                                'fareInfoRef' => $fareInfo->getFareRuleKey()->getFareInfoRef(),
                                'providerCode' => $fareInfo->getFareRuleKey()->getProviderCode(),
                                'textNode' => $fareInfo->getFareRuleKey()->get_(),
                            ],
                            'brand' => [
                                'key' => $fareInfo->getBrand()->getKey(),
                                'brandId' => $fareInfo->getBrand()->getBrandID(),
                                'upSellBrandId' => $fareInfo->getBrand()->getUpSellBrandID(),
                                'name' => $fareInfo->getBrand()->getName(),
                                'carrier' => $fareInfo->getBrand()->getCarrier(),
                                'brandTier' => $fareInfo->getBrand()->getBrandTier()
                            ]
                        ];

                        if(!is_null($fareInfo->getFareSurcharge())) {
                            /** @var FareSurcharge $fareSurcharge */
                            foreach ($fareInfo->getFareSurcharge() as $fareSurcharge) {
                                $fareInfoData['fareSurcharge'][] = [
                                    'key' => $fareSurcharge->getKey(),
                                    'type' => $fareSurcharge->getType(),
                                    'amount' =>  (float) substr($fareSurcharge->getAmount(), 3),
                                    'currency' => substr($fareSurcharge->getAmount(), 0, 3)
                                ];
                            }
                        }

                        if($fareInfo->getBrand()->getTitle()) {
                            /** @var typeTextElement $title */
                            foreach ($fareInfo->getBrand()->getTitle() as $title) {
                                $fareInfoData['brand']['title'][] = [
                                    'type' => $title->getType(),
                                    'languageCode' => $title->getLanguageCode(),
                                    'textNode' => $title->get_(),
                                ];
                            }
                        }

                        if($fareInfo->getBrand()->getText()) {
                            /** @var typeTextElement $text */
                            foreach ($fareInfo->getBrand()->getText() as $text) {
                                $fareInfoData['brand']['text'][] = [
                                    'type' => $text->getType(),
                                    'languageCode' => $text->getLanguageCode(),
                                    'textNode' => $text->get_(),
                                ];
                            }
                        }

                        if($fareInfo->getBrand()->getImageLocation()) {
                            /** @var ImageLocation $imageLocation */
                            foreach ($fareInfo->getBrand()->getImageLocation() as $imageLocation) {
                                $fareInfoData['brand']['imageLocation'][] = [
                                    'type' => $imageLocation->getType(),
                                    'width' => $imageLocation->getImageWidth(),
                                    'height' => $imageLocation->getImageHeight(),
                                    'textNode' => $imageLocation->get_(),
                                ];
                            }
                        }

                        if($fareInfo->getBrand()->getOptionalServices()) {
                            /** @var OptionalService $optimalService */
                            foreach ($fareInfo->getBrand()->getOptionalServices()->getOptionalService() as $optimalService) {

                                $serviceDataAll = [];
                                /** @var ServiceData $serviceDate */
                                foreach ($optimalService->getServiceData() as $serviceDate) {
                                    $serviceDataAll[] = [
                                        'airSegmentRef' => $serviceDate->getAirSegmentRef()
                                    ];
                                }

                                $emd = [];
                                if($optimalService->getEMD()) {
                                    $emd = [
                                        'AssociatedItem' => $optimalService->getEMD()->getAssociatedItem()
                                    ];
                                }

                                $fareInfoData['brand']['optimalService'][] = [
                                    'type' => $optimalService->getType(),
                                    'createDate' => Carbon::parse($optimalService->getCreateDate())->format('Y-m-d\TH:i:s'),
                                    'serviceSubCode' => $optimalService->getServiceSubCode(),
                                    'key' => $optimalService->getKey(),
                                    'secondaryType' => $optimalService->getSecondaryType(),
                                    'chargeable' => $optimalService->getChargeable(),
                                    'tag' => $optimalService->getTag(),
                                    'displayOrder' => $optimalService->getDisplayOrder(),
                                    'serviceData' => $serviceDataAll,
                                    'serviceInfo' => $optimalService->getServiceInfo(),
                                    'emd' => $emd
                                ];

                            }
                        }

                        $airPricingInfoData['fareInfo'][] = $fareInfoData;
                    }

                    if($airPricingInfo->getBookingInfo()) {
                        /** @var BookingInfo $bookingInfo */
                        foreach($airPricingInfo->getBookingInfo() as $bookingInfo) {
                            $airPricingInfoData['bookingInfo'][] = [
                                'bookingCode' => $bookingInfo->getBookingCode(),
                                'bookingCount' => $bookingInfo->getBookingCount(),
                                'cabinClass' => $bookingInfo->getCabinClass(),
                                'fareInfoRef' => $bookingInfo->getFareInfoRef(),
                                'segmentRef' => $bookingInfo->getSegmentRef(),
                                'couponRef' => $bookingInfo->getCouponRef(),
                                'airItinerarySolutionRef' => $bookingInfo->getAirItinerarySolutionRef(),
                                'hostTokenRef' => $bookingInfo->getHostTokenRef(),
                            ];
                        }
                    }

                    if($airPricingInfo->getTaxInfo()) {
                        /** @var typeTaxInfo $taxInfo */
                        foreach($airPricingInfo->getTaxInfo() as $taxInfo) {
                            $airPricingInfoData['taxInfo'][] = [
                                'category' => $taxInfo->getCategory(),
                                'price' => [
                                    'amount' => (float) substr($taxInfo->getAmount(), 3),
                                    'currency' => substr($taxInfo->getAmount(), 0, 3)
                                ],
                                'key' => $taxInfo->getKey(),
                            ];
                        }
                    }

                    /** @var typeFarePenalty $chargePenalty */
                    foreach($airPricingInfo->getChangePenalty() as $chargePenalty) {
                        $airPricingInfoData['chargePenalty'][] = [
                            'price' => [
                                'amount' => (float) substr($chargePenalty->getAmount(), 3),
                                'currency' => substr($chargePenalty->getAmount(), 0, 3)
                            ],
                            'percentage' => $chargePenalty->getPercentage(),
                            'penaltyApplies' => $chargePenalty->getPenaltyApplies(),
                            'noShow' => $chargePenalty->getNoShow()
                        ];
                    }

                    foreach($airPricingInfo->getCancelPenalty() as $cancelPenalty) {
                        $airPricingInfoData['cancelPenalty'][] = [
                            'percentage' => $cancelPenalty->getPercentage(),
                            'penaltyApplies' => $cancelPenalty->getPenaltyApplies(),
                        ];
                    }

                    /** @var BaggageAllowanceInfo $baggageAllowanceInfo */
                    foreach($airPricingInfo->getBaggageAllowances()->getBaggageAllowanceInfo() as $baggageAllowanceInfo) {

                        $urlInfoData = [];
                        /** @var URLInfo $urlInfo */
                        foreach ($baggageAllowanceInfo->getURLInfo() as $urlInfo) {
                            $urlInfoData[] = [
                                'url' => $urlInfo->getURL(),
                                'text' => $urlInfo->getText(),
                            ];
                        }

                        $textInfoData = [];
                        /** @var TextInfo $textInfo */
                        foreach ($baggageAllowanceInfo->getTextInfo() as $textInfo) {
                            $textInfoData[] = [
                                'title' => $textInfo->getTitle(),
                                'text' => $textInfo->getText()
                            ];
                        }

                        /** @var BagDetails $bagDetail */
                        $bagDetailData = [];
                        foreach($baggageAllowanceInfo->getBagDetails() as $bagDetail) {

                            $baggageRestrictionData = [];
                            /** @var BaggageRestriction $baggageRestriction */
                            foreach ($bagDetail->getBaggageRestriction() as $baggageRestriction) {

                                $textInfoDataBagRest = [];
                                foreach($baggageRestriction->getTextInfo() as $textInfo) {
                                    $textInfoDataBagRest[] = [
                                        'title' => $textInfo->getTitle(),
                                        'text' => $textInfo->getText()
                                    ];
                                }

                                $baggageRestrictionData[] = [
                                    'dimension' => $baggageRestriction->getDimension(),
                                    'maxWeight' => $baggageRestriction->getMaxWeight(),
                                    'textInfo' => $textInfoDataBagRest
                                ];
                            }
                            $bagDetailData[] = [
                                'applicableBags' => $bagDetail->getApplicableBags(),
                                'basePrice' => [
                                    'amount' => (float) substr($bagDetail->getBasePrice(), 3),
                                    'currency' => substr($bagDetail->getBasePrice(), 0, 3)
                                ],
                                'approximateBasePrice' => [
                                    'amount' => (float) substr($bagDetail->getApproximateBasePrice(), 3),
                                    'currency' => substr($bagDetail->getApproximateBasePrice(), 0, 3)
                                ],
                                'totalPrice' => [
                                    'amount' => (float) substr($bagDetail->getTotalPrice(), 3),
                                    'currency' => substr($bagDetail->getTotalPrice(), 0, 3)
                                ],
                                'approximateTotalPrice' => [
                                    'amount' => (float) substr($bagDetail->getApproximateTotalPrice(), 3),
                                    'currency' => substr($bagDetail->getApproximateTotalPrice(), 0, 3)
                                ],
                                'baggageRestriction' => $baggageRestrictionData
                            ];
                        }

                        $airPricingInfoData['baggageAllowances']['baggageAllowanceInfo'][] = [
                            'travelType' => $baggageAllowanceInfo->getTravelerType(),
                            'origin' => $baggageAllowanceInfo->getOrigin(),
                            'destination' => $baggageAllowanceInfo->getDestination(),
                            'carrier' => $baggageAllowanceInfo->getCarrier(),
                            'urlInfo' => $urlInfoData,
                            'textInfo' => $textInfoData,
                            'baggageDetail' => $bagDetailData
                        ];
                    }

                    /** @var CarryOnAllowanceInfo $carryOnAllowanceInfo */
                    foreach($airPricingInfo->getBaggageAllowances()->getCarryOnAllowanceInfo() as $carryOnAllowanceInfo) {

                        $urlInfoData = [];
                        if($carryOnAllowanceInfo->getURLInfo()) {
                            /** @var URLInfo $urlInfo */
                            foreach ($carryOnAllowanceInfo->getURLInfo() as $urlInfo) {
                                $urlInfoData[] = [
                                    'url' => $urlInfo->getURL(),
                                    'text' => $urlInfo->getText(),
                                ];
                            }
                        }

                        $textInfoData = [];
                        /** @var TextInfo $textInfo */
                        foreach ($carryOnAllowanceInfo->getTextInfo() as $textInfo) {
                            $textInfoData[] = [
                                'title' => $textInfo->getTitle(),
                                'text' => $textInfo->getText()
                            ];
                        }

                        $carryOnDetailData = [];
                        if($carryOnAllowanceInfo->getCarryOnDetails()) {
                            /** @var CarryOnDetails $bagDetail */
                            foreach($carryOnAllowanceInfo->getCarryOnDetails() as $carryOnDetail) {

                                $baggageRestrictionData = [];
                                /** @var BaggageRestriction $baggageRestriction */
                                foreach ($carryOnDetail->getBaggageRestriction() as $baggageRestriction) {

                                    $textInfoDataBagRest = [];
                                    foreach($baggageRestriction->getTextInfo() as $textInfo) {
                                        $textInfoDataBagRest[] = [
                                            'title' => $textInfo->getTitle(),
                                            'text' => $textInfo->getText()
                                        ];
                                    }

                                    $baggageRestrictionData[] = [
                                        'dimension' => $baggageRestriction->getDimension(),
                                        'maxWeight' => $baggageRestriction->getMaxWeight(),
                                        'textInfo' => $textInfoDataBagRest
                                    ];
                                }

                                $carryOnDetailData[] = [
                                    'applicableCarryOnBags' => $carryOnDetail->getApplicableCarryOnBags(),
                                    'basePrice' => [
                                        'amount' => (float) substr($carryOnDetail->getBasePrice(), 3),
                                        'currency' => substr($carryOnDetail->getBasePrice(), 0, 3)
                                    ],
                                    'approximateBasePrice' => [
                                        'amount' => (float) substr($carryOnDetail->getApproximateBasePrice(), 3),
                                        'currency' => substr($carryOnDetail->getApproximateBasePrice(), 0, 3)
                                    ],
                                    'totalPrice' => [
                                        'amount' => (float) substr($carryOnDetail->getTotalPrice(), 3),
                                        'currency' => substr($carryOnDetail->getTotalPrice(), 0, 3)
                                    ],
                                    'approximateTotalPrice' => [
                                        'amount' => (float) substr($carryOnDetail->getApproximateTotalPrice(), 3),
                                        'currency' => substr($carryOnDetail->getApproximateTotalPrice(), 0, 3)
                                    ],
                                    'baggageRestriction' => $baggageRestrictionData
                                ];
                            }
                        }

                        $airPricingInfoData['baggageAllowances']['carryOnAllowanceInfo'][] = [
                            'origin' => $carryOnAllowanceInfo->getOrigin(),
                            'destination' => $carryOnAllowanceInfo->getDestination(),
                            'carrier' => $carryOnAllowanceInfo->getCarrier(),
                            'urlInfo' => $urlInfoData,
                            'textInfo' => $textInfoData,
                            'carryOnDetails' => $carryOnDetailData
                        ];
                    }

                    $airSolutionData['airPricingInfo'][] = $airPricingInfoData;
                }
                $agencyChargeAll = static::AGENCY_CHARGE_AMOUNT * $countOfPassengers;

                $airSolutionData['agencyCharge'] = [
                    'amount' => $agencyChargeAll,
                    'currency' => static::AGENCY_CHARGE_CURRENCY
                ];

                $airSolutionData['totalPrice'] = [
                    'amount' => (float) substr($airSolution->getTotalPrice(), 3) + $agencyChargeAll,
                    'currency' => (float) substr($airSolution->getTotalPrice(), 0, 3),
                ];

                $airSolutionData['paymentOptionCharge'] = [
                    'cache' => [
                        'amount' => $countOfPassengers * static::CACHE_AMOUNT,
                        'currency' => static::CACHE_CURRENCY
                    ],
                    'intesa' => [
                        'amount' => $airSolutionData['totalPrice']['amount'] * static::INTESA_COMMISSION / 100,
                        'currency' => $airSolutionData['totalPrice']['currency']
                    ],
                    'paypal' => [
                        'amount' => ($airSolutionData['totalPrice']['amount'] * static::PAYPAL_COMMISSION / 100) + static::PAYPAL_COMMISSION_FIX,
                        'currency' => $airSolutionData['totalPrice']['currency']
                    ]
                ];

                if($airSolution->getFareNote()) {
                    /** @var FareNote $fareNote */
                    foreach ($airSolution->getFareNote() as $fareNote) {
                        $airSolutionData['fareNote'][] = [
                            'key' => $fareNote->getKey(),
                            'textNode' => $fareNote->get_(),
                        ];
                    }
                }

                if($airSolution->getHostToken()) {
                    /** @var HostToken $hostToken */
                    foreach ($airSolution->getHostToken() as $hostToken) {
                        $airSolutionData['hostToken'][] = [
                            'key' => $hostToken->getKey(),
                            'textNode' => $hostToken->get_(),
                        ];
                    }
                }

                $airPriceResultData['airSolution'][] = $airSolutionData;
            }

            /** @var FareRule $fareRule */
            foreach ($airPriceResult->getFareRule() as $fareRule)
            {
                $fareRuleLongData = [];
                /** @var FareRuleLong $fareRuleLong */
                foreach ($fareRule->getFareRuleLong() as $fareRuleLong) {
                    $fareRuleLongData[] = [
                        'textNode' => $fareRuleLong->get_(),
                        'category' => $fareRuleLong->getCategory(),
                        'type' => $fareRuleLong->getType()
                    ];
                }

                $airPriceResultData['fareRule'][] = [
                    'fareInfoRef' => $fareRule->getFareInfoRef(),
                    'ruleNumber' => $fareRule->getRuleNumber(),
                    'source' => $fareRule->getSource(),
                    'tariffNumber' => $fareRule->getTariffNumber(),
                    'fareRuleLong' => $fareRuleLongData
                ];
            }

            $airPriceResultCollection->add($airPriceResultData);
        }

        $groupsData->put('segments', $airSegmentCollection);
        $groupsData->put('prices', $airPriceResultCollection);
        $info = collect([
            'nationality' => false,
            'dateOfBirth' => false,
            'passportNo' => false,
            'passportCountry' => false,
            'passportExpiration' => false
        ]);

        $results = collect(['groupsData' => $groupsData, 'info' => $info]);

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