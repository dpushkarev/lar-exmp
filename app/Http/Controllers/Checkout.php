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
use App\Services\PlatformRule\ApplyRulesService;
use Carbon\Carbon;
use FilippoToso\Travelport\Air\AirPricePoint;
use FilippoToso\Travelport\Air\AirPriceRsp;
use FilippoToso\Travelport\Air\LowFareSearchRsp;
use FilippoToso\Travelport\UniversalRecord\AirCreateReservationRsp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class Checkout extends Controller
{

    /**
     * @param $flightInfoCode
     * @param FtObjectAdapter $adapter
     * @param MoneyService $moneyService
     * @param ApplyRulesService $applyRulesService
     * @return FinishedOrder|FlightsSearchResults
     * @throws ApiException
     */
    public function getData(
        $flightInfoCode,
        FtObjectAdapter $adapter,
        MoneyService $moneyService,
        ApplyRulesService $applyRulesService
    ) {
        /** @var FlightsSearchFlightInfo $flightInfo */
        $flightInfo = FlightsSearchFlightInfo::whereCode($flightInfoCode)->with('result.request')->first();

        if (is_null($flightInfo)) {
            throw ApiException::getInstanceInvalidCode($flightInfoCode);
        }

        if ($flightInfo->reservation) {
            return new FinishedOrder($flightInfo->reservation);
        }

        if ($flightInfo->created_at->diffInHours(Carbon::now()) > 3) {
            throw ApiException::getInstance('Order has expired');
        }

        try {
            $logLfs = resolve(\FilippoToso\Travelport\TravelportLogger::class)
                ->getLog(
                    LowFareSearchRsp::class,
                    $flightInfo->result->request->transaction_id,
                    TravelPortLogger::OBJECT_TYPE
                );

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

            $applyRulesService->coverCheckout($airPriceResult, $flightInfo->result->rule_id);

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
            throw ApiException::getInstanceInvalidCode($flightInfoCode);
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
     * @param ApplyRulesService $applyRulesService
     * @param Request $request
     * @param $reservationCode
     * @return AirReservation
     * @throws ApiException
     */
    public function getReservation(
        FtObjectAdapter $adapter,
        ApplyRulesService $applyRulesService,
        Request $request,
        $reservationCode
    ) {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'access_code' => 'required',
        ]);

        if ($validator->fails()) {
            throw ApiException::getInstanceValidate($validator->errors()->first(), 666);
        }

        /** @var Reservation $reservation */
        $reservation = Reservation::where('code', $reservationCode)->with('flightInfo.result')->first();

        if (is_null($reservation)) {
            throw ApiException::getInstanceInvalidCode($reservationCode);
        }

        if ($reservation->access_code !== $request->get('access_code')) {
            throw ApiException::getInstanceValidate('Wrong access code');
        }

        try {
            $log = resolve(\FilippoToso\Travelport\TravelportLogger::class)
                ->getLog(AirCreateReservationRsp::class, $reservation->transaction_id, TravelPortLogger::OBJECT_TYPE);

            $response = $adapter->AirReservationAdapt(unserialize($log));
            $applyRulesService->coverReservationResponse(
                $response,
                $reservation->getAmountPrice(),
                $reservation->flightInfo->result->rule_id
            );

            $response->put('paymentOption', $reservation->data['paymentOption']);

            return new AirReservation($response);
        } catch (TravelPortLoggerException $exception) {
            throw ApiException::getInstance($exception->getMessage());
        }
    }
}
