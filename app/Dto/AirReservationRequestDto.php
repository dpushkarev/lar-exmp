<?php


namespace App\Dto;

use App\Models\FlightsSearchFlightInfo;
use FilippoToso\Travelport\Air\AirPricingSolution;
use FilippoToso\Travelport\Air\HostToken;
use FilippoToso\Travelport\Air\typeBaseAirSegment;

/**
 * Class FlightsSearchRequestDto
 * @package App\Dto
 */
class AirReservationRequestDto
{
    private $passengers;
    private $address;
    private $airSolutionKey;
    private $order;
    private $airSolution;
    private $segments = [];
    private $hostTokens = [];
    private $phoneNumber;
    private $email;

    /**
     * AirReservationRequestDto constructor.
     * @param $passengers
     * @param $address
     * @param $airSolutionKey
     * @param $phoneNumber
     * @param $email
     */
    public function __construct($passengers, $address, $airSolutionKey, $phoneNumber, $email)
    {
        $this->passengers = $passengers;
        $this->address = $address;
        $this->airSolutionKey = $airSolutionKey;
        $this->phoneNumber = $phoneNumber;
        $this->email = $email;
    }

    /**
     * @return array
     */
    public function getAddress(): array
    {
        return $this->address;
    }

    /**
     * @return array
     */
    public function getPassengers(): array
    {
        return $this->passengers;
    }

    public function getPassengersGenerator()
    {
        foreach ($this->getPassengers() as $passenger) {
            yield $passenger;
        }
    }

    /**
     * @return array|null
     */
    public function getAirSolutionKey(): string
    {
        return $this->airSolutionKey;
    }

    /**
     * @param FlightsSearchFlightInfo $order
     */
    public function setOrder(FlightsSearchFlightInfo $order)
    {
        $this->order = $order;
    }

    /**
     * @return FlightsSearchFlightInfo
     */
    public function getOrder(): FlightsSearchFlightInfo
    {
        return $this->order;
    }

    /**
     * @param AirPricingSolution $airPricingSolution
     */
    public function setAirSolution(AirPricingSolution $airPricingSolution)
    {
        $this->airSolution = $airPricingSolution;
    }

    /**
     * @return AirPricingSolution|null
     */
    public function getAirSolution(): ?AirPricingSolution
    {
        return $this->airSolution;
    }

    /**
     * @param typeBaseAirSegment $segment
     */
    public function setSegment(typeBaseAirSegment $segment)
    {
        $this->segments[] = $segment;
    }

    /**
     * @return array
     */
    public function getSegments(): array
    {
        return $this->segments;
    }

    public function setHostToken(HostToken $hostToken)
    {
        $this->hostTokens[] = $hostToken;
    }

    public function getHostTokens(): array
    {
        return $this->hostTokens;
    }

    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    public function getEmail()
    {
        return $this->email;
    }


}