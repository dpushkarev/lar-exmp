<?php

namespace App\Http\Controllers;

use App\Exceptions\TravelPortException;
use App\Http\Requests\TravelPortSearchRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Controller as BaseController;
use App\Facades\TP;
use App\Http\Resources\TravelPort as TravelPortResource;

/**
 * Class TravelPort
 * @package App\Http\Controllers
 */
class TravelPort extends BaseController
{
    /**
     * @param TravelPortSearchRequest $request
     * @return TravelPortResource
     */
    public function search(TravelPortSearchRequest $request)
    {
        try{
            $result = TP::LowFareSearchReq($request->getTravelPortSearchDto());
            return new TravelPortResource($result);
        } catch (TravelPortException $exception) {
            throw new HttpResponseException(response()->json($exception->getMessage(), 422));
        }
    }

}
