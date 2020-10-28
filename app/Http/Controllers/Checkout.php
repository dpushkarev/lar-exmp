<?php

namespace App\Http\Controllers;

use App\Adapters\FtObjectAdapter;
use App\Exceptions\ApiException;
use App\Exceptions\TravelPortLoggerException;
use App\Http\Requests\AirReservationRequest;
use App\Http\Resources\NemoWidget\AirReservation;
use App\Http\Resources\NemoWidget\FinishedOrder;
use App\Http\Resources\NemoWidget\FlightsSearchResults;
use App\Logging\TravelPortLogger;
use App\Models\FlightsSearchFlightInfo;
use App\Models\Reservation;
use App\Services\CheckoutService;
use App\Services\MoneyService;
use Carbon\Carbon;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use FilippoToso\Travelport\UniversalRecord\AirCreateReservationRsp;
use Illuminate\Http\Request;

class Checkout extends Controller
{

    /**
     * @param $flightInfoCode
     * @param FtObjectAdapter $adapter
     * @param MoneyService $moneyService
     * @return FinishedOrder|FlightsSearchResults
     * @throws ApiException
     */
    public function getData($flightInfoCode, FtObjectAdapter $adapter, MoneyService $moneyService)
    {
        /** @var FlightsSearchFlightInfo $flightInfo */
        $flightInfo = FlightsSearchFlightInfo::whereCode($flightInfoCode)->with('result.request')->first();

        if (is_null($flightInfo)) {
            throw ApiException::getInstanceInvalidId($flightInfoCode);
        }

        if ($flightInfo->reservation) {
            return new FinishedOrder($flightInfo->reservation);
        }

        if ($flightInfo->created_at->diffInHours(Carbon::now()) > 3) {
            throw ApiException::getInstance('Order has expired');
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
     * @param $flightInfoCode
     * @return AirReservation|FinishedOrder
     * @throws ApiException
     */
    public function reservation(AirReservationRequest $request, CheckoutService $service, $flightInfoCode)
    {
        /** @var FlightsSearchFlightInfo $flightInfo */
        $flightInfo = FlightsSearchFlightInfo::whereCode($flightInfoCode)->with('reservation')->first();

        if (is_null($flightInfo)) {
            throw ApiException::getInstanceInvalidId($flightInfoCode);
        }

        if ($flightInfo->reservation) {
            return new FinishedOrder($flightInfo->reservation);
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
     * @param Request $request
     * @param $reservationCode
     * @return AirReservation
     * @throws ApiException
     */
    public function getReservation(FtObjectAdapter $adapter, Request $request, $reservationCode)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'access_code' => 'required',
        ]);

        if ($validator->fails()) {
            throw ApiException::getInstanceValidate($validator->errors()->first(), 666);
        }

        /** @var FlightsSearchFlightInfo $order */
        $reservation = Reservation::where('code', $reservationCode)->first();

        if (is_null($reservation)) {
            throw ApiException::getInstanceInvalidId($reservationCode);
        }

        if ($reservation->access_code !== $request->get('access_code')) {
            throw ApiException::getInstanceValidate('Wrong access code');
        }

        try {
            $log = resolve(\FilippoToso\Travelport\TravelportLogger::class)
                ->getLog(AirCreateReservationRsp::class, $reservation->transaction_id, TravelPortLogger::OBJECT_TYPE);

            $response = $adapter->AirReservationAdapt(unserialize($log));
            $response->put('paymentOption', $reservation->data['paymentOption']);

            return new AirReservation($response);
        } catch (TravelPortLoggerException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }
}
