<?php

namespace App\Http\Controllers;

use App\Adapters\FtObjectAdapter;
use App\Exceptions\ApiException;
use App\Exceptions\NemoWidgetServiceException;
use App\Exceptions\TravelPortLoggerException;
use App\Http\Requests\AirReservationRequest;
use App\Http\Resources\NemoWidget\AirReservation;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Logging\TravelPortLogger;
use App\Models\FlightsSearchFlightInfo;
use App\Services\CheckoutService;
use App\Services\MoneyService;
use Carbon\Carbon;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use FilippoToso\Travelport\UniversalRecord\AirCreateReservationRsp;

class Checkout extends Controller
{

    /**
     * @param $flightInfoId
     * @param FtObjectAdapter $adapter
     * @param MoneyService $moneyService
     * @return FlightsSearchResults
     * @throws ApiException
     *
     */
    public function getData($flightInfoId, FtObjectAdapter $adapter, MoneyService $moneyService)
    {
        /** @var FlightsSearchFlightInfo $flightInfo */
        $flightInfo = FlightsSearchFlightInfo::whereId($flightInfoId)->with('result.request')->first();

        if (is_null($flightInfo)) {
            throw ApiException::getInstanceInvalidId($flightInfoId);
        }

        if ($flightInfo->reservation) {
            throw ApiException::getInstance('Finished order');
        }

        if ($flightInfo->created_at->diffInHours(Carbon::now()) > 3) {
            throw ApiException::getInstance('Offer has expired');
        }

        try {
            $logLfs = resolve(\FilippoToso\Travelport\TravelportLogger::class)
                ->getLog(LowFareSearchRsp::class, $flightInfo->result->request->transaction_id, TravelPortLogger::OBJECT_TYPE);

            /** @var  $lowFareSearchRsp  LowFareSearchRsp */
            $lowFareSearchRsp = unserialize($logLfs);
            $airPriceNum = (int)filter_var($flightInfo->result->price, FILTER_SANITIZE_NUMBER_INT) - 1;

            /** @var AirPricePoint $airPricePoint */
            $airPricePoint = $lowFareSearchRsp->getAirPricePointList()->getAirPricePoint()[$airPriceNum];
            $oldTotalPrice = $moneyService->getMoneyByString($airPricePoint->getTotalPrice());

            $logAp = resolve(\FilippoToso\Travelport\TravelportLogger::class)
                ->getLog(AirPriceRsp::class, $flightInfo->transaction_id, TravelPortLogger::OBJECT_TYPE);

            $airPriceResult = $adapter->AirPriceAdaptCheckout(unserialize($logAp), $oldTotalPrice);
            $airPriceResult->put('request', $flightInfo->result->request);

            return new FlightsSearchResults($airPriceResult);
        } catch (TravelPortLoggerException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }

    /**
     * @param AirReservationRequest $request
     * @param CheckoutService $service
     * @param $flightInfoId
     * @return AirReservation
     * @throws ApiException
     */
    public function reservation(AirReservationRequest $request, CheckoutService $service, $flightInfoId)
    {
        /** @var FlightsSearchFlightInfo $flightInfo */
        $flightInfo = FlightsSearchFlightInfo::find($flightInfoId);

        if (is_null($flightInfo)) {
            throw ApiException::getInstanceInvalidId($flightInfoId);
        }

        if ($flightInfo->reservation) {
            throw ApiException::getInstance('Finished order');
        }

        $dto = $request->getAirReservationRequestDto();
        $dto->setOrder($flightInfo);

        try {
            $response = $service->reservation($dto);

            return new AirReservation($response);
        } catch (TravelPortLoggerException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }

    /**
     * @param FtObjectAdapter $adapter
     * @param $orderId
     * @return AirReservation
     * @throws ApiException
     */
    public function order(FtObjectAdapter $adapter, $orderId)
    {
        /** @var FlightsSearchFlightInfo $order */
        $flightInfo = FlightsSearchFlightInfo::whereId($orderId)->has('reservation')->with('reservation')->first();

        if (is_null($flightInfo)) {
            throw ApiException::getInstanceInvalidId($orderId);
        }

        try {
            $log = resolve(\FilippoToso\Travelport\TravelportLogger::class)
                ->getLog(AirCreateReservationRsp::class, $flightInfo->reservation->transaction_id, TravelPortLogger::OBJECT_TYPE);

            $response = $adapter->AirReservationAdapt(unserialize($log));
            $response->put('paymentOption', $flightInfo->reservation->data['paymentOption']);
            $response->put('reservationId', $flightInfo->reservation->id);

            return new AirReservation($response);
        } catch (TravelPortLoggerException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }
}
