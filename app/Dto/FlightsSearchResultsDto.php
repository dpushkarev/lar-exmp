<?php


namespace App\Dto;

use FilippoToso\Travelport\Air\AirSearchRsp;

/**
 * Class FlightsSearchResultsDto
 * @package App\Dto
 */
class FlightsSearchResultsDto
{
    private $request;
    private $results;

    /**
     * FlightsSearchResultsDto constructor.
     * @param FlightsSearchRequestDto $request
     * @param AirSearchRsp $results
     */
    public function __construct(FlightsSearchRequestDto $request, AirSearchRsp $results)
    {
        $this->request = $request;
        $this->results = $results;
    }

    /**
     * @return FlightsSearchRequestDto
     */
    public function getRequest(): FlightsSearchRequestDto
    {
        return $this->request;
    }

    /**
     * @return AirSearchRsp
     */
    public function getResults(): AirSearchRsp
    {
        return $this->results;
    }
}