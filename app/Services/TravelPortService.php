<?php


namespace App\Services;

use App\Dto\AirPriceRequestDto;
use App\Dto\FlightsSearchRequestDto;
use App\Exceptions\TravelPortException;
use Carbon\Carbon;
use FilippoToso\Travelport\Air;
use FilippoToso\Travelport\Air\AirLegModifiers;
use FilippoToso\Travelport\Air\typeFareRuleType;
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
     * @param $request
     * @return mixed
     * @throws TravelPortException
     */
    protected function execute($request)
    {
        try {
            $result = $this->travelPort->execute($request);
        } catch (TravelPortException $travelPortException) {
            throw $travelPortException;
        }

        return $result;
    }

    /**
     * @param FlightsSearchRequestDto $dto
     * @return mixed
     * @throws TravelPortException
     */
    public function LowFareSearchReq(FlightsSearchRequestDto $dto)
    {
        $request = $this->getLowFareSearchRequest($dto);
        return $this->execute($request);
    }

    public function AirPriceReq(AirPriceRequestDto $dto)
    {
        $request = $this->getAirPriceRequest($dto);
        return $this->execute($request);
    }

    protected function getAirPriceRequest(AirPriceRequestDto $dto)
    {
        /** @var  $airPriceRequest Air\AirPriceReq */
        $airPriceRequest = app()->make(Air\AirPriceReq::class);

        $billingPointOfSaleInfo = $this->getBillingPointOfSaleInfo();
        $airItinerary = $this->getAirSegments($dto->getSegments());
        $airPricingModifiers = $this->getAirPricingModifiers();
        $searchPassengers = $this->getSearchPassengers($dto->getPassengers());
        $aiePricingCommand = $this->getAirPricingCommand($dto->getBookings());

        return $airPriceRequest
            ->setFareRuleType(typeFareRuleType::long)
            ->setAirItinerary($airItinerary)
            ->setBillingPointOfSaleInfo($billingPointOfSaleInfo)
            ->setAirPricingModifiers($airPricingModifiers)
            ->setSearchPassenger($searchPassengers)
            ->setAirPricingCommand($aiePricingCommand);
    }

    /**
     * @return Air\BillingPointOfSaleInfo
     */
    protected function getBillingPointOfSaleInfo()
    {
        return (new Air\BillingPointOfSaleInfo(static::APPLICATION));
    }

    protected function getAirPricingCommand($bookings)
    {
        $airSegmentPricingModifiers = [];
        foreach ($bookings as $booking) {
            $airSegmentPricingModifiers[] = (new Air\AirSegmentPricingModifiers())
                ->setAirSegmentRef(getXmlAttribute($booking, 'SegmentRef'))
                ->setPermittedBookingCodes(
                        new Air\PermittedBookingCodes(new Air\BookingCode(getXmlAttribute($booking, 'BookingCode')))
                );
        }

        return (new Air\AirPricingCommand())->setAirSegmentPricingModifiers($airSegmentPricingModifiers);
    }

    protected function getAirSegments($segments)
    {
        $airSegments = [];
        foreach ($segments as $segment) {
            $airSegments[] = (new Air\typeBaseAirSegment())
                ->setKey(getXmlAttribute($segment, 'Key'))
                ->setGroup(getXmlAttribute($segment, 'Group'))
                ->setCarrier(getXmlAttribute($segment, 'Carrier'))
                ->setFlightNumber(getXmlAttribute($segment, 'FlightNumber'))
                ->setOrigin(getXmlAttribute($segment, 'Origin'))
                ->setDestination(getXmlAttribute($segment, 'Destination'))
                ->setDepartureTime(getXmlAttribute($segment, 'DepartureTime'))
                ->setArrivalTime(getXmlAttribute($segment, 'ArrivalTime'))
                ->setFlightTime(getXmlAttribute($segment, 'FlightTime'))
                ->setDistance(getXmlAttribute($segment, 'Distance'))
                ->setETicketability(getXmlAttribute($segment, 'ETicketability'))
                ->setEquipment(getXmlAttribute($segment, 'Equipment'))
                ->setChangeOfPlane(getXmlAttribute($segment, 'ChangeOfPlane'))
                ->setParticipantLevel(getXmlAttribute($segment, 'ParticipantLevel'))
                ->setPolledAvailabilityOption(getXmlAttribute($segment, 'PolledAvailabilityOption'))
                ->setOptionalServicesIndicator(getXmlAttribute($segment, 'OptionalServicesIndicator'))
                ->setAvailabilitySource(getXmlAttribute($segment, 'AvailabilitySource'))
                ->setAvailabilityDisplayType(getXmlAttribute($segment, 'AvailabilityDisplayType'))
                ->setProviderCode(static::GALILEO_PROVIDER_ID);
        }

        return (new Air\AirItinerary())->setAirSegment($airSegments);
    }

    /**
     * @param FlightsSearchRequestDto $dto
     * @return mixed
     */
    protected function getLowFareSearchRequest(FlightsSearchRequestDto $dto)
    {
        $searchAirLegs = $this->getSearchAirLeg($dto->getSegments(), $dto->getParameters());
        $searchPassengers = $this->getSearchPassengers($dto->getPassengers());
        $searchModifiers = $this->getSearchModifiers($dto->getParameters());
        $billingPointOfSaleInfo = $this->getBillingPointOfSaleInfo();
        $airPricingModifiers = $this->getAirPricingModifiers();

        return (new Air\LowFareSearchReq())
            ->setReturnUpsellFare(true)
            ->setBillingPointOfSaleInfo($billingPointOfSaleInfo)
            ->setAirSearchModifiers($searchModifiers)
            ->setSearchAirLeg($searchAirLegs)
            ->setSearchPassenger($searchPassengers)
            ->setAirPricingModifiers($airPricingModifiers)
            ->setTraceId($this->traceId);

    }

    protected function getAirPricingModifiers()
    {
        return (new Air\AirPricingModifiers())
            ->setInventoryRequestType(Air\typeInventoryRequest::DirectAccess)
            ->setFaresIndicator(Air\typeFaresIndicator::AllFares)
            ->setReturnFareAttributes(true)
            ->setExemptTaxes((new Air\ExemptTaxes())->setAllTaxes(false));
    }

    protected function getLowFareSearchAsyncRequest(FlightsSearchRequestDto $dto)
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

        if (isset($parameters['serviceClass']) && $parameters['serviceClass'] !== 'All') {
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
                    ->setCode(static::PASSENGERS_MAP[$passenger['type']] ?? $passenger['type'])
                    ->setBookingTravelerRef(base64_encode(rand(10000000, 20000000)))
                    ->setKey(base64_encode(rand(10000000, 20000000)));
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

            $searchAirLeg->setAirLegModifiers((new AirLegModifiers)->setAllowDirectAccess(true));

            $searchAirLegs[] = $searchAirLeg;
        }
        return $searchAirLegs;
    }

}