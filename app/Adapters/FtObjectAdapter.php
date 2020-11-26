<?php


namespace App\Adapters;


use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\FlightsSearchResult;
use App\Services\MoneyService;
use App\Services\TravelPortService;
use Carbon\Carbon;
use FilippoToso\Travelport\Air\ActionStatus;
use FilippoToso\Travelport\Air\AirFareRulesRsp;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPriceResult;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\AirPricingInfo;
use FilippoToso\Travelport\Air\AirPricingSolution;
use FilippoToso\Travelport\Air\BagDetails;
use FilippoToso\Travelport\Air\BaggageAllowanceInfo;
use FilippoToso\Travelport\Air\BaggageRestriction;
use FilippoToso\Travelport\Air\BookingInfo;
use FilippoToso\Travelport\Air\BookingTraveler;
use FilippoToso\Travelport\Air\Brand;
use FilippoToso\Travelport\Air\CarryOnAllowanceInfo;
use FilippoToso\Travelport\Air\CarryOnDetails;
use FilippoToso\Travelport\Air\DeliveryInfo;
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
use FilippoToso\Travelport\Air\ProviderReservationInfoRef;
use FilippoToso\Travelport\Air\ServiceData;
use FilippoToso\Travelport\Air\SupplierLocator;
use FilippoToso\Travelport\Air\TextInfo;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use FilippoToso\Travelport\Air\typeFarePenalty;
use FilippoToso\Travelport\Air\typeStructuredAddress;
use FilippoToso\Travelport\Air\typeTaxInfo;
use FilippoToso\Travelport\Air\typeTextElement;
use FilippoToso\Travelport\Air\typeWeight;
use FilippoToso\Travelport\Air\URLInfo;
use FilippoToso\Travelport\UniversalRecord\AgentAction;
use FilippoToso\Travelport\UniversalRecord\AirCreateReservationRsp;
use FilippoToso\Travelport\UniversalRecord\AirSolutionChangedInfo;
use FilippoToso\Travelport\UniversalRecord\BookingTravelerRef;
use FilippoToso\Travelport\UniversalRecord\Email;
use FilippoToso\Travelport\UniversalRecord\Endorsement;
use FilippoToso\Travelport\UniversalRecord\FormOfPayment;
use FilippoToso\Travelport\UniversalRecord\PassengerType;
use FilippoToso\Travelport\UniversalRecord\PhoneNumber;
use FilippoToso\Travelport\UniversalRecord\SegmentRemark;
use FilippoToso\Travelport\UniversalRecord\TicketingModifiers;
use FilippoToso\Travelport\UniversalRecord\TicketingModifiersRef;
use FilippoToso\Travelport\UniversalRecord\typeFormOfPaymentPNRReference;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Libs\Money;

class FtObjectAdapter extends NemoWidgetAbstractAdapter
{
    protected $FareAttributes = [
        1 => [
            'code' => 'baggage',
            'feature' => 'baggage',
        ],
//        2 => [
//            'code' => 'carry_on',
//            'feature' => 'baggage',
//        ],
        3 => [
            'code' => 'exchangeable',
            'feature' => 'refunds',
        ],
        4 => [
            'code' => 'refundable',
            'feature' => 'refunds',
        ],
//        5 => [
//            'code' => 'vip_service',
//            'feature' => 'misc',
//        ],
//        6 => [
//            'code' => 'vip_service',
//            'feature' => 'misc',
//        ],
//        7 => [
//            'code' => 'vip_service',
//            'feature' => 'misc',
//        ],
    ];

    protected $indicators = [
        'I' => 'Free',
        'A' => 'Charge',
        'N' => 'NotAvailable',
    ];

    protected $moneyService;

    const AGENCY_CHARGE_AMOUNT = 495;
    const AGENCY_CHARGE_CURRENCY = 'RSD';

    const CASH_AMOUNT = 795;
    const CASH_CURRENCY = 'RSD';

    const INTESA_COMMISSION = 0;
    const PAYPAL_COMMISSION = 2.9;
    const PAYPAL_COMMISSION_FIX = 30;

    const PASSENGER_TYPE_ADULT = 'ADT';
    const PASSENGER_TYPE_INFANT = 'INF';
    const PASSENGER_TYPE_CHILD = 'CNN';

    public function __construct(MoneyService $moneyService)
    {
        $this->moneyService = $moneyService;
    }

