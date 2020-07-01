<?php


namespace App\Dto;

/**
 * Class AirPriceRequestDto
 * @package App\Dto
 */
class AirPriceRequestDto
{
    private $segments;
    private $passengers;
    private $bookings;

    /**
     * AirPriceRequestDto constructor.
     * @param $segments
     * @param $passengers
     * @param $bookings
     */
    public function __construct($segments, $passengers, $bookings)
    {
        $this->segments = $segments;
        $this->passengers = $passengers;
        $this->bookings = $bookings;
    }

    /**
     * @return array
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @return array
     */
    public function getPassengers(): array
    {
        return $this->passengers;
    }

    public function getBookings()
    {
        return $this->bookings;
    }

}