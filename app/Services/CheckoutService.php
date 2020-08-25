<?php


namespace App\Services;

use App\Adapters\FtObjectAdapter;
use App\Dto\AirReservationRequestDto;
use App\Exceptions\ApiException;
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

    /**
     * @param AirReservationRequestDto $dto
     * @return \Illuminate\Support\Collection
     * @throws ApiException
     */
    public function reservation(AirReservationRequestDto $dto)
    {
        $segmentKeys = collect();
        $log = $this->logger->getLog(AirPriceRsp::class, $dto->getOrder()->transaction_id, \App\Logging\TravelPortLogger::OBJECT_TYPE);
        /** @var AirPriceRsp $airPriceRsp */
        $airPriceRsp = unserialize($log);
        $passengerCount = 0;
        $passengerGenerator = $dto->getPassengersGenerator();

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

                        foreach ($airPricingInfo->getPassengerType() as $passengerType) {
                            $passengerCount++;
                            $passengerFromRequest = $passengerGenerator->current();
                            $passengerType->BookingTravelerRef = $passengerFromRequest['key'];
                            $passengerType->DOB = $passengerFromRequest['birth'] ?? null;
                            $passengerGenerator->next();
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

        if(is_null($dto->getAirSolution())) {
            throw ApiException::getInstance('AirSolutionKey is invalid');
        }

        if(count($dto->getPassengers()) !== $passengerCount) {
            throw ApiException::getInstance('Count of passenger is not correct');
        }

        /** @var typeBaseAirSegment $airSegment */
        foreach ($airPriceRsp->getAirItinerary()->getAirSegment() as $airSegment) {
            if ($segmentKeys->contains($airSegment->getKey())) {
                $dto->setSegment($airSegment);
            }
        }

        /** @todo remove cache */
        $response = Cache::rememberForever('reservation' . $dto->getOrder()->flight_search_result_id, function () use ($dto) {
            return TP::AirCreateReservationReq($dto);
        });

        return $this->adapter->AirReservationAdapt($response);
    }

}