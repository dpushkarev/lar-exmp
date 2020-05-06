<?php

namespace App\Http\Controllers;

use App\Exceptions\TravelPortException;
use App\Http\Requests\TravelPortSearchRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller as BaseController;
use App\Facades\TP;

/**
 * Class TravelPort
 * @package App\Http\Controllers
 */
class TravelPort extends BaseController
{
    /**
     * @param TravelPortSearchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(TravelPortSearchRequest $request)
    {
        try{
            $result = TP::LowFareSearchReq($request->getTravelPortSearchDto());
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

            return response()->json($response ?? '');
        } catch (TravelPortException $exception) {
            throw new HttpResponseException(response()->json($exception->getMessage(), 422));
        }
    }

}
