<?php


namespace App\Services;

use App\Dto\AirPriceRequestDto;
use App\Dto\AirReservationRequestDto;
use App\Dto\FlightsSearchRequestDto;
use App\Exceptions\TravelPortException;
use Carbon\Carbon;
use FilippoToso\Travelport\Air;
use FilippoToso\Travelport\Air\AirLegModifiers;
use FilippoToso\Travelport\Air\typeFareRuleType;
use FilippoToso\Travelport\UniversalRecord\AirCreateReservationReq;
use FilippoToso\Travelport\UniversalRecord\FormOfPayment;
use FilippoToso\Travelport\UniversalRecord\typeRetainReservation;
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
    const CHILDREN_AGE = 11;
    const FORM_OF_PAYMENT_ROUTING_NUMBER = 456;
    const FORM_OF_PAYMENT_ACCOUNT_NUMBER = 789;
    const FORM_OF_PAYMENT_CHECK_NUMBER = 123456;
    const FORM_OF_PAYMENT_CHECK_KEY = 1;
    const FORM_OF_PAYMENT_TYPE = 'Check';
    const ACTION_STATUS_TYPE = 'ACTIVE';
    const ACTION_TICKET_DATA = 'T*';

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

    public function AirCreateReservationReq(AirReservationRequestDto $dto)
    {
        $request = $this->getAirCreateReservationRequest($dto);
        return $this->execute($request);
    }

    public function airFareRules($fareRulesKeys)
    {
        $request = $this->getAirFareRulesRequest($fareRulesKeys);
        return $this->execute($request);
    }

    protected  function  getAirFareRulesRequest($fareRulesKeys)
    {
        /** @var Air\AirFareRulesReq $airFareRulesRequest */
        $airFareRulesRequest = app()->make(Air\AirFareRulesReq::class);

        $billingPointOfSaleInfo = $this->getBillingPointOfSaleInfo();

        return $airFareRulesRequest->setFareRuleKey($fareRulesKeys)
            ->setBillingPointOfSaleInfo($billingPointOfSaleInfo);
    }

    protected function getAirCreateReservationRequest(AirReservationRequestDto $dto)
    {
        /** @var AirCreateReservationReq $airCreateReservationReq */
        $airCreateReservationReq = app()->make(AirCreateReservationReq::class);

        $airPricingSolution = $this->getAirPricingSolution($dto);
        $bookingTraveler = $this->getBookingTraveler($dto);
        $billingPointOfSaleInfo = $this->getBillingPointOfSaleInfo();
        $formOfPayment = $this->getFormOfPayment();
        $actionStatus = $this->getActionStatus();

        return $airCreateReservationReq
            ->setRetainReservation(typeRetainReservation::Both)
            ->setActionStatus($actionStatus)
            ->setFormOfPayment($formOfPayment)
            ->setProviderCode(static::GALILEO_PROVIDER_ID)
            ->setBillingPointOfSaleInfo($billingPointOfSaleInfo)
            ->setAirPricingSolution($airPricingSolution)
            ->setBookingTraveler($bookingTraveler);
    }

    protected function getActionStatus()
    {
        return (new Air\ActionStatus())
            ->setType(static::ACTION_STATUS_TYPE)
            ->setTicketDate(static::ACTION_TICKET_DATA)
            ->setProviderCode(static::GALILEO_PROVIDER_ID);
    }

    protected function getFormOfPayment()
    {
        return (new FormOfPayment())
            ->setType(static::FORM_OF_PAYMENT_TYPE)
            ->setKey(static::FORM_OF_PAYMENT_CHECK_KEY)
            ->setCheck((new Air\Check())
                ->setRoutingNumber(static::FORM_OF_PAYMENT_ROUTING_NUMBER)
                ->setAccountNumber(static::FORM_OF_PAYMENT_ACCOUNT_NUMBER)
                ->setCheckNumber(static::FORM_OF_PAYMENT_CHECK_NUMBER)
            );
    }

    public function getBookingTraveler(AirReservationRequestDto $dto)
    {
        $bookingTraveler = [];
        $phoneNumber = $dto->getPhoneNumber();
        $address = $dto->getAddress();

        foreach ($dto->getPassengers() as $passenger) {
            $bookingTraveler[] = (new Air\BookingTraveler())
                ->setBookingTravelerName((new Air\BookingTravelerName(
                    $passenger['prefix'],
                    $passenger['first'],
                    $passenger['middle'] ?? null,
                    $passenger['last'],
                    $passenger['suffix'] ?? null
                )))
                ->setPhoneNumber((new Air\PhoneNumber())
                    ->setCountryCode($phoneNumber['country'])
                    ->setAreaCode($phoneNumber['area'])
                    ->setNumber($phoneNumber['number'])
                )
                ->setEmail((new Air\Email())->setEmailID($dto->getEmail()))
                ->setDeliveryInfo((new Air\DeliveryInfo())
                    ->setShippingAddress((new Air\ShippingAddress())
                        ->setStreet([$address['street']])
                        ->setCity($address['city'])
                        ->setPostalCode($address['postalCode'])
                        ->setCountry($address['country'])
                    )
                )
                ->setDOB($passenger['dob'] ?? null)
                ->setKey($passenger['key'])
                ->setTravelerType(static::PASSENGERS_MAP[$passenger['travelerType']] ?? $passenger['travelerType'])
                ->setAddress((new Air\typeStructuredAddress())
                    ->setAddressName($address['street'])
                    ->setStreet([$address['street']])
                    ->setCity($address['city'])
                    ->setPostalCode($address['postalCode'])
                    ->setCountry($address['country'])
                );
        }

        return $bookingTraveler;
    }

    public function getAirPricingSolution(AirReservationRequestDto $dto): Air\AirPricingSolution
    {
        return $dto->getAirSolution()
            ->setAirSegment($dto->getSegments());
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
        /** @var Air\BookingInfo $booking */
        foreach ($bookings as $booking) {
            $airSegmentPricingModifiers[] = (new Air\AirSegmentPricingModifiers())
                ->setAirSegmentRef($booking->getSegmentRef())
                ->setPermittedBookingCodes(
                    new Air\PermittedBookingCodes(new Air\BookingCode($booking->getBookingCode()))
                );
        }

        return (new Air\AirPricingCommand())->setAirSegmentPricingModifiers($airSegmentPricingModifiers);
    }

    protected function getAirSegments($segments)
    {
        $airSegments = [];
        /** @var Air\typeBaseAirSegment $segment */
        foreach ($segments as $segment) {
            $airSegments[] = $segment
                ->setAirAvailInfo(null)
                ->setFlightDetailsRef(null)
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
            ->setFaresIndicator(Air\typeFaresIndicator::AllFares)
            ->setReturnFareAttributes(false)
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

        if (isset($parameters['serviceClass']) && $parameters['serviceClass'] !== 'Economy') {
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
                    ->setAge($passenger['type'] === 'CLD' ? static::CHILDREN_AGE : null)
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