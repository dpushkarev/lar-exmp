<?php


namespace App\Services;

use App\Adapters\FtObjectAdapter;
use App\Dto\AirReservationRequestDto;
use App\Facades\TP;
use FilippoToso\Travelport\Air\AirPriceResult;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\AirPricingInfo;
use FilippoToso\Travelport\Air\AirPricingSolution;
use FilippoToso\Travelport\Air\AirSegmentRef;
use FilippoToso\Travelport\Air\FareInfo;
use FilippoToso\Travelport\Air\typeBaseAirSegment;
use FilippoToso\Travelport\TravelportLogger;
use Illuminate\Support\Facades\Cache;

class CheckoutService
{
    protected $logger;
    protected $adapter;

    public function __construct(TravelportLogger $logger, FtObjectAdapter $adapter)
    {
        $this->logger = $logger;
        $this->adapter = $adapter;
    }

    public function reservation(AirReservationRequestDto $dto)
    {
        $segmentKeys = collect();
        $log = $this->logger->getLog(AirPriceRsp::class, $dto->getOrder()->transaction_id, \App\Logging\TravelPortLogger::OBJECT_TYPE);
        /** @var AirPriceRsp $airPriceRsp */
        $airPriceRsp = unserialize($log);

        /** @var $airPriceResult AirPriceResult */
        foreach ($airPriceRsp->getAirPriceResult() as $airPriceResult) {
            /** @var AirPricingSolution $airSolution */
            foreach ($airPriceResult->getAirPricingSolution() as $airSolution) {
                if ($airSolution->getKey() === $dto->getAirSolutionKey()) {
                    /** @var AirSegmentRef $airSegmentRef */
                    foreach ($airSolution->getAirSegmentRef() as $airSegmentRef) {
                        $segmentKeys->add($airSegmentRef->getKey());
                    }
                    /** @var AirPricingInfo $airPricingInfo */
                    foreach ($airSolution->getAirPricingInfo() as $airPricingInfo) {
                        $airPricingInfo->setFareStatus(null);
                        $airPricingInfo->setFareInfoRef(null);
                        $airPricingInfo->setFareCalc(null);
                        $airPricingInfo->setBookingTravelerRef(null);
                        $airPricingInfo->setWaiverCode(null);
                        $airPricingInfo->setPaymentRef(null);
                        $airPricingInfo->setChangePenalty(null);
                        $airPricingInfo->setCancelPenalty(null);
                        $airPricingInfo->setBaggageAllowances(null);

                        /** @var FareInfo $fareInfo */
                        foreach ($airPricingInfo->getFareInfo() as $fareInfo) {
                            $fareInfo->setFareSurcharge(null);
                            $fareInfo->setBrand(null);
                        }
                    }
                    $airSolution->setAirSegmentRef(null);
                    $airSolution->setJourney(null);
                    $airSolution->setLegRef(null);
                    $airSolution->setFareNote(null);

                    $dto->setAirSolution($airSolution);
                    break 2;
                }
            }
        }

        /** @var typeBaseAirSegment $airSegment */
        foreach ($airPriceRsp->getAirItinerary()->getAirSegment() as $airSegment) {
            if ($segmentKeys->contains($airSegment->getKey())) {
                $dto->setSegment($airSegment);
            }
        }

        $response = Cache::rememberForever('reservation' . $dto->getOrder()->flight_search_result_id, function () use ($dto) {
            return TP::AirCreateReservationReq($dto);
        });

        return $this->adapter->AirReservationAdapt($response);
    }

}