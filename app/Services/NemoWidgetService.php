<?php


namespace App\Services;


use App\Adapters\ModelAdapter;
use App\Adapters\FtObjectAdapter;
use App\Adapters\XmlAdapter;
use App\Dto\AirPriceRequestDto;
use App\Dto\FlightsSearchRequestDto;
use App\Exceptions\NemoWidgetServiceException;
use App\Exceptions\TravelPortException;
use App\Facades\TP;
use App\Models\Airline;
use App\Models\Country;
use App\Models\FlightsSearchFlightInfo;
use App\Models\FlightsSearchRequest;
use App\Models\FlightsSearchResult;
use App\Models\VocabularyName;
use App\Models\FlightsSearchRequest as FlightsSearchRequestModel;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\AirPricingInfo;
use FilippoToso\Travelport\Air\BookingInfo;
use FilippoToso\Travelport\Air\Connection;
use FilippoToso\Travelport\Air\FareInfo;
use FilippoToso\Travelport\Air\FareInfoRef;
use FilippoToso\Travelport\Air\FlightOption;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use FilippoToso\Travelport\Air\Option;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use FilippoToso\Travelport\TravelportLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NemoWidgetService
{
    protected $ftObjectAdapter;
    protected $modelAdapter;
    protected $xmlAdapter;
    protected $logger;

    public function __construct(
        FtObjectAdapter $ftObjectAdapter,
        ModelAdapter $modelAdapter,
        XmlAdapter $xmlAdapter,
        TravelportLogger $logger
    )
    {
        $this->ftObjectAdapter = $ftObjectAdapter;
        $this->modelAdapter = $modelAdapter;
        $this->xmlAdapter = $xmlAdapter;
        $this->logger = $logger;
    }

    public function autocomplete($q, $iataCode = null)
    {
        $result = VocabularyName::cacheStatic('getByName', $q);

        if (null !== $iataCode) {
            $result = $result->reject(function ($element) use ($iataCode) {
                return $element->nameable->code === $iataCode;
            });
        }

        $result = $this->modelAdapter->autocompleteAdapt($result);

        return $result;
    }

    /**
     * @return mixed
     */
    public function airlinesAll()
    {
        return Airline::cacheStatic('getAll');
    }

    /**
     * @return mixed
     */
    public function countriesAll()
    {
        return Country::cacheStatic('getAll');
    }

    /**
     * @param FlightsSearchRequestDto $dto
     * @return Collection
     */
    public function flightsSearchRequest(FlightsSearchRequestDto $dto): Collection
    {
        $fsrModel = FlightsSearchRequestModel::forceCreate([
            'data' => $dto
        ]);

        return $this->modelAdapter->flightsSearchRequestAdapt($fsrModel);
    }

    /**
     * @param FlightsSearchRequestModel $request
     * @return Collection
     */
    public function flightsSearchResult(FlightsSearchRequest $request)
    {
        $requestDto = new FlightsSearchRequestDto(
            $request->data['segments'],
            $request->data['passengers'],
            $request->data['parameters']
        );

        try {
//            $lowFareSearchRsp = Cache::rememberForever('result'. $request->id, function () use ($requestDto) {
//                return TP::LowFareSearchReq($requestDto);
//            });
//
            $lowFareSearchRsp = TP::LowFareSearchReq($requestDto);

//            print_r('<pre>');
//            print_r($lowFareSearchRsp);die;

            $LowFareSearchAdapt = $this->ftObjectAdapter->LowFareSearchAdapt($lowFareSearchRsp, $request->id);
            $LowFareSearchAdapt->put('request', $request);

            $request->transaction_id = $lowFareSearchRsp->getTransactionId();

            return $LowFareSearchAdapt;
        } catch (TravelPortException $travelPortException) {
            $request->transaction_id = $travelPortException->getTransactionId();
            $flightsSearchRequestAdapt = $this->modelAdapter->flightsSearchRequestAdapt($request);
            $flightsSearchRequestAdapt->put('results', collect([
                'info' => collect([
                    'errorCode' => $travelPortException->getCode(),
                    'errorMessageEng' => $travelPortException->getMessage()
                ])
            ]));

            return $flightsSearchRequestAdapt;
        } finally {
            $request->save();
        }
    }

    /**
     * @param FlightsSearchResult $resultModel
     * @return Collection
     * @throws NemoWidgetServiceException
     */
    public function getFlightInfo(FlightsSearchResult $resultModel)
    {
        $log = $this->logger->getLog(LowFareSearchRsp::class, $resultModel->request->transaction_id, \App\Logging\TravelPortLogger::OBJECT_TYPE);

        if (null === $log) {
            throw NemoWidgetServiceException::getInstance('Log of result was not found');
        }

        /** @var  $lowFareSearchRsp  LowFareSearchRsp */
        $lowFareSearchRsp = unserialize($log);
        $allAirSegments = $lowFareSearchRsp->getAirSegmentList()->getAirSegment();
        $airSegments = collect();

        foreach ($resultModel->segments as $segmentNumber) {
            $segmentNumber = (int)filter_var($segmentNumber, FILTER_SANITIZE_NUMBER_INT) - 1;

            /** @var typeBaseAirSegment $airSegment */
            $airSegment = $allAirSegments[$segmentNumber] ?? null;

            if (null !== $airSegment) {
                $airSegments->put($airSegment->getKey(), $airSegment);
            }
        }

        $airPriceNum = (int)filter_var($resultModel->price, FILTER_SANITIZE_NUMBER_INT) - 1;
        /** @var AirPricePoint $airPricePoint */
        $airPricePoint = $lowFareSearchRsp->getAirPricePointList()->getAirPricePoint()[$airPriceNum];

        $bookings = collect();
        /** @var FlightOption $flightOprion */
        foreach ($airPricePoint->getAirPricingInfo()[0]->getFlightOptionsList()->getFlightOption() as $flightOprion) {
            /** @var Option $option */
            foreach ($flightOprion->getOption() as $option) {
                /** @var BookingInfo $bookingInfo */
                foreach ($option->getBookingInfo() as $bookingIndex => $bookingInfo) {
                    /** @var typeBaseAirSegment $airSegmentNode */
                    if ($airSegmentNode = $airSegments->get($bookingInfo->getSegmentRef())) {
                        $bookings->add($bookingInfo);

                        if (!is_null($option->getConnection())) {
                            /** @var Connection $connection */
                            foreach ($option->getConnection() as $connection) {
                                if (!$connection->getStopOver() &&
                                    $bookingIndex === $connection->getSegmentIndex()) {
                                    $airSegmentNode->setConnection($connection);
                                }
                            }
                        }
                    }
                }
            }
        }

        $airPriceRequestDto = new AirPriceRequestDto(
            $airSegments,
            $resultModel->request->data['passengers'],
            $bookings
        );

        /** @var  $airPriceRsp AirPriceRsp */
        $airPriceRsp = TP::AirPriceReq($airPriceRequestDto);

        $order = FlightsSearchFlightInfo::forceCreate([
            'transaction_id' => $airPriceRsp->getTransactionId(),
            'flight_search_result_id' => $resultModel->id
        ]);

        $aiePriceRsp = $this->ftObjectAdapter->AirPriceAdapt($airPriceRsp, $oldTotalPrice);
        $aiePriceRsp->put('createOrderLink', sprintf('/checkout?id=%d', $order->id));

        return $aiePriceRsp;
    }

    public function getFareRule(FlightsSearchResult $resultModel)
    {
        $log = $this->logger->getLog(LowFareSearchRsp::class, $resultModel->request->transaction_id, \App\Logging\TravelPortLogger::OBJECT_TYPE);

        if (null === $log) {
            throw NemoWidgetServiceException::getInstance('Log of result was not found');
        }

        /** @var  $lowFareSearchRsp  LowFareSearchRsp */
        $lowFareSearchRsp = unserialize($log);

        $airPriceNum = (int)filter_var($resultModel->price, FILTER_SANITIZE_NUMBER_INT) - 1;
        /** @var AirPricePoint $airPricePoint */
        $airPricePoint = $lowFareSearchRsp->getAirPricePointList()->getAirPricePoint()[$airPriceNum];

        $airPricingInfoRefs = collect();
        /** @var AirPricingInfo $airPricingInfo */
        foreach ($airPricePoint->getAirPricingInfo() as $airPricingInfo) {
            /** @var FareInfoRef $fareInfoRef */
            foreach ($airPricingInfo->getFareInfoRef() as $fareInfoRef) {
                $airPricingInfoRefs->put($fareInfoRef->getKey(), $fareInfoRef->getKey());
            }
        }

        $fareRulesKeys = collect();
        /** @var FareInfo $fareINfo */
        foreach ($lowFareSearchRsp->getFareInfoList()->getFareInfo() as $fareINfo) {
            if ($airPricingInfoRefs->has($fareINfo->getKey())) {
                $fareRulesKeys->add($fareINfo->getFareRuleKey());
            }
        }

//        $airFareRulesRsp = Cache::rememberForever('fareRules' . $resultModel->id, function () use ($fareRulesKeys) {
//            return TP::airFareRules($fareRulesKeys->toArray());
//        });

        $airFareRulesRsp = TP::airFareRules($fareRulesKeys->toArray());

        return $this->ftObjectAdapter->airFareRulesAdapt($airFareRulesRsp);
    }


}