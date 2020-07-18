<?php

namespace App\Http\Controllers;

use App\Adapters\FtObjectAdapter;
use App\Exceptions\ApiException;
use App\Exceptions\TravelPortLoggerException;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Logging\TravelPortLogger;
use App\Models\FlightsSearchFlightInfo;
use FilippoToso\Travelport\Air\AirPriceRsp;

class Checkout extends Controller
{

    /**
     * @param $orderId
     * @param FtObjectAdapter $adapter
     * @return FlightsSearchResults
     * @throws ApiException
     */
    public function getData($orderId, FtObjectAdapter $adapter)
    {
        $order = FlightsSearchFlightInfo::find($orderId);

        if(is_null($order)) {
            throw ApiException::getInstanceInvalidId($orderId);
        }

        try {
            $log = resolve(\FilippoToso\Travelport\TravelportLogger::class)
                ->getLog(AirPriceRsp::class, $order->transaction_id, TravelPortLogger::OBJECT_TYPE);

            $airPriceResult = $adapter->AirPriceAdaptCheckout(unserialize($log));
            $airPriceResult->put('request', $order->result->request);

            return new FlightsSearchResults($airPriceResult);
        } catch (TravelPortLoggerException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }
}