    /**
     * @param LowFareSearchRsp $searchRsp
     * @param int $requestId
     * @return Collection
     * @throws \Brick\Money\Exception\MoneyMismatchException
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
        $brandMap = collect();

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
                'operatingCompany' => is_null($airSegment->getCodeshareInfo()) ? $carrier : ($airSegment->getCodeshareInfo()->getOperatingCarrier() ? $airSegment->getCodeshareInfo()->getOperatingCarrier() : $carrier),
                'number' => 0,
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

            if (!is_null($airSegmentData['operatingCompany']) && !$airLines->has($airSegmentData['operatingCompany'])) {
                $airLines->put($airSegmentData['operatingCompany'], Airline::whereCode($airSegmentData['operatingCompany'])->first());
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

        if (!is_null($searchRsp->getBrandList())) {
            /** @var  $brand Brand */
            foreach ($searchRsp->getBrandList()->getBrand() as $brand) {
                $brandMap->put($brand->getBrandID(), $brand);
            }
        }


        /** @var $airPricePoint AirPricePoint */
        foreach ($searchRsp->getAirPricePointList()->getAirPricePoint() as $key => $airPricePoint) {
            $segmentsGroup = [];
            $segmentFareMap = [];
            $segmentRefStack = [];
            $airPricePointKey = sprintf('P%d', $key + 1);
            $countOfPassengers = 0;

            $flightPrice = $this->moneyService->getMoneyByString($airPricePoint->getTotalPrice());

            $airPricePointData = [
                'flightPrice' => [
                    'amount' => $flightPrice->getAmountAsFloat(),
                    'currency' => $flightPrice->getCurrency()->getCurrencyCode()
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

            /** @var  $airPricingInfo AirPricingInfo */
            foreach ($airPricePoint->getAirPricingInfo() as $airPricingInfo) {
                $passengerFares = [];
                $airPricePointData['refundable'] = $airPricingInfo->getRefundable() ?? false;
                $refundsData = [];
                if ($airPricingInfo->getChangePenalty()) {
                    /** @var typeFarePenalty $chargePenalty */
                    foreach ($airPricingInfo->getChangePenalty() as $chargePenalty) {
                        $refundsData[] = [
                            'code' => 'exchangeable',
                            'description' => [
                                'short' => 'Exchange',
                                'full' => $chargePenalty->getPercentage() === '0.00' ? 'Non-exchangeable' : 'Exchangeable',
                            ],
                            'needToPay' => $chargePenalty->getPercentage() === '0.00' ? 'NotAvailable' : 'agencyCharge',
                            'markAsImportant' => false,
                            'priority' => 2,
                            'showTitle' => false
                        ];
                    }
                }
                if ($airPricingInfo->getCancelPenalty()) {
                    /** @var typeFarePenalty $cancelPenalty */
                    foreach ($airPricingInfo->getCancelPenalty() as $cancelPenalty) {
                        $refundsData[] = [
                            'code' => 'refundable',
                            'description' => [
                                'short' => 'Refund',
                                'full' => $cancelPenalty->getPercentage() === '0.00' ? 'Non-refundable' : 'Refundable',
                            ],
                            'needToPay' => $cancelPenalty->getPercentage() === '0.00' ? 'NotAvailable' : 'agencyCharge',
                            'markAsImportant' => false,
                            'priority' => 3,
                            'showTitle' => false
                        ];
                    }
                }
                $passengerFares['count'] = count($airPricingInfo->getPassengerType());
                $passengerFares['type'] = $airPricingInfo->getPassengerType()[0]->Code;
                $countOfPassengers += $passengerFares['count'];

                $baseFare = $this->moneyService->getMoneyByString($airPricingInfo->getBasePrice());
                $passengerFares['baseFare'] = [
                    'amount' => $baseFare->getAmountAsFloat(),
                    'currency' => $baseFare->getCurrency()->getCurrencyCode()
                ];

                $equivFare = $this->moneyService->getMoneyByString($airPricingInfo->getEquivalentBasePrice());
                $passengerFares['equivFare'] = [
                    'amount' => $equivFare->getAmountAsFloat(),
                    'currency' => $equivFare->getCurrency()->getCurrencyCode(),
                ];

                $totalPrice = $this->moneyService->getMoneyByString($airPricingInfo->getTotalPrice());
                $passengerFares['totalFare'] = [
                    'amount' => $totalPrice->getAmountAsFloat(),
                    'currency' => $totalPrice->getCurrency()->getCurrencyCode(),
                ];

                /** @var  $typeTaxInfo typeTaxInfo */
                $passengerFares['taxes'] = [];
                if ($airPricingInfo->getTaxInfo()) {
                    foreach ($airPricingInfo->getTaxInfo() as $typeTaxInfo) {
                        $taxInfoPrice = $this->moneyService->getMoneyByString($typeTaxInfo->getAmount());
                        $passengerFares['taxes'][] = [
                            $typeTaxInfo->getCategory() => [
                                'amount' => $taxInfoPrice->getAmountAsFloat(),
                                'currency' => $taxInfoPrice->getCurrency()->getCurrencyCode()
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

                                if (!isset($segmentRefStack[(string)$bookingInfo->getSegmentRef()])) {
                                    $airPricePointData['segmentInfo'][] = [
                                        "segNum" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('key'),
                                        "routeNumber" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('segment')->getGroup(),
                                        "bookingClass" => $bookingInfo->getBookingCode(),
                                        "serviceClass" => $bookingInfo->getCabinClass(),
                                        "avlSeats" => $bookingInfo->getBookingCount(),
                                        "freeBaggage" => $bookingInfo->getFareInfoRef()
                                    ];

                                    $segmentRefStack[(string)$bookingInfo->getSegmentRef()] = (string)$bookingInfo->getSegmentRef();
                                }
                            }
                            if (!isset($segmentFareMap[$segmentFareHash])) {
                                $features = [];
                                /** @var FareInfo $getFareInfo */
                                $getFareInfo = $fareInfoMap->get($bookingInfo->getFareInfoRef());

                                if ($getFareInfo
//                                    && $getFareInfo->getFareAttributes()
                                    && $getFareInfo->getBaggageAllowance()
                                ) {
//                                    $getFareAttributes = $getFareInfo->getFareAttributes();
                                    $numberOfPiece = $getFareInfo->getBaggageAllowance()->getNumberOfPieces();
                                    /** @var typeWeight $maxWeight */
                                    $maxWeight = $getFareInfo->getBaggageAllowance()->getMaxWeight();

                                    $short = '';
                                    $indicator = 'N';
                                    if ($numberOfPiece > 0) {
                                        $short = Str::plural('bag', $numberOfPiece);
                                        $indicator = 'I';
                                    }

                                    if ($maxWeight && $maxWeight->getValue() > 0) {
                                        $short = $maxWeight->getValue() . " kg";
                                        $indicator = 'I';
                                    }

//                                    $refundSection = $refundsData[$fareAttribute['code']] ?? [];
                                    $features['baggage'][] = [
                                        'code' => 'baggage',
                                        'description' => [
                                            'short' => $short,
                                            'full' => ''
                                        ],
                                        'needToPay' => $this->indicators[$indicator],
                                        'markAsImportant' => false,
                                        'priority' => 1,
                                        'showTitle' => true
                                    ];

                                    $features['hasFeatures'] = true;
                                    $features['refunds'] = $refundsData;
                                    $features['misc'] = [];
//                                    $getFareAttributesSplit = explode('|', $getFareAttributes);
//                                    foreach ($getFareAttributesSplit as $getFareAttributeSplit) {
//                                        list($priority, $indicator) = explode(',', $getFareAttributeSplit);
//                                        $fareAttribute = $this->FareAttributes[$priority] ?? null;
//
//                                        if (is_null($fareAttribute)) {
//                                            continue;
//                                        }
//
//                                        $short = '';
//                                        $indicator = 'N';
//                                        if ($numberOfPiece > 0) {
//                                            $short = "$numberOfPiece bag(s)";
//                                            $indicator = 'I';
//                                        }
//
//                                        if ($maxWeight && $maxWeight->getValue() > 0) {
//                                            $short = "$maxWeight kg";
//                                            $indicator = 'I';
//                                        }
//
//                                        $refundSection = $refundsData[$fareAttribute['code']] ?? [];
//                                        $features[$fareAttribute['feature']][] = array_merge(
//                                            [
//                                                'baggage' => [
//                                                    'code' => $fareAttribute['code'],
//                                                    'description' => [
//                                                        'short' => $short,
//                                                        'full' => ''
//                                                    ],
//                                                ],
//                                                'refunds' => array_shift($refundSection)
//                                            ][$fareAttribute['feature']],
//                                            [
//                                                'needToPay' => $this->indicators[$indicator],
//                                                'markAsImportant' => false,
//                                                'priority' => $priority,
//                                                'showTitle' => true
//                                            ]
//                                        );
//
//                                        $features['hasFeatures'] = true;
//                                    }
                                }

                                $brand = $fareInfoMap->get($bookingInfo->getFareInfoRef())->getBrand();
                                $brand = !is_null($brand) ? $brandMap->get($brand->getBrandID(), null) : null;
                                $title = !is_null($brand) ? $brand->getTitle()[0] : null;
                                $passengerFares['tariffs'][] = [
                                    "code" => $fareInfoMap->get($bookingInfo->getFareInfoRef())->getFareBasis(),
                                    "familyName" => !is_null($title) ? $title->get_() : null,
                                    "segNum" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('key'),
                                    "features" => !is_null($brand) ? $features : [],
                                    "routeNumber" => $airSegmentMap->get($bookingInfo->getSegmentRef())->get('segment')->getGroup(),
                                    'serviceClass' => $bookingInfo->getCabinClass()
                                ];

                                $segmentFareMap[$segmentFareHash] = $segmentFareHash;
                            }
                        }
                    }
                }

                $airPricePointData['passengerFares'][] = $passengerFares;
            }

            $agencyChargeAll = $this->moneyService->getAgencyChargeForAllPassengers($countOfPassengers);

            $airPricePointData['agencyCharge'] = [
                'amount' => $agencyChargeAll->getAmountAsFloat(),
                'currency' => $agencyChargeAll->getCurrency()->getCurrencyCode(),
                'regular' => [
                    static::PASSENGER_TYPE_ADULT => MoneyService::AGENCY_CHARGE_AMOUNT,
                    static::PASSENGER_TYPE_CHILD => MoneyService::AGENCY_CHARGE_AMOUNT,
                    static::PASSENGER_TYPE_INFANT => MoneyService::AGENCY_CHARGE_AMOUNT,
                ],
                'brand' => [
                    static::PASSENGER_TYPE_ADULT => MoneyService::BRAND_CHARGE_AMOUNT,
                    static::PASSENGER_TYPE_CHILD => MoneyService::BRAND_CHARGE_AMOUNT,
                    static::PASSENGER_TYPE_INFANT => MoneyService::BRAND_CHARGE_AMOUNT,
                ]

            ];

            $totalPrice = $this->moneyService->getMoneyByString($airPricePoint->getTotalPrice())->plus($agencyChargeAll);
            $airPricePointData['totalPrice'] = [
                'amount' => $totalPrice->getAmountAsFloat(),
                'currency' => $totalPrice->getCurrency()->getCurrencyCode()
            ];

            $avlSeatsMin = [];
            foreach ($airPricePointData['segmentInfo'] as &$segmentInfo) {
                $avlSeatsMin[] = $segmentInfo['avlSeats'];
                $fareInfoRef = $segmentInfo['freeBaggage'];
                $segmentInfo['freeBaggage'] = [];
                $fareInfo = $fareInfoMap->get($fareInfoRef);

                if (!is_null($fareInfo->getBaggageAllowance())) {
                    foreach ($airPricePointData['passengerFares'] as $passengerFare) {
                        $value = $fareInfo->getBaggageAllowance()->getNumberOfPieces() ?? $fareInfo->getBaggageAllowance()->getMaxWeight()->getValue() ?? null;

                        $freeBaggage = [
                            'passtype' => $passengerFare['type'],
                            "value" => $value,
                            'measurement' => $fareInfo->getBaggageAllowance()->getNumberOfPieces() ? 'pc' : ($fareInfo->getBaggageAllowance()->getMaxWeight()->getValue() ? 'kg' : 'pc'),
                        ];
                        $segmentInfo['freeBaggage'][] = $freeBaggage;

                        if ($passengerFare['type'] === static::PASSENGER_TYPE_ADULT) {
                            $segmentInfo["minBaggage"] = [
                                'value' => $freeBaggage['value'],
                                'measurement' => $freeBaggage['measurement']
                            ];
                        }
                    }
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
            $countries = $countries->add($airport->country);
            $cities = $cities->add($airport->city);
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

    /**
     * @param AirPriceRsp $response
     * @param Money $oldTotalPrice
     * @return Collection
     * @throws \Brick\Money\Exception\MoneyMismatchException
     */
    public function AirPriceAdapt(AirPriceRsp $response, Money $oldTotalPrice)
    {
        $newTotalPrice = null;
        /** @var  $airPricingResult AirPriceResult */
        foreach ($response->getAirPriceResult() as $keyApr => $airPricingResult) {
            /** @var  $airPricingSolution AirPricingSolution */
            foreach ($airPricingResult->getAirPricingSolution() as $keyAps => $airPricingSolution) {
                if ($keyApr === 0 && $keyAps === 0) {
                    $newTotalPrice = $this->moneyService->getMoneyByString($airPricingSolution->getTotalPrice());
                }

                if ($this->moneyService->getMoneyByString($airPricingSolution->getTotalPrice())->isEqualTo($oldTotalPrice)) {
                    $newTotalPrice = $this->moneyService->getMoneyByString($airPricingSolution->getTotalPrice());
                    break 2;
                }
            }
        }

        return collect([
            "priceStatus" => [
                "changed" => !$newTotalPrice->isEqualTo($oldTotalPrice),
                "oldValue" => [
                    'amount' => $oldTotalPrice->getAmountAsFloat(),
                    'currency' => $oldTotalPrice->getCurrency()->getCurrencyCode()
                ],
                "newValue" => [
                    'amount' => $newTotalPrice->getAmountAsFloat(),
                    'currency' => $newTotalPrice->getCurrency()->getCurrencyCode()
                ]
            ],
            "hasAltFlights" => '?', // ?
            "tariffRules" => ['?'], //?
            "isAvail" => '?', // ?
        ]);
    }

    public function AirPriceAdaptCheckout(AirPriceRsp $response, Money $price)
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
        $infoData = collect();

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

                    $infoData->put($airPricingInfoData['type'], [
                        'nationality' => false,
                        'dateOfBirth' => in_array($airPricingInfoData['type'], [static::PASSENGER_TYPE_INFANT, static::PASSENGER_TYPE_CHILD]),
                        'passportNo' => false,
                        'passportCountry' => false,
                        'passportExpiration' => false
                    ]);

                    $baseFare = $this->moneyService->getMoneyByString($airPricingInfo->getBasePrice());
                    $airPricingInfoData['baseFare'] = [
                        'amount' => $baseFare->getAmountAsFloat(),
                        'currency' => $baseFare->getCurrency()->getCurrencyCode()
                    ];

                    $equivFare = $this->moneyService->getMoneyByString($airPricingInfo->getEquivalentBasePrice());
                    $airPricingInfoData['equivFare'] = [
                        'amount' => $equivFare->getAmountAsFloat(),
                        'currency' => $equivFare->getCurrency()->getCurrencyCode()
                    ];

                    $totalPrice = $this->moneyService->getMoneyByString($airPricingInfo->getTotalPrice());
                    $airPricingInfoData['totalFare'] = [
                        'amount' => $totalPrice->getAmountAsFloat(),
                        'currency' => $totalPrice->getCurrency()->getCurrencyCode()
                    ];

                    /** @var  $typeTaxInfo typeTaxInfo */
                    if ($airPricingInfo->getTaxInfo()) {
                        foreach ($airPricingInfo->getTaxInfo() as $typeTaxInfo) {
                            $taxPrice = $this->moneyService->getMoneyByString($typeTaxInfo->getAmount());
                            $airPricingInfoData['taxes'][] = [
                                $typeTaxInfo->getCategory() => [
                                    'amount' => $taxPrice->getAmountAsFloat(),
                                    'currency' => $taxPrice->getCurrency()->getCurrencyCode()
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
                        ];

                        if (!is_null($fareInfo->getBrand())) {
                            $fareInfoData['brand'] = [
                                'key' => $fareInfo->getBrand()->getKey(),
                                'brandId' => $fareInfo->getBrand()->getBrandID(),
                                'upSellBrandId' => $fareInfo->getBrand()->getUpSellBrandID(),
                                'name' => $fareInfo->getBrand()->getName(),
                                'carrier' => $fareInfo->getBrand()->getCarrier(),
                                'brandTier' => $fareInfo->getBrand()->getBrandTier()
                            ];

                            if ($fareInfo->getBrand()->getTitle()) {
                                /** @var typeTextElement $title */
                                foreach ($fareInfo->getBrand()->getTitle() as $title) {
                                    $fareInfoData['brand']['title'][] = [
                                        'type' => $title->getType(),
                                        'languageCode' => $title->getLanguageCode(),
                                        'textNode' => $title->get_(),
                                    ];
                                }
                            }

                            if ($fareInfo->getBrand()->getText()) {
                                /** @var typeTextElement $text */
                                foreach ($fareInfo->getBrand()->getText() as $text) {
                                    $fareInfoData['brand']['text'][] = [
                                        'type' => $text->getType(),
                                        'languageCode' => $text->getLanguageCode(),
                                        'textNode' => $text->get_(),
                                    ];
                                }
                            }

                            if ($fareInfo->getBrand()->getImageLocation()) {
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

                            if ($fareInfo->getBrand()->getOptionalServices()) {
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
                                    if ($optimalService->getEMD()) {
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
                        }

                        if (!is_null($fareInfo->getFareSurcharge())) {
                            /** @var FareSurcharge $fareSurcharge */
                            foreach ($fareInfo->getFareSurcharge() as $fareSurcharge) {
                                $fareInfoData['fareSurcharge'][] = [
                                    'key' => $fareSurcharge->getKey(),
                                    'type' => $fareSurcharge->getType(),
                                ];
                            }
                        }

                        $airPricingInfoData['fareInfo'][] = $fareInfoData;
                    }

                    if ($airPricingInfo->getBookingInfo()) {
                        /** @var BookingInfo $bookingInfo */
                        foreach ($airPricingInfo->getBookingInfo() as $bookingInfo) {
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

                    if ($airPricingInfo->getTaxInfo()) {
                        /** @var typeTaxInfo $taxInfo */
                        foreach ($airPricingInfo->getTaxInfo() as $taxInfo) {
                            $taxInfoPrice = $this->moneyService->getMoneyByString($taxInfo->getAmount());
                            $airPricingInfoData['taxInfo'][] = [
                                'category' => $taxInfo->getCategory(),
                                'price' => [
                                    'amount' => $taxInfoPrice->getAmountAsFloat(),
                                    'currency' => $taxInfoPrice->getCurrency()->getCurrencyCode()
                                ],
                                'key' => $taxInfo->getKey(),
                            ];
                        }
                    }

                    if (!is_null($airPricingInfo->getChangePenalty())) {
                        /** @var typeFarePenalty $chargePenalty */
                        foreach ($airPricingInfo->getChangePenalty() as $chargePenalty) {
                            $chargePenaltyPrice = $this->moneyService->getMoneyByString($chargePenalty->getAmount());
                            $airPricingInfoData['chargePenalty'][] = [
                                'price' => [
                                    'amount' => $chargePenaltyPrice->getAmountAsFloat(),
                                    'currency' => $chargePenaltyPrice->getCurrency()->getCurrencyCode()
                                ],
                                'percentage' => $chargePenalty->getPercentage(),
                                'penaltyApplies' => $chargePenalty->getPenaltyApplies(),
                                'noShow' => $chargePenalty->getNoShow()
                            ];
                        }
                    }

                    if (!is_null($airPricingInfo->getCancelPenalty())) {
                        foreach ($airPricingInfo->getCancelPenalty() as $cancelPenalty) {
                            $airPricingInfoData['cancelPenalty'][] = [
                                'percentage' => $cancelPenalty->getPercentage(),
                                'penaltyApplies' => $cancelPenalty->getPenaltyApplies(),
                            ];
                        }
                    }

                    /** @var BaggageAllowanceInfo $baggageAllowanceInfo */
                    foreach ($airPricingInfo->getBaggageAllowances()->getBaggageAllowanceInfo() as $baggageAllowanceInfo) {

                        $urlInfoData = [];
                        if (!is_null($baggageAllowanceInfo->getURLInfo())) {
                            /** @var URLInfo $urlInfo */
                            foreach ($baggageAllowanceInfo->getURLInfo() as $urlInfo) {
                                $urlInfoData[] = [
                                    'url' => $urlInfo->getURL(),
                                    'text' => $urlInfo->getText(),
                                ];
                            }
                        }

                        $textInfoData = [];
                        if (!is_null($baggageAllowanceInfo->getTextInfo())) {
                            /** @var TextInfo $textInfo */
                            foreach ($baggageAllowanceInfo->getTextInfo() as $textInfo) {
                                $textInfoData[] = [
                                    'title' => $textInfo->getTitle(),
                                    'text' => $textInfo->getText()
                                ];
                            }
                        }


                        $bagDetailData = [];
                        if (!is_null($baggageAllowanceInfo->getBagDetails())) {
                            /** @var BagDetails $bagDetail */
                            foreach ($baggageAllowanceInfo->getBagDetails() as $bagDetail) {

                                $baggageRestrictionData = [];
                                if (!is_null($bagDetail->getBaggageRestriction())) {
                                    /** @var BaggageRestriction $baggageRestriction */
                                    foreach ($bagDetail->getBaggageRestriction() as $baggageRestriction) {

                                        $textInfoDataBagRest = [];
                                        if (!is_null($baggageRestriction->getTextInfo())) {
                                            foreach ($baggageRestriction->getTextInfo() as $textInfo) {
                                                $textInfoDataBagRest[] = [
                                                    'title' => $textInfo->getTitle(),
                                                    'text' => $textInfo->getText()
                                                ];
                                            }
                                        }


                                        $baggageRestrictionData[] = [
                                            'dimension' => $baggageRestriction->getDimension(),
                                            'maxWeight' => $baggageRestriction->getMaxWeight(),
                                            'textInfo' => $textInfoDataBagRest
                                        ];
                                    }
                                }

                                $basePrice = $this->moneyService->getMoneyByString($bagDetail->getBasePrice());
                                $approximateBasePrice = $this->moneyService->getMoneyByString($bagDetail->getApproximateBasePrice());
                                $totalPrice = $this->moneyService->getMoneyByString($bagDetail->getTotalPrice());
                                $approximateTotalPrice = $this->moneyService->getMoneyByString($bagDetail->getApproximateTotalPrice());
                                $bagDetailData[] = [
                                    'applicableBags' => $bagDetail->getApplicableBags(),
                                    'basePrice' => [
                                        'amount' => $basePrice->getAmountAsFloat(),
                                        'currency' => $basePrice->getCurrency()->getCurrencyCode()
                                    ],
                                    'approximateBasePrice' => [
                                        'amount' => $approximateBasePrice->getAmountAsFloat(),
                                        'currency' => $approximateBasePrice->getCurrency()->getCurrencyCode()
                                    ],
                                    'totalPrice' => [
                                        'amount' => $totalPrice->getAmountAsFloat(),
                                        'currency' => $totalPrice->getCurrency()->getCurrencyCode()
                                    ],
                                    'approximateTotalPrice' => [
                                        'amount' => $approximateTotalPrice->getAmountAsFloat(),
                                        'currency' => $approximateTotalPrice->getCurrency()->getCurrencyCode()
                                    ],
                                    'baggageRestriction' => $baggageRestrictionData
                                ];
                            }
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
                    foreach ($airPricingInfo->getBaggageAllowances()->getCarryOnAllowanceInfo() as $carryOnAllowanceInfo) {

                        $urlInfoData = [];
                        if ($carryOnAllowanceInfo->getURLInfo()) {
                            /** @var URLInfo $urlInfo */
                            foreach ($carryOnAllowanceInfo->getURLInfo() as $urlInfo) {
                                $urlInfoData[] = [
                                    'url' => $urlInfo->getURL(),
                                    'text' => $urlInfo->getText(),
                                ];
                            }
                        }

                        $textInfoData = [];
                        if (!is_null($carryOnAllowanceInfo->getTextInfo())) {
                            /** @var TextInfo $textInfo */
                            foreach ($carryOnAllowanceInfo->getTextInfo() as $textInfo) {
                                $textInfoData[] = [
                                    'title' => $textInfo->getTitle(),
                                    'text' => $textInfo->getText()
                                ];
                            }
                        }


                        $carryOnDetailData = [];
                        if ($carryOnAllowanceInfo->getCarryOnDetails()) {
                            /** @var CarryOnDetails $bagDetail */
                            foreach ($carryOnAllowanceInfo->getCarryOnDetails() as $carryOnDetail) {

                                $baggageRestrictionData = [];
                                if (!is_null($carryOnDetail->getBaggageRestriction())) {
                                    /** @var BaggageRestriction $baggageRestriction */
                                    foreach ($carryOnDetail->getBaggageRestriction() as $baggageRestriction) {

                                        $textInfoDataBagRest = [];
                                        if (!is_null($baggageRestriction->getTextInfo())) {
                                            foreach ($baggageRestriction->getTextInfo() as $textInfo) {
                                                $textInfoDataBagRest[] = [
                                                    'title' => $textInfo->getTitle(),
                                                    'text' => $textInfo->getText()
                                                ];
                                            }
                                        }

                                        $baggageRestrictionData[] = [
                                            'dimension' => $baggageRestriction->getDimension(),
                                            'maxWeight' => $baggageRestriction->getMaxWeight(),
                                            'textInfo' => $textInfoDataBagRest
                                        ];
                                    }
                                }

                                $basePrice = $this->moneyService->getMoneyByString($carryOnDetail->getBasePrice());
                                $approximateBasePrice = $this->moneyService->getMoneyByString($carryOnDetail->getApproximateBasePrice());
                                $totalPrice = $this->moneyService->getMoneyByString($carryOnDetail->getTotalPrice());
                                $approximateTotalPrice = $this->moneyService->getMoneyByString($carryOnDetail->getApproximateTotalPrice());
                                $carryOnDetailData[] = [
                                    'applicableCarryOnBags' => $carryOnDetail->getApplicableCarryOnBags(),
                                    'basePrice' => [
                                        'amount' => $basePrice->getAmountAsFloat(),
                                        'currency' => $basePrice->getCurrency()->getCurrencyCode()
                                    ],
                                    'approximateBasePrice' => [
                                        'amount' => $approximateBasePrice->getAmountAsFloat(),
                                        'currency' => $approximateBasePrice->getCurrency()->getCurrencyCode()
                                    ],
                                    'totalPrice' => [
                                        'amount' => $totalPrice->getAmountAsFloat(),
                                        'currency' => $totalPrice->getCurrency()->getCurrencyCode()
                                    ],
                                    'approximateTotalPrice' => [
                                        'amount' => $approximateTotalPrice->getAmountAsFloat(),
                                        'currency' => $approximateTotalPrice->getCurrency()->getCurrencyCode()
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

                $agencyChargeAll = $this->moneyService->getAgencyChargeForAllPassengers($countOfPassengers);

                $airSolutionData['agencyCharge'] = [
                    'amount' => $agencyChargeAll->getAmountAsFloat(),
                    'currency' => $agencyChargeAll->getCurrency()->getCurrencyCode(),
                    'regular' => [
                        static::PASSENGER_TYPE_ADULT => MoneyService::AGENCY_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_CHILD => MoneyService::AGENCY_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_INFANT => MoneyService::AGENCY_CHARGE_AMOUNT,
                    ],
                    'brand' => [
                        static::PASSENGER_TYPE_ADULT => MoneyService::BRAND_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_CHILD => MoneyService::BRAND_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_INFANT => MoneyService::BRAND_CHARGE_AMOUNT,
                    ]
                ];

                $airSolutionPrice = $this->moneyService->getMoneyByString($airSolution->getTotalPrice());
                $totalPrice = $airSolutionPrice->plus($agencyChargeAll);
                $airSolutionData['totalPrice'] = [
                    'amount' => $totalPrice->getAmountAsFloat(),
                    'currency' => $totalPrice->getCurrency()->getCurrencyCode()
                ];

                $intesaPrice = $this->moneyService->calculateIntesaPrice($totalPrice);
                $cashPrice = $this->moneyService->calculateCashPrice($countOfPassengers);
                $payPalPrice = $this->moneyService->calculatePayPalPrice($totalPrice);

                $airSolutionData['paymentOptionCharge'] = [
                    'cache' => [
                        'amount' => $cashPrice->getAmountAsFloat(),
                        'currency' => $cashPrice->getCurrency()->getCurrencyCode()
                    ],
                    'intesa' => [
                        'amount' => $intesaPrice->getAmountAsFloat(),
                        'currency' => $intesaPrice->getCurrency()->getCurrencyCode()
                    ],
                    'paypal' => [
                        'amount' => $payPalPrice->getAmountAsFloat(),
                        'currency' => $payPalPrice->getCurrency()->getCurrencyCode()
                    ]
                ];

                if ($airSolution->getFareNote()) {
                    /** @var FareNote $fareNote */
                    foreach ($airSolution->getFareNote() as $fareNote) {
                        $airSolutionData['fareNote'][] = [
                            'key' => $fareNote->getKey(),
                            'textNode' => $fareNote->get_(),
                        ];
                    }
                }

                if ($airSolution->getHostToken()) {
                    /** @var HostToken $hostToken */
                    foreach ($airSolution->getHostToken() as $hostToken) {
                        $airSolutionData['hostToken'][] = [
                            'key' => $hostToken->getKey(),
                            'textNode' => $hostToken->get_(),
                        ];
                    }
                }

                $airSolutionData['myChoice'] = $airSolutionPrice->isEqualTo($price);

                $airPriceResultData['airSolution'][] = $airSolutionData;
            }

            /** @var FareRule $fareRule */
            foreach ($airPriceResult->getFareRule() as $fareRule) {
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

        $results = collect(['groupsData' => $groupsData, 'info' => $infoData]);

        foreach ($airports as $airport) {
            $countries = $countries->add($airport->country);
            $cities = $cities->add($airport->city);
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

    public function AirReservationAdapt(AirCreateReservationRsp $response)
    {
        $bookingTravelerCollection = collect();
        $actionStatusCollection = collect();
        $providerReservationCollection = collect();
        $countries = collect();
        $cities = collect();
        $airports = collect();
        $airLines = collect();
        $aircrafts = collect();
        $countOfPassengers = 0;
        $countOfPassengersMap = collect();
        $totalPrice = Money::zero(MoneyService::AGENCY_CHARGE_CURRENCY);
        $accessCode = null;

        /** @var BookingTraveler $bookingTraveler */
        foreach ($response->getUniversalRecord()->getBookingTraveler() as $bookingTraveler) {

            $countOfPassengers++;

            $bookingTravelerData = [
                'key' => $bookingTraveler->getKey(),
                'travelerType' => $bookingTraveler->getTravelerType(),
                'age' => $bookingTraveler->getAge(),
                'vip' => $bookingTraveler->getVIP(),
                'dob' => $bookingTraveler->getDOB(),
                'gender' => $bookingTraveler->getGender(),
                'nationality' => $bookingTraveler->getNationality(),
                'elStat' => $bookingTraveler->getElStat(),
                'keyOverride' => $bookingTraveler->getKeyOverride(),
                'loyaltyCard' => $bookingTraveler->getLoyaltyCard(),
                'discountCard' => $bookingTraveler->getDiscountCard(),
                'ssr' => $bookingTraveler->getSSR(),
                'nameRemark' => $bookingTraveler->getNameRemark(),
                'airSeatAssignment' => $bookingTraveler->getAirSeatAssignment(),
                'railSeatAssignment' => $bookingTraveler->getRailSeatAssignment(),
                'emergencyInfo' => $bookingTraveler->getEmergencyInfo(),
                'driversLicense' => $bookingTraveler->getDriversLicense(),
                'appliedProfile' => $bookingTraveler->getAppliedProfile(),
                'customizedNameData' => $bookingTraveler->getCustomizedNameData(),
                'travelComplianceData' => $bookingTraveler->getTravelComplianceData(),
            ];

            $bookingTravelerData['bookingTravelerName'] = [
                'prefix' => $bookingTraveler->getBookingTravelerName()->getPrefix(),
                'first' => $bookingTraveler->getBookingTravelerName()->getFirst(),
                'middle' => $bookingTraveler->getBookingTravelerName()->getMiddle(),
                'last' => $bookingTraveler->getBookingTravelerName()->getLast(),
                'suffix' => $bookingTraveler->getBookingTravelerName()->getSuffix(),
            ];

            $deliveryInfoData = [];
            /** @var DeliveryInfo $deliveryInfo */
            foreach ($bookingTraveler->getDeliveryInfo() as $deliveryInfo) {

                $providerReservationInfoRef = [];
                if (!is_null($deliveryInfo->getProviderReservationInfoRef())) {
                    /** @var ProviderReservationInfoRef $reservationRefs */
                    foreach ($deliveryInfo->getProviderReservationInfoRef() as $reservationRefs) {
                        $providerReservationInfoRef[] = $reservationRefs->getKey();
                    }
                }

                $deliveryInfoData[]['shippingAddress'] = [
                    'key' => $deliveryInfo->getShippingAddress()->getKey(),
                    'AddressName' => $deliveryInfo->getShippingAddress()->getAddressName(),
                    'street' => $deliveryInfo->getShippingAddress()->getStreet(),
                    'city' => $deliveryInfo->getShippingAddress()->getCity(),
                    'state' => $deliveryInfo->getShippingAddress()->getState(),
                    'postalCode' => $deliveryInfo->getShippingAddress()->getPostalCode(),
                    'country' => $deliveryInfo->getShippingAddress()->getCountry(),
                    'elStat' => $deliveryInfo->getShippingAddress()->getElStat(),
                    'keyOverride' => $deliveryInfo->getShippingAddress()->getKeyOverride(),
                    'providerReservationInfoRef' => $providerReservationInfoRef
                ];
            }

            $bookingTravelerData['deliveryInfo'] = $deliveryInfoData;

            $phoneNumberData = [];
            /** @var PhoneNumber $phoneNumber */
            foreach ($bookingTraveler->getPhoneNumber() as $phoneNumber) {

                $providerReservationInfoRef = [];
                if (!is_null($phoneNumber->getProviderReservationInfoRef())) {
                    /** @var ProviderReservationInfoRef $reservationRefs */
                    foreach ($phoneNumber->getProviderReservationInfoRef() as $reservationRefs) {
                        $providerReservationInfoRef[] = $reservationRefs->getKey();
                    }
                }

                $phoneNumberData[] = [
                    'key' => $phoneNumber->getKey(),
                    'type' => $phoneNumber->getType(),
                    'location' => $phoneNumber->getLocation(),
                    'countryCode' => $phoneNumber->getCountryCode(),
                    'areaCode' => $phoneNumber->getAreaCode(),
                    'number' => $phoneNumber->getNumber(),
                    'extension' => $phoneNumber->getExtension(),
                    'text' => $phoneNumber->getText(),
                    'elStat' => $phoneNumber->getElStat(),
                    'keyOverride' => $phoneNumber->getKeyOverride(),
                    'providerReservationInfoRef' => $providerReservationInfoRef
                ];
            }

            $bookingTravelerData['phoneNumber'] = $phoneNumberData;

            $emailData = [];
            /** @var Email $email */
            foreach ($bookingTraveler->getEmail() as $email) {

                $providerReservationInfoRef = [];
                /** @var ProviderReservationInfoRef $reservationRefs */
                foreach ($email->getProviderReservationInfoRef() as $reservationRefs) {
                    $providerReservationInfoRef[] = $reservationRefs->getKey();
                }

                $emailData[] = [
                    'key' => $email->getKey(),
                    'type' => $email->getType(),
                    'comment' => $email->getComment(),
                    'emailID' => $email->getEmailID(),
                    'elStat' => $email->getElStat(),
                    'keyOverride' => $email->getKeyOverride(),
                    'providerReservationInfoRef' => $providerReservationInfoRef
                ];
            }

            $bookingTravelerData['email'] = $emailData;

            $addressData = [];
            /** @var typeStructuredAddress $address */
            foreach ($bookingTraveler->getAddress() as $address) {

                $providerReservationInfoRef = [];
                if (!is_null($address->getProviderReservationInfoRef())) {
                    /** @var ProviderReservationInfoRef $reservationRefs */
                    foreach ($address->getProviderReservationInfoRef() as $reservationRefs) {
                        $providerReservationInfoRef[] = $reservationRefs->getKey();
                    }
                }

                $addressData[] = [
                    'addressName' => $address->getAddressName(),
                    'street' => $address->getStreet(),
                    'city' => $address->getCity(),
                    'state' => $address->getState(),
                    'postalCode' => $address->getPostalCode(),
                    'country' => $address->getCountry(),
                    'elStat' => $address->getElStat(),
                    'keyOverride' => $address->getKeyOverride(),
                    'providerReservationInfoRef' => $providerReservationInfoRef
                ];
            }

            $bookingTravelerData['address'] = $addressData;

            $bookingTravelerCollection->add($bookingTravelerData);
        }

        /** @var ActionStatus $actionStatus */
        foreach ($response->getUniversalRecord()->getActionStatus() as $actionStatus) {
            $actionStatusCollection->add([
                'remark' => $actionStatus->getRemark(),
                'type' => $actionStatus->getType(),
                'ticketDate' => $actionStatus->getTicketDate(),
                'key' => $actionStatus->getKey(),
                'providerReservationInfoRef' => $actionStatus->getProviderReservationInfoRef(),
                'queueCategory' => $actionStatus->getQueueCategory(),
                'airportCode' => $actionStatus->getAirportCode(),
                'pseudoCityCode' => $actionStatus->getPseudoCityCode(),
                'accountCode' => $actionStatus->getAccountCode(),
                'providerCode' => $actionStatus->getProviderCode(),
                'supplierCode' => $actionStatus->getSupplierCode(),
                'elStat' => $actionStatus->getElStat(),
                'keyOverride' => $actionStatus->getKeyOverride(),
            ]);
        }

        /** @var \FilippoToso\Travelport\UniversalRecord\ProviderReservationInfo $providerReservation */
        foreach ($response->getUniversalRecord()->getProviderReservationInfo() as $providerReservation) {
            $accessCode = $providerReservation->LocatorCode;
            $providerReservationCollection->add([
                'providerCode' => $providerReservation->getProviderCode(),
                'providerLocatorCode' => $providerReservation->getProviderLocatorCode(),
                'supplierCode' => $providerReservation->getSupplierCode(),
                'key' => $providerReservation->Key,
                'locatorCode' => $providerReservation->LocatorCode,
                'createDate' => $providerReservation->CreateDate,
                'hostCreateDate' => $providerReservation->HostCreateDate,
                'modifiedDate' => $providerReservation->ModifiedDate,
                'owningPCC' => $providerReservation->OwningPCC,
            ]);
        }

        $airReservationData = [];
        /** @var \FilippoToso\Travelport\UniversalRecord\AirReservation $airReservation */
        foreach ($response->getUniversalRecord()->getAirReservation() as $airReservation) {
            $supplierLocatorData = [];
            if (!is_null($airReservation->getSupplierLocator())) {
                /** @var SupplierLocator $supplierLocator */
                foreach ($airReservation->getSupplierLocator() as $supplierLocator) {
                    $supplierLocatorData[] = [
                        'segmentRef' => $supplierLocator->getSegmentRef(),
                        'supplierCode' => $supplierLocator->getSupplierCode(),
                        'supplierLocatorCode' => $supplierLocator->getSupplierLocatorCode(),
                        'providerReservationInfoRef' => $supplierLocator->getProviderReservationInfoRef(),
                        'createDateTime' => $supplierLocator->getCreateDateTime(),
                    ];
                }
            }

            $airReservationData['supplierLocator'] = $supplierLocatorData;

            $bookingTravelerRefData = [];
            /** @var BookingTravelerRef $bookingTravelerRef */
            foreach ($airReservation->getBookingTravelerRef() as $bookingTravelerRef) {
                $bookingTravelerRefData[] = $bookingTravelerRef->getKey();
            }

            $airReservationData['bookingTravelerRef'] = $bookingTravelerRefData;

            $providerReservationInfoRefData = [];
            /** @var \FilippoToso\Travelport\UniversalRecord\ProviderReservationInfoRef $providerReservationInfoRef */
            foreach ($airReservation->getProviderReservationInfoRef() as $providerReservationInfoRef) {
                $providerReservationInfoRefData[] = $providerReservationInfoRef->getKey();
            }

            $airReservationData['providerReservationInfoRef'] = $providerReservationInfoRefData;

            $airSegmentData = [];
            /** @var \FilippoToso\Travelport\UniversalRecord\typeBaseAirSegment $airSegment */
            foreach ($airReservation->getAirSegment() as $airSegment) {
                $flightDetailsData = [];
                $origin = $airSegment->getOrigin();
                $destination = $airSegment->getDestination();
                $carrier = $airSegment->getCarrier();
                $aircraftType = $airSegment->getEquipment();

                if (!is_null($airSegment->getFlightDetails())) {
                    /** @var \FilippoToso\Travelport\UniversalRecord\FlightDetails $flightDetails */
                    foreach ($airSegment->getFlightDetails() as $flightDetails) {
                        $flightDetailsData[] = [
                            'key' => $flightDetails->getKey(),
                            'connection' => $flightDetails->getConnection(),
                            'meals' => $flightDetails->getMeals(),
                            'inFlightServices' => $flightDetails->getInFlightServices(),
                            'equipment' => $flightDetails->getEquipment(),
                            'onTimePerformance' => $flightDetails->getOnTimePerformance(),
                            'originTerminal' => $flightDetails->getOriginTerminal(),
                            'destinationTerminal' => $flightDetails->getDestinationTerminal(),
                            'groundTime' => $flightDetails->getGroundTime(),
                            'automatedCheckin' => $flightDetails->getAutomatedCheckin(),
                            'origin' => $flightDetails->getOrigin(),
                            'destination' => $flightDetails->getDestination(),
                            'departureTime' => $flightDetails->getDepartureTime(),
                            'arrivalTime' => $flightDetails->getArrivalTime(),
                            'flightTime' => $flightDetails->getFlightTime(),
                            'travelTime' => $flightDetails->getTravelTime(),
                            'distance' => $flightDetails->getDistance(),
                            'elStat' => $flightDetails->getElStat(),
                            'keyOverride' => $flightDetails->getKeyOverride(),
                        ];
                    }
                }

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

                $segmentRemarkData = [];
                if (!is_null($airSegment->getSegmentRemark())) {
                    /** @var SegmentRemark $segmentRemark */
                    foreach ($airSegment->getSegmentRemark() as $segmentRemark) {
                        $segmentRemarkData[] = [
                            '_' => $segmentRemark->get_(),
                            'key' => $segmentRemark->getKey(),
                        ];
                    }
                }

                $connection = [];
                if (!is_null($airSegment->getConnection())) {
                    $connection = [
                        'fareNote' => $airSegment->getConnection()->getFareNote(),
                        'changeOfPlane' => $airSegment->getConnection()->getChangeOfPlane(),
                        'changeOfTerminal' => $airSegment->getConnection()->getChangeOfTerminal(),
                        'changeOfAirport' => $airSegment->getConnection()->getChangeOfAirport(),
                        'stopover' => $airSegment->getConnection()->getStopOver(),
                        'minConnectionTime' => $airSegment->getConnection()->getMinConnectionTime(),
                        'duration' => $airSegment->getConnection()->getDuration(),
                        'segmentIndex' => $airSegment->getConnection()->getSegmentIndex(),
                        'flightDetailsIndex' => $airSegment->getConnection()->getFlightDetailsIndex(),
                        'includeStopOverToFareQuote' => $airSegment->getConnection()->getIncludeStopOverToFareQuote(),
                    ];
                }

                $airSegmentData[] = [
                    'sponsoredFltInfo' => $airSegment->getSponsoredFltInfo(),
                    'codeShareInfo' => $airSegment->getCodeshareInfo(),
                    'flightDetails' => $flightDetailsData,
                    'flightDetailsRef' => $airSegment->getFlightDetailsRef(),
                    'alternateLocationDistanceRef' => $airSegment->getAlternateLocationDistanceRef(),
                    'sellMessage' => $airSegment->getSellMessage(),
                    'railCoachDetails' => $airSegment->getRailCoachDetails(),
                    'openSegment' => $airSegment->getOpenSegment(),
                    'group' => $airSegment->getGroup(),
                    'carrier' => $airSegment->getCarrier(),
                    'cabinClass' => $airSegment->getCabinClass(),
                    'flightNumber' => $airSegment->getFlightNumber(),
                    'classOfService' => $airSegment->getClassOfService(),
                    'eTicketAbility' => $airSegment->getETicketability(),
                    'equipment' => $airSegment->getEquipment(),
                    'marriageGroup' => $airSegment->getMarriageGroup(),
                    'numberOfStops' => $airSegment->getNumberOfStops(),
                    'connection' => $connection,
                    'seamless' => $airSegment->getSeamless(),
                    'changeOfPlane' => $airSegment->getChangeOfPlane(),
                    'guaranteedPaymentCarrier' => $airSegment->getGuaranteedPaymentCarrier(),
                    'hostTokenRef' => $airSegment->getHostTokenRef(),
                    'providerReservationInfoRef' => $airSegment->getProviderReservationInfoRef(),
                    'passiveProviderReservationInfoRef' => $airSegment->getPassiveProviderReservationInfoRef(),
                    'optionalServicesIndicator' => $airSegment->getOptionalServicesIndicator(),
                    'availabilitySource' => $airSegment->getAvailabilitySource(),
                    'APISRequirementsRef' => $airSegment->getAPISRequirementsRef(),
                    'blackListed' => $airSegment->getBlackListed(),
                    'operationalStatus' => $airSegment->getOperationalStatus(),
                    'numberInParty' => $airSegment->getNumberInParty(),
                    'railCoachNumber' => $airSegment->getRailCoachNumber(),
                    'bookingDate' => $airSegment->getBookingDate(),
                    'flownSegment' => $airSegment->getFlownSegment(),
                    'scheduleChange' => $airSegment->getScheduleChange(),
                    'brandIndicator' => $airSegment->getBrandIndicator(),
                    'origin' => $airSegment->getOrigin(),
                    'destination' => $airSegment->getDestination(),
                    'departureTime' => $airSegment->getDepartureTime(),
                    'arrivalTime' => $airSegment->getArrivalTime(),
                    'flightTime' => $airSegment->getFlightTime(),
                    'travelTime' => $airSegment->getTravelTime(),
                    'distance' => $airSegment->getDistance(),
                    'providerCode' => $airSegment->getProviderCode(),
                    'supplierCode' => $airSegment->getSupplierCode(),
                    'participantLevel' => $airSegment->getParticipantLevel(),
                    'linkAvailability' => $airSegment->getLinkAvailability(),
                    'polledAvailabilityOption' => $airSegment->getPolledAvailabilityOption(),
                    'availabilityDisplayType' => $airSegment->getAvailabilityDisplayType(),
                    'segmentRemark' => $segmentRemarkData,
                    'key' => $airSegment->getKey(),
                    'status' => $airSegment->getStatus(),
                    'passive' => $airSegment->getPassive(),
                    'travelOrder' => $airSegment->getTravelOrder(),
                    'providerSegmentOrder' => $airSegment->getProviderSegmentOrder(),
                    'elStat' => $airSegment->getElStat(),
                    'keyOverride' => $airSegment->getKeyOverride(),
                ];
            }

            $airReservationData['airSegmentInfo'] = $airSegmentData;

            $airPricingInfoData = [];
            if (!is_null($airReservation->getAirPricingInfo())) {
                /** @var \FilippoToso\Travelport\UniversalRecord\AirPricingInfo $airPricingInfo */
                foreach ($airReservation->getAirPricingInfo() as $airPricingInfo) {
                    $airPricingInfoPrice = $this->moneyService->getMoneyByString($airPricingInfo->getTotalPrice());
                    $fareInfoData = [];
                    /** @var \FilippoToso\Travelport\UniversalRecord\FareInfo $fareInfo */
                    foreach ($airPricingInfo->getFareInfo() as $fareInfo) {
                        $endorsementData = [];
                        if (!is_null($fareInfo->getEndorsement())) {
                            /** @var Endorsement $endorsement */
                            foreach ($fareInfo->getEndorsement() as $endorsement) {
                                $endorsementData[] = [
                                    'value' => $endorsement->getValue(),
                                ];
                            }
                        }

                        $fareInfoData[] = [
                            'fareTicketDesignator' => $fareInfo->getFareTicketDesignator(),
                            'fareSurcharge' => $fareInfo->getFareSurcharge(),
                            'accountCode' => $fareInfo->getAccountCode(),
                            'contractCode' => $fareInfo->getContractCode(),
                            'endorsement' => $endorsementData,
                            'baggageAllowance' => [
                                'NumberOfPieces' => $fareInfo->getBaggageAllowance()->getNumberOfPieces(),
                                'maxWeight' => $fareInfo->getBaggageAllowance()->getMaxWeight(),
                            ],
                            'fareRuleKey' => $fareInfo->getFareRuleKey(),
                            'fareRuleFailureInfo' => $fareInfo->getFareRuleFailureInfo(),
                            'fareRemarkRef' => $fareInfo->getFareRemarkRef(),
                            'brand' => $fareInfo->getBrand(), //??
                            'commission' => $fareInfo->getCommission(),
                            'key' => $fareInfo->getKey(),
                            'fareBasis' => $fareInfo->getFareBasis(),
                            'passengerTypeCode' => $fareInfo->getPassengerTypeCode(),
                            'origin' => $fareInfo->getOrigin(),
                            'destination' => $fareInfo->getDestination(),
                            'effectiveDate' => $fareInfo->getEffectiveDate(),
                            'travelDate' => $fareInfo->getTravelDate(),
                            'departureDate' => $fareInfo->getDepartureDate(),
                            'privateFare' => $fareInfo->getPrivateFare(),
                            'negotiatedFare' => $fareInfo->getNegotiatedFare(),
                            'pseudoCityCode' => $fareInfo->getPseudoCityCode(),
                            'fareFamily' => $fareInfo->getFareFamily(),
                            'promotionalFare' => $fareInfo->getPromotionalFare(),
                            'supplierCode' => $fareInfo->getSupplierCode(),
                            'elStat' => $fareInfo->getElStat(),
                            'keyOverride' => $fareInfo->getKeyOverride(),
                        ];
                    }

                    $bookingInfoData = [];
                    /** @var \FilippoToso\Travelport\UniversalRecord\BookingInfo $bookingInfo */
                    foreach ($airPricingInfo->getBookingInfo() as $bookingInfo) {
                        $bookingInfoData[] = [
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

                    $taxInfoData = [];
                    if (!is_null($airPricingInfo->getTaxInfo())) {
                        /** @var \FilippoToso\Travelport\UniversalRecord\typeTaxInfo $taxInfo */
                        foreach ($airPricingInfo->getTaxInfo() as $taxInfo) {
                            $taxInfoPrice = $this->moneyService->getMoneyByString($taxInfo->getAmount());
                            $taxInfoData[] = [
                                'taxDetail' => $taxInfo->getTaxDetail(),
                                'includedInBase' => $taxInfo->getIncludedInBase(),
                                'key' => $taxInfo->getKey(),
                                'category' => $taxInfo->getCategory(),
                                'carrierDefinedCategory' => $taxInfo->getCarrierDefinedCategory(),
                                'segmentRef' => $taxInfo->getSegmentRef(),
                                'flightDetailsRef' => $taxInfo->getFlightDetailsRef(),
                                'couponRef' => $taxInfo->getCouponRef(),
                                'taxExempted' => $taxInfo->getTaxExempted(),
                                'providerCode' => $taxInfo->getProviderCode(),
                                'supplierCode' => $taxInfo->getSupplierCode(),
                                'text' => $taxInfo->getText(),
                                'amount' => [
                                    'value' => $taxInfoPrice->getAmountAsFloat(),
                                    'currency' => $taxInfoPrice->getCurrency()->getCurrencyCode()
                                ],
                                'originAirport' => $taxInfo->getOriginAirport(),
                                'destinationAirport' => $taxInfo->getDestinationAirport(),
                                'countryCode' => $taxInfo->getCountryCode(),
                                'fareInfoRef' => $taxInfo->getFareInfoRef(),
                            ];
                        }
                    }

                    $passengerTypeData = [];
                    /** @var PassengerType $passengerTpe */
                    foreach ($airPricingInfo->getPassengerType() as $passengerTpe) {
                        $passengerTypeData[] = [
                            'fareGuaranteeInfo' => [
                                'guaranteeDate' => $passengerTpe->getFareGuaranteeInfo()->getGuaranteeDate(),
                                'guaranteeType' => $passengerTpe->getFareGuaranteeInfo()->getGuaranteeType()
                            ],
                            'name' => $passengerTpe->getName(),
                            'loyaltyCard' => $passengerTpe->getLoyaltyCard(),
                            'discountCard' => $passengerTpe->getDiscountCard(),
                            'personalGeography' => $passengerTpe->getPersonalGeography(),
                            'code' => $passengerTpe->getCode(),
                            'age' => $passengerTpe->getAge(),
                            'dob' => $passengerTpe->getDOB(),
                            'gender' => $passengerTpe->getGender(),
                            'pricePTCOnly' => $passengerTpe->getPricePTCOnly(),
                            'bookingTravelerRef' => $passengerTpe->getBookingTravelerRef(),
                            'accompaniedPassenger' => $passengerTpe->getAccompaniedPassenger(),
                            'residencyType' => $passengerTpe->getResidencyType(),
                        ];

                        $code = (string)$passengerTpe->getCode();
                        $countOfPassengersMap->put($code, $countOfPassengersMap->get($code, 0) + 1);
                        $totalPrice = $this->moneyService->addAgencyChargeByPassengerType($totalPrice, $code);
                        $totalPrice = $totalPrice->plus($airPricingInfoPrice);
                    }

                    $bookingTravelerRefData = [];
                    /** @var BookingTravelerRef $bookingTravelerRef */
                    foreach ($airPricingInfo->getBookingTravelerRef() as $bookingTravelerRef) {
                        $bookingTravelerRefData[] = $bookingTravelerRef->getKey();
                    }

                    $ticketingModifiersRefData = [];
                    /** @var TicketingModifiersRef $ticketingModifiersRef */
                    foreach ($airPricingInfo->getTicketingModifiersRef() as $ticketingModifiersRef) {
                        $ticketingModifiersRefData[] = $ticketingModifiersRef->getKey();
                    }

                    $changePenaltyData = [];
                    if (!is_null($airPricingInfo->getChangePenalty())) {
                        /** @var \FilippoToso\Travelport\UniversalRecord\typeFarePenalty $changePenalty */
                        foreach ($airPricingInfo->getChangePenalty() as $changePenalty) {
                            $changePenaltyPrice = $this->moneyService->getMoneyByString($changePenalty->getAmount());
                            $changePenaltyData[] = [
                                'amount' => [
                                    'value' => $changePenaltyPrice->getAmountAsFloat(),
                                    'currency' => $changePenaltyPrice->getCurrency()->getCurrencyCode()
                                ],
                                'percentage' => $changePenalty->getPercentage(),
                                'penaltyApplies' => $changePenalty->getPenaltyApplies(),
                                'noShow' => $changePenalty->getNoShow(),
                            ];
                        }
                    }

                    $cancelPenaltyData = [];
                    if (!is_null($airPricingInfo->getCancelPenalty())) {
                        /** @var \FilippoToso\Travelport\UniversalRecord\typeFarePenalty $cancelPenalty */
                        foreach ($airPricingInfo->getCancelPenalty() as $cancelPenalty) {
                            $cancelPenaltyPrice = $this->moneyService->getMoneyByString($cancelPenalty->getAmount());
                            $cancelPenaltyData[] = [
                                'amount' => [
                                    'value' => $cancelPenaltyPrice->getAmountAsFloat(),
                                    'currency' => $cancelPenaltyPrice->getCurrency()->getCurrencyCode()
                                ],
                                'percentage' => $cancelPenalty->getPercentage(),
                                'penaltyApplies' => $cancelPenalty->getPenaltyApplies(),
                                'noShow' => $cancelPenalty->getNoShow(),
                            ];
                        }
                    }

                    $basePrice = $this->moneyService->getMoneyByString($airPricingInfo->getBasePrice());
                    $approximateTotalPrice = $this->moneyService->getMoneyByString($airPricingInfo->getApproximateTotalPrice());
                    $approximateBasePrice = $this->moneyService->getMoneyByString($airPricingInfo->getApproximateBasePrice());
                    $equivalentBasePrice = $this->moneyService->getMoneyByString($airPricingInfo->getEquivalentBasePrice());
                    $taxesPrice = $this->moneyService->getMoneyByString($airPricingInfo->getTaxes());
                    $feesPrice = $this->moneyService->getMoneyByString($airPricingInfo->getFees());

                    $airPricingInfoData[] = [
                        'fareInfo' => $fareInfoData,
                        'fareStatus' => $airPricingInfo->getFareStatus(),
                        'fareInfoRef' => $airPricingInfo->getFareInfoRef(),
                        'waiverCode' => $airPricingInfo->getWaiverCode(),
                        'paymentRef' => $airPricingInfo->getPaymentRef(),
                        'bookingInfo' => $bookingInfoData,
                        'taxInfo' => $taxInfoData,
                        'fareCalc' => $airPricingInfo->getFareCalc(),
                        'passengerType' => $passengerTypeData,
                        'bookingTravelerRef' => $bookingTravelerRefData,
                        'changePenalty' => $changePenaltyData,
                        'cancelPenalty' => $cancelPenaltyData,
                        'ticketingModifiersRef' => $ticketingModifiersRefData,
                        'airSegmentPricingModifiers' => $airPricingInfo->getAirSegmentPricingModifiers(),
//                    'flightOptionsList' => $airPricingInfo->getAirSegmentPricingModifiers(), ??
                        'key' => $airPricingInfo->getKey(),
                        'refundable' => $airPricingInfo->getRefundable(),
                        'exchangeable' => $airPricingInfo->getExchangeable(),
                        'commandKey' => $airPricingInfo->getCommandKey(),
                        'amountType' => $airPricingInfo->getAmountType(),
                        'includesVAT' => $airPricingInfo->getIncludesVAT(),
                        'exchangeAmount' => $airPricingInfo->getExchangeAmount(),
                        'latestTicketingTime' => $airPricingInfo->getTrueLastDateToTicket(),
                        'pricingMethod' => $airPricingInfo->getPricingMethod(),
                        'checksum' => $airPricingInfo->getChecksum(),
                        'eTicketAbility' => $airPricingInfo->getETicketability(),
                        'platingCarrier' => $airPricingInfo->getPlatingCarrier(),
                        'providerReservationInfoRef' => $airPricingInfo->getProviderReservationInfoRef(),
                        'airPricingInfoGroup' => $airPricingInfo->getAirPricingInfoGroup(),
                        'totalNetPrice' => $airPricingInfo->getTotalNetPrice(),
                        'ticketed' => $airPricingInfo->getTicketed(),
                        'pricingType' => $airPricingInfo->getPricingType(),
                        'trueLastDateToTicket' => $airPricingInfo->getTrueLastDateToTicket(),
                        'fareCalculationInd' => $airPricingInfo->getFareCalculationInd(),
                        'cat35Indicator' => $airPricingInfo->getCat35Indicator(),
                        'totalPrice' => [
                            'amount' => $airPricingInfoPrice->getAmountAsFloat(),
                            'currency' => $airPricingInfoPrice->getCurrency()->getCurrencyCode()
                        ],
                        'basePrice' => [
                            'amount' => $basePrice->getAmountAsFloat(),
                            'currency' => $basePrice->getCurrency()->getCurrencyCode()
                        ],
                        'approximateTotalPrice' => [
                            'amount' => $approximateTotalPrice->getAmountAsFloat(),
                            'currency' => $approximateTotalPrice->getCurrency()->getCurrencyCode()
                        ],
                        'approximateBasePrice' => [
                            'amount' => $approximateBasePrice->getAmountAsFloat(),
                            'currency' => $approximateBasePrice->getCurrency()->getCurrencyCode()
                        ],
                        'equivalentBasePrice' => [
                            'amount' => $equivalentBasePrice->getAmountAsFloat(),
                            'currency' => $equivalentBasePrice->getCurrency()->getCurrencyCode()
                        ],
                        'taxes' => [
                            'amount' => $taxesPrice->getAmountAsFloat(),
                            'currency' => $taxesPrice->getCurrency()->getCurrencyCode()
                        ],
                        'fees' => [
                            'amount' => $feesPrice->getAmountAsFloat(),
                            'currency' => $feesPrice->getCurrency()->getCurrencyCode()
                        ],
                        'providerCode' => $airPricingInfo->getProviderCode(),
                        'supplierCode' => $airPricingInfo->getSupplierCode(),
                        'elStat' => $airPricingInfo->getElStat(),
                    ];
                }
            }

            $ticketingModifiersData = [];
            if (!is_null($airReservation->getTicketingModifiers())) {
                /** @var TicketingModifiers $ticketingModifiers */
                foreach ($airReservation->getTicketingModifiers() as $ticketingModifiers) {
                    $ticketingModifiersData[] = [
                        'key' => $ticketingModifiers->getKey(),
                        'platingCarrier' => $ticketingModifiers->getPlatingCarrier(),
                        'elStat' => $ticketingModifiers->getElStat(),
                        'documentSelect' => [
                            'IssueElectronicTicket' => $ticketingModifiers->getDocumentSelect()->getIssueElectronicTicket(),
                        ],
                    ];
                }
            }

            $airReservationData['airPricingInfo'] = $airPricingInfoData;
            $airReservationData['ticketingModifiers'] = $ticketingModifiersData;
            $airReservationData['locatorCode'] = $airReservation->getLocatorCode();
            $airReservationData['createDate'] = $airReservation->getCreateDate();
            $airReservationData['modifiedDate'] = $airReservation->getModifiedDate();
        }

        $agencyInfoData = [];
        /** @var AgentAction $agentAction */
        foreach ($response->getUniversalRecord()->getAgencyInfo()->getAgentAction() as $agentAction) {
            $agencyInfoData['agentAction'][] = [
                'actionType' => $agentAction->getActionType(),
                'agentCode' => $agentAction->getAgencyCode(),
                'brandCode' => $agentAction->getBranchCode(),
                'agencyCode' => $agentAction->getAgencyCode(),
                'agentSine' => $agentAction->getAgentSine(),
                'eventTime' => $agentAction->getEventTime(),
                'agentOverride' => $agentAction->getAgentOverride(),
            ];
        }

        $formOfPaymentData = [];
        /** @var FormOfPayment $formOfPayment */
        foreach ($response->getUniversalRecord()->getFormOfPayment() as $formOfPayment) {

            $providerReservationInfoRefData = [];
            /** @var typeFormOfPaymentPNRReference $providerReservationInfoRef */
            foreach ($formOfPayment->getProviderReservationInfoRef() as $providerReservationInfoRef) {
                $providerReservationInfoRefData[] = [
                    'key' => $providerReservationInfoRef->getKey(),
                    'providerReservationLevel' => $providerReservationInfoRef->getProviderReservationLevel(),
                ];
            }

            $formOfPaymentData[] = [
                'check' => [
                    'MICRNumber' => $formOfPayment->getCheck()->getMICRNumber(),
                    'routingNumber' => $formOfPayment->getCheck()->getRoutingNumber(),
                    'accountNumber' => $formOfPayment->getCheck()->getAccountNumber(),
                    'checkNumber' => $formOfPayment->getCheck()->getCheckNumber(),
                ],
                'providerReservationInfoRef' => $providerReservationInfoRefData
            ];
        }

        $airSolutionChangeInfoData = [];
        if (!is_null($response->getAirSolutionChangedInfo())) {
            /** @var AirSolutionChangedInfo $airSolutionChangeInfo */
            foreach ($response->getAirSolutionChangedInfo() as $airSolutionChangeInfo) {
                $airSegmentData = [];
                /** @var typeBaseAirSegment $airSegment */
                foreach ($airSolutionChangeInfo->getAirPricingSolution()->getAirSegment() as $airSegment) {
                    $flightDetailsData = [];
                    $origin = $airSegment->getOrigin();
                    $destination = $airSegment->getDestination();
                    $carrier = $airSegment->getCarrier();
                    $aircraftType = $airSegment->getEquipment();

                    if (!is_null($airSegment->getFlightDetails())) {
                        /** @var \FilippoToso\Travelport\UniversalRecord\FlightDetails $flightDetails */
                        foreach ($airSegment->getFlightDetails() as $flightDetails) {
                            $flightDetailsData[] = [
                                'key' => $flightDetails->getKey(),
                                'connection' => $flightDetails->getConnection(),
                                'meals' => $flightDetails->getMeals(),
                                'inFlightServices' => $flightDetails->getInFlightServices(),
                                'equipment' => $flightDetails->getEquipment(),
                                'onTimePerformance' => $flightDetails->getOnTimePerformance(),
                                'originTerminal' => $flightDetails->getOriginTerminal(),
                                'destinationTerminal' => $flightDetails->getDestinationTerminal(),
                                'groundTime' => $flightDetails->getGroundTime(),
                                'automatedCheckin' => $flightDetails->getAutomatedCheckin(),
                                'origin' => $flightDetails->getOrigin(),
                                'destination' => $flightDetails->getDestination(),
                                'departureTime' => $flightDetails->getDepartureTime(),
                                'arrivalTime' => $flightDetails->getArrivalTime(),
                                'flightTime' => $flightDetails->getFlightTime(),
                                'travelTime' => $flightDetails->getTravelTime(),
                                'distance' => $flightDetails->getDistance(),
                                'elStat' => $flightDetails->getElStat(),
                                'keyOverride' => $flightDetails->getKeyOverride(),
                            ];
                        }
                    }

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

                    $segmentRemarkData = [];
                    if (!is_null($airSegment->getSegmentRemark())) {
                        /** @var SegmentRemark $segmentRemark */
                        foreach ($airSegment->getSegmentRemark() as $segmentRemark) {
                            $segmentRemarkData[] = [
                                '_' => $segmentRemark->get_(),
                                'key' => $segmentRemark->getKey(),
                            ];
                        }
                    }

                    $connection = [];
                    if (!is_null($airSegment->getConnection())) {
                        $connection = [
                            'fareNote' => $airSegment->getConnection()->getFareNote(),
                            'changeOfPlane' => $airSegment->getConnection()->getChangeOfPlane(),
                            'changeOfTerminal' => $airSegment->getConnection()->getChangeOfTerminal(),
                            'changeOfAirport' => $airSegment->getConnection()->getChangeOfAirport(),
                            'stopover' => $airSegment->getConnection()->getStopOver(),
                            'minConnectionTime' => $airSegment->getConnection()->getMinConnectionTime(),
                            'duration' => $airSegment->getConnection()->getDuration(),
                            'segmentIndex' => $airSegment->getConnection()->getSegmentIndex(),
                            'flightDetailsIndex' => $airSegment->getConnection()->getFlightDetailsIndex(),
                            'includeStopOverToFareQuote' => $airSegment->getConnection()->getIncludeStopOverToFareQuote(),
                        ];
                    }

                    $airSegmentData[] = [
                        'sponsoredFltInfo' => $airSegment->getSponsoredFltInfo(),
                        'codeShareInfo' => $airSegment->getCodeshareInfo(),
                        'flightDetails' => $flightDetailsData,
                        'flightDetailsRef' => $airSegment->getFlightDetailsRef(),
                        'alternateLocationDistanceRef' => $airSegment->getAlternateLocationDistanceRef(),
                        'sellMessage' => $airSegment->getSellMessage(),
                        'railCoachDetails' => $airSegment->getRailCoachDetails(),
                        'openSegment' => $airSegment->getOpenSegment(),
                        'group' => $airSegment->getGroup(),
                        'carrier' => $airSegment->getCarrier(),
                        'cabinClass' => $airSegment->getCabinClass(),
                        'flightNumber' => $airSegment->getFlightNumber(),
                        'classOfService' => $airSegment->getClassOfService(),
                        'eTicketAbility' => $airSegment->getETicketability(),
                        'equipment' => $airSegment->getEquipment(),
                        'marriageGroup' => $airSegment->getMarriageGroup(),
                        'numberOfStops' => $airSegment->getNumberOfStops(),
                        'connection' => $connection,
                        'seamless' => $airSegment->getSeamless(),
                        'changeOfPlane' => $airSegment->getChangeOfPlane(),
                        'guaranteedPaymentCarrier' => $airSegment->getGuaranteedPaymentCarrier(),
                        'hostTokenRef' => $airSegment->getHostTokenRef(),
                        'providerReservationInfoRef' => $airSegment->getProviderReservationInfoRef(),
                        'passiveProviderReservationInfoRef' => $airSegment->getPassiveProviderReservationInfoRef(),
                        'optionalServicesIndicator' => $airSegment->getOptionalServicesIndicator(),
                        'availabilitySource' => $airSegment->getAvailabilitySource(),
                        'APISRequirementsRef' => $airSegment->getAPISRequirementsRef(),
                        'blackListed' => $airSegment->getBlackListed(),
                        'operationalStatus' => $airSegment->getOperationalStatus(),
                        'numberInParty' => $airSegment->getNumberInParty(),
                        'railCoachNumber' => $airSegment->getRailCoachNumber(),
                        'bookingDate' => $airSegment->getBookingDate(),
                        'flownSegment' => $airSegment->getFlownSegment(),
                        'scheduleChange' => $airSegment->getScheduleChange(),
                        'brandIndicator' => $airSegment->getBrandIndicator(),
                        'origin' => $airSegment->getOrigin(),
                        'destination' => $airSegment->getDestination(),
                        'departureTime' => $airSegment->getDepartureTime(),
                        'arrivalTime' => $airSegment->getArrivalTime(),
                        'flightTime' => $airSegment->getFlightTime(),
                        'travelTime' => $airSegment->getTravelTime(),
                        'distance' => $airSegment->getDistance(),
                        'providerCode' => $airSegment->getProviderCode(),
                        'supplierCode' => $airSegment->getSupplierCode(),
                        'participantLevel' => $airSegment->getParticipantLevel(),
                        'linkAvailability' => $airSegment->getLinkAvailability(),
                        'polledAvailabilityOption' => $airSegment->getPolledAvailabilityOption(),
                        'availabilityDisplayType' => $airSegment->getAvailabilityDisplayType(),
                        'segmentRemark' => $segmentRemarkData,
                        'key' => $airSegment->getKey(),
                        'status' => $airSegment->getStatus(),
                        'passive' => $airSegment->getPassive(),
                        'travelOrder' => $airSegment->getTravelOrder(),
                        'providerSegmentOrder' => $airSegment->getProviderSegmentOrder(),
                        'elStat' => $airSegment->getElStat(),
                        'keyOverride' => $airSegment->getKeyOverride(),
                    ];
                }

                $airSolutionChangeInfoData['airSegmentInfo'] = $airSegmentData;
            }
        }

        foreach ($airports as $airport) {
            $countries = $countries->add($airport->country);
            $cities = $cities->add($airport->city);
        }

        $agencyChargeAll = $this->moneyService->getAgencyChargeForAllPassengers($countOfPassengers);
        $intesaPrice = $this->moneyService->calculateIntesaPrice($totalPrice);
        $cashPrice = $this->moneyService->calculateCashPrice($countOfPassengers);
        $payPalPrice = $this->moneyService->calculatePayPalPrice($totalPrice);
        $totalPrice = $totalPrice->plus($intesaPrice)->plus($cashPrice);

        return collect([
            'universalRecord' => [
                'bookingTraveler' => $bookingTravelerCollection,
                'agencyCharge' => [
                    'amount' => $agencyChargeAll->getAmountAsFloat(),
                    'currency' => $agencyChargeAll->getCurrency()->getCurrencyCode(),
                    'regular' => [
                        static::PASSENGER_TYPE_ADULT => MoneyService::AGENCY_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_CHILD => MoneyService::AGENCY_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_INFANT => MoneyService::AGENCY_CHARGE_AMOUNT,
                    ],
                    'brand' => [
                        static::PASSENGER_TYPE_ADULT => MoneyService::BRAND_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_CHILD => MoneyService::BRAND_CHARGE_AMOUNT,
                        static::PASSENGER_TYPE_INFANT => MoneyService::BRAND_CHARGE_AMOUNT,
                    ]
                ],
                'paymentOptionCharge' => [
                    'cache' => [
                        'amount' => $cashPrice->getAmountAsFloat(),
                        'currency' => $cashPrice->getCurrency()->getCurrencyCode()
                    ],
                    'intesa' => [
                        'amount' => $intesaPrice->getAmountAsFloat(),
                        'currency' => $intesaPrice->getCurrency()->getCurrencyCode()
                    ],
                    'paypal' => [
                        'amount' => $payPalPrice->getAmountAsFloat(),
                        'currency' => $payPalPrice->getCurrency()->getCurrencyCode()
                    ]
                ],
                'actionStatus' => $actionStatusCollection,
                'providerReservationInfo' => $providerReservationCollection,
                'airReservation' => $airReservationData,
                'agencyInfo' => $agencyInfoData,
                'formOfPayment' => $formOfPaymentData,
                'locatorCode' => $response->getUniversalRecord()->getLocatorCode(),
                'status' => $response->getUniversalRecord()->getStatus(),
                'version' => $response->getUniversalRecord()->getVersion(),
            ],
            'airSolutionChangeInfo' => $airSolutionChangeInfoData,
            'accessCode' => $accessCode,
            'responseTime' => $response->getResponseTime(),
            'airlines' => $airLines,
            'airports' => $airports,
            'aircrafts' => $aircrafts,
            'cities' => $cities,
            'countries' => $countries,
            'passengersCount' => $countOfPassengersMap,
            'totalPrice' => [
                'amount' => $totalPrice->getAmountAsFloat(),
                'currency' => $totalPrice->getCurrency()->getCurrencyCode(),
            ]
        ]);
    }

    public function airFareRulesAdapt(AirFareRulesRsp $response)
    {
        $fareRuleData = [];
        /** @var FareRule $fareRule */
        foreach ($response->getFareRule() as $key => $fareRule) {
            /** @var FareRuleLong $fareRuleLong */
            foreach ($fareRule->getFareRuleLong() as $fareRuleLong) {
                $fareRuleData[$key][] = [
                    'name' => trim(strstr($fareRuleLong->get_(), "\n", true)),
                    'text' => trim(strstr($fareRuleLong->get_(), "\n")),
                    'code' => $fareRuleLong->getCategory(),
                    'segmentNumber' => $key,
                    'passengerTypes' => [static::PASSENGER_TYPE_ADULT],
                    'isURL' => false,
                    'tariffCode' => static::PASSENGER_TYPE_ADULT
                ];
            }
        }

        return $fareRuleData;
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