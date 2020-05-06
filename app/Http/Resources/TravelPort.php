<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TravelPort extends JsonResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $result = $this->resource;
        $response = [];
        foreach ($result->getAirSegmentList()->getAirSegment() as $airSegment) {
            $data = [
                'FlightNumber' => $airSegment->getFlightNumber(),
            ];

            foreach ($airSegment->getFlightDetailsRef() as $flightDetailRef) {
                foreach ($result->getFlightDetailsList()->getFlightDetails() as $flightDetail) {
                    if($flightDetailRef->getKey() === $flightDetail->getKey()) {
                        $data['FlightDetails'][] = [
                            'Equipment' => $flightDetail->getEquipment(),
                            'OriginTerminal' => $flightDetail->getOriginTerminal()
                        ];
                    }
                }
            }
            $response[] = $data;
        }

        return $response;
    }
}