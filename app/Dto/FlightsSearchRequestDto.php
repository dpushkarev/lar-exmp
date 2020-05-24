<?php


namespace App\Dto;

/**
 * Class FlightsSearchRequestDto
 * @package App\Dto
 */
class FlightsSearchRequestDto implements \JsonSerializable
{
    private $segments;
    private $passengers;
    private $parameters;
    private $requestId;

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

    public function getRequestId(): ?int
    {
        return $this->requestId;
    }

    public function setRequestId(int $request_id): void
    {
        $this->requestId = $request_id;
    }

    public function jsonSerialize()
    {
        return [
            'segments' => $this->segments,
            'passengers' => $this->passengers,
            'parameters' => $this->parameters,
        ];
    }
}