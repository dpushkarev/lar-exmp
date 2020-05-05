<?php


namespace App\Dto;

/**
 * Class TravelPortSearchDto
 * @package App\Dto
 */
class TravelPortSearchDto
{
    private $segments;
    private $passengers;
    private $parameters;

    /**
     * TravelPortSearchDto constructor.
     * @param $segments
     * @param $passengers
     * @param null $parameters
     */
    public function __construct($segments, $passengers, $parameters = null)
    {
        $this->segments = $segments;
        $this->passengers = $passengers;
        $this->parameters = $parameters;
    }

    /**
     * @return array
     */
    public function getSegments(): array
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

    /**
     * @return array|null
     */
    public function getParameters(): ?array
    {
        return $this->parameters;
    }
}