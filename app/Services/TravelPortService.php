<?php


namespace App\Services;

use App\Dto\TravelPortSearchDto;
use App\Exceptions\TravelPortException;
use Carbon\Carbon;
use FilippoToso\Travelport\Air;
use Libs\FilippoToso\Travelport;

/**
 * Class TravelPortService
 * @package App\Services
 */
class TravelPortService
{
    const GALILEO_PROVIDER_ID = '1G';
    const APPLICATION = 'UAPI';
    const PASSENGERS_MAP = [
        'CLD' => 'CNN'
    ];

    private $travelPort;
    private $traceId;

    /**
     * TravelPortService constructor.
     * @param Travelport $travelPort
     */
    public function __construct(Travelport $travelPort)
    {
        $this->travelPort = $travelPort;
        $this->traceId = sha1('my-unique-token');
    }

    /**
     * @param TravelPortSearchDto $dto
     * @return mixed
     * @throws TravelPortException
     */
    public function LowFareSearchReq(TravelPortSearchDto $dto)
    {
        try {
            $request = $this->getLowFareSearchRequest($dto);
            return $this->travelPort->execute($request);
        } catch (\SoapFault $e) {
            throw TravelPortException::getInstance($e->getMessage());
        }
    }

    public function LowFareSearchAsyncReq(TravelPortSearchDto $dto)
    {
        try {
            $request = $this->getLowFareSearchAsyncRequest($dto);
            return  $this->travelPort->execute($request);
        } catch (\SoapFault $e) {
            throw TravelPortException::getInstance($e->getMessage());
        }
    }

    /**
     * @return Air\BillingPointOfSaleInfo
     */
    protected function getBillingPointOfSaleInfo()
    {
        return (new Air\BillingPointOfSaleInfo(static::APPLICATION));
    }

    /**
     * @param TravelPortSearchDto $dto
     * @return mixed
     */
    protected function getLowFareSearchRequest(TravelPortSearchDto $dto)
    {
        $searchAirLegs = $this->getSearchAirLeg($dto->getSegments(), $dto->getParameters());
        $searchPassengers = $this->getSearchPassengers($dto->getPassengers());
        $searchModifiers = $this->getSearchModifiers($dto->getParameters());
        $billingPointOfSaleInfo = $this->getBillingPointOfSaleInfo();

        return (new Air\LowFareSearchReq())
            ->setBillingPointOfSaleInfo($billingPointOfSaleInfo)
            ->setAirSearchModifiers($searchModifiers)
            ->setSearchAirLeg($searchAirLegs)
            ->setSearchPassenger($searchPassengers)
            ->setTraceId($this->traceId);

    }

    protected function getLowFareSearchAsyncRequest(TravelPortSearchDto $dto)
    {
        $searchAirLegs = $this->getSearchAirLeg($dto->getSegments(), $dto->getParameters());
        $billingPointOfSaleInfo = $this->getBillingPointOfSaleInfo();
        $searchModifiers = $this->getSearchModifiers($dto->getParameters());
        $searchPassengers = $this->getSearchPassengers($dto->getPassengers());

        return (new Air\LowFareSearchAsynchReq())
            ->setSearchAirLeg($searchAirLegs)
            ->setBillingPointOfSaleInfo($billingPointOfSaleInfo)
            ->setAirSearchModifiers($searchModifiers)
            ->setSearchPassenger($searchPassengers)
            ->setTraceId($this->traceId);

    }

    /**
     * @param $providerId
     * @return Air\Provider
     */
    protected function getProvider($providerId)
    {
        return (new Air\Provider())->setCode($providerId);
    }

    /**
     * @return Air\PreferredProviders
     */
    protected function getPreferredProviders()
    {
        return (new Air\PreferredProviders())
            ->setProvider($this->getProvider(static::GALILEO_PROVIDER_ID));
    }

    protected function getSearchModifiers($parameters)
    {
        $searchModifiers = (new Air\AirSearchModifiers())
            ->setPreferredProviders($this->getPreferredProviders());

        if (isset($parameters['direct'])) {
            $searchModifiers->setFlightType(
                (new Air\FlightType())->setNonStopDirects((bool)$parameters['direct'])
            );
        }

        if (isset($parameters['serviceClass'])) {
            $searchModifiers->setPreferredCabins(
                (new Air\PreferredCabins())->setCabinClass(new Air\CabinClass($parameters['serviceClass']))
            );
        }

        if (isset($parameters['airlines'])) {
            foreach ($parameters['airlines'] as $airline) {
                $carriers[] = new Air\Carrier($airline);
            }

            if (!empty($carriers)) {
                $searchModifiers->setPreferredCarriers(
                    new Air\PreferredCarriers($carriers)
                );
            }

        }

        return $searchModifiers;
    }

    /**
     * @param $passengers
     * @return array
     */
    protected function getSearchPassengers($passengers)
    {
        $searchPassengers = [];
        foreach ($passengers as $passenger) {
            for ($i = 0; $i < $passenger['count']; $i++) {
                $searchPassengers[] = (new Air\SearchPassenger)
                    ->setCode(static::PASSENGERS_MAP[$passenger['type']] ?? $passenger['type']);
            }
        }

        return $searchPassengers;
    }

    /**
     * @param $segments
     * @return array
     */
    protected function getSearchAirLeg($segments, $parameters)
    {
        $searchAirLegs = [];
        foreach ($segments as $segment) {
            $searchAirLeg = new Air\SearchAirLeg;

            if (isset($segment['departure'])) {
                if ($segment['departure']['isCity']) {
                    $searchAirLeg->setSearchOrigin([
                        (new Air\typeSearchLocation)->setCityOrAirport((new Air\CityOrAirport())->setCode($segment['departure']['IATA'])),
                    ]);
                } else {
                    $searchAirLeg->setSearchOrigin([
                        (new Air\typeSearchLocation)->setAirport((new Air\Airport())->setCode($segment['departure']['IATA'])),
                    ]);
                }


            }

            if (isset($segment['arrival'])) {
                if ($segment['arrival']['isCity']) {
                    $searchAirLeg->setSearchDestination([
                        (new Air\typeSearchLocation)->setCityOrAirport((new Air\CityOrAirport())->setCode($segment['arrival']['IATA'])),
                    ]);
                } else {
                    $searchAirLeg->setSearchDestination([
                        (new Air\typeSearchLocation)->setAirport((new Air\Airport())->setCode($segment['arrival']['IATA'])),
                    ]);
                }

            }

            if (isset($segment['departureDate'])) {
                $time = Carbon::createFromFormat('Y-m-d\Th:i:s', $segment['departureDate']);
                $searchAirLeg->setSearchDepTime([
                    (new Air\typeFlexibleTimeSpec)->setPreferredTime($time->toDateString()),
                ]);
            }

            if (isset($segment['arrivalDate'])) {
                $time = Carbon::createFromFormat('Y-m-d\Th:i:s', $segment['departureDate']);
                $searchAirLeg->setSearchArvTime([
                    (new Air\typeFlexibleTimeSpec)->setPreferredTime($time),
                ]);
            }

            $searchAirLegs[] = $searchAirLeg;
        }
        return $searchAirLegs;
    }

}