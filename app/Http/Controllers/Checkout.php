<?php

namespace App\Http\Controllers;

use App\Adapters\FtObjectAdapter;
use App\Exceptions\ApiException;
use App\Exceptions\TravelPortLoggerException;
use App\Http\Requests\AirReservationRequest;
use App\Http\Resources\NemoWidget\AirReservation;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Logging\TravelPortLogger;
use App\Models\FlightsSearchFlightInfo;
use App\Services\CheckoutService;
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
        /** @var FlightsSearchFlightInfo $order */
        $order = FlightsSearchFlightInfo::find($orderId);

        if(is_null($order)) {
            throw ApiException::getInstanceInvalidId($orderId);
        }

        if ($order->isBooked()) {
            throw ApiException::getInstance('Finished order');
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

    /**
     * @param AirReservationRequest $request
     * @param CheckoutService $service
     * @param $orderId
     * @return AirReservation
     * @throws ApiException
     */
    public function reservation(AirReservationRequest $request, CheckoutService $service, $orderId)
    {
        /** @var FlightsSearchFlightInfo $order */
        $order = FlightsSearchFlightInfo::find($orderId);

        if(is_null($order)) {
            throw ApiException::getInstanceInvalidId($orderId);
        }

        if ($order->isBooked()) {
            throw ApiException::getInstance('Finished order');
        }

        $dto = $request->getAirReservationRequestDto();
        $dto->setOrder($order);

        try {
            $response = $service->reservation($dto);

            return new AirReservation($response);
        } catch (TravelPortLoggerException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }
}
