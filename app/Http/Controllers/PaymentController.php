<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Resources\Payment\Confirm;
use App\Http\Resources\Payment\Confirment;
use App\Models\FlightsSearchFlightInfo;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Cubes\Nestpay\MerchantService;
use NP;

/**
 * Class PaymentController
 * @package App\Http\Controllers
 */
class PaymentController extends Controller
{
    /**
     * @param $flightInfoId
     * @return Confirm
     * @throws ApiException
     */
    public function confirment($flightInfoId)
    {
        /** @var FlightsSearchFlightInfo $flightInfo */
        $flightInfo = FlightsSearchFlightInfo::whereId($flightInfoId)->has('reservation')->with('reservation')->first();

        if (is_null($flightInfo)) {
            throw ApiException::getInstanceInvalidId($flightInfoId);
        }

        return new Confirm($this->getPaymentData($flightInfo->reservation));

//        return view('nestpay::confirm', [
//            'paymentData' => $this->getPaymentData()
//        ]);

    }

    /**
     * @param $reservation_id
     * @return Confirm
     * @throws ApiException
     */
    public function confirm($reservation_id)
    {
        $reservation = Reservation::find($reservation_id);

        if (!$reservation) {
            throw ApiException::getInstance('Reservation not found');
        }

        NP::getMerchantConfig()->setConfig([
            'okUrl' => route('payment.success'),
            'failUrl' => route('payment.fail'),
        ]);

        $formFields = NP::paymentMakeRequestParameters($this->getPaymentData($reservation));

        /**
         * The working payment is created at this point
         * Set specific columns for nestpay model, like user_id , order_id etc
         */
//        $nestpayPayment = $nestpayMerchantService->getWorkingPayment();
//        $nestpayPayment->fill([
//            'user_id' => auth()->user()->getAuthIdentifier(),
//        ]);
//        $nestpayPayment->save();

        return new Confirm($formFields);
    }

    /**
     * This is successfull processing
     *
     *
     * @param MerchantService $nestpayMerchantService
     * @param Request $request
     * @return view
     */
    public function success(MerchantService $nestpayMerchantService, Request $request)
    {
        $payment = null;
        $ex = null;

        try {
            $payment = $nestpayMerchantService->paymentProcess3DGateResponse($request->all());

            //the payment has been process successfully 
            //THAT DOES NOT MEAN THAT CUSTOMER HAS PAID!!!!
            //FOR SUCCESSFULL PAYMENT SEE \App\Listeners\NestpayEventSubscriber!!!
            //DO NOT ADD CODE HERE FOR SUCCESSFULL PAYMENT!!!!

        } catch (\Cubes\Nestpay\PaymentAlreadyProcessedException $ex) {
            //the payment has been already processed
            //this error occures if customer refresh result page
            //add code here for the case if necessary 
            $ex = null;//comment this if you want to show this exception if debug is on

        } catch (\Exception $ex) {
            //any other error
            //add code here for the case if necessary 
        } finally {
            //try to get working payment

            try {
                $payment = $nestpayMerchantService->getWorkingPayment();
            } catch (\Exception $exTemp) {
            }
        }

        if ($ex && config('app.debug')) {
            //if debug is enabled throw exception
            throw $ex;
        }

        return view('nestpay::result', [
            'payment' => $payment,
            'exception' => $ex,
        ]);
    }

    /**
     * The fiail url
     * Process payment even in this case!!!
     *
     * @param MerchantService $nestpayMerchantService
     * @param Request $request
     * @return void
     */
    public function fail(MerchantService $nestpayMerchantService, Request $request)
    {
        $payment = null;
        $ex = null;

        try {
            $payment = $nestpayMerchantService->paymentProcess3DGateResponse($request->all());

        } catch (\Cubes\Nestpay\PaymentAlreadyProcessedException $ex) {
            //the payment has been already processed
            //this error occures if customer refresh result page
            //add code here for the case if necessary 
            $ex = null;//comment this if you want to show this exception if debug is on

        } catch (\Exception $ex) {
            //any other error
            //add code here for the case if necessary 

        } finally {
            //try to get working payment

            try {
                $payment = $nestpayMerchantService->getWorkingPayment();
            } catch (\Exception $exTemp) {
            }
        }

        if ($ex && config('app.debug')) {
            //if debug is enabled throw exception
            throw $ex;
        }

        return view('nestpay::result', [
            'payment' => $payment,
            'exception' => $ex,
            'isFail' => true
        ]);
    }

    /**
     * @param Reservation $reservation
     * @return array
     */
    protected function getPaymentData(Reservation $reservation): array
    {
        return [
            \Cubes\Nestpay\Payment::PROP_AMOUNT => $reservation->total_price,
            \Cubes\Nestpay\Payment::PROP_CURRENCY => \Cubes\Nestpay\Payment::CURRENCY_RSD,
            \Cubes\Nestpay\Payment::PROP_TRANTYPE => \Cubes\Nestpay\Payment::TRAN_TYPE_PREAUTH,
            \Cubes\Nestpay\Payment::PROP_LANG => app()->getLocale(),
            \Cubes\Nestpay\Payment::PROP_INVOICENUMBER => $reservation->id,
            \Cubes\Nestpay\Payment::PROP_DESCRIPTION => $reservation->id,
            \Cubes\Nestpay\Payment::PROP_COMMENTS => false,
            \Cubes\Nestpay\Payment::PROP_EMAIL => $reservation->data['email'],
            \Cubes\Nestpay\Payment::PROP_TEL => implode('', $reservation->data['phoneNumber']),
            \Cubes\Nestpay\Payment::PROP_BILLTOCOMPANY => '?',
            \Cubes\Nestpay\Payment::PROP_BILLTONAME => '?',
            \Cubes\Nestpay\Payment::PROP_BILLTOSTREET1 => $reservation->data['address']['street'],
            \Cubes\Nestpay\Payment::PROP_BILLTOCITY => $reservation->data['address']['city'],
            \Cubes\Nestpay\Payment::PROP_BILLTOPOSTALCODE => $reservation->data['address']['postalCode'],
            \Cubes\Nestpay\Payment::PROP_BILLTOCOUNTRY => $reservation->data['address']['country'],
            \Cubes\Nestpay\Payment::PROP_SHIPTOCOMPANY => '?',
            \Cubes\Nestpay\Payment::PROP_SHIPTONAME => '?',
            \Cubes\Nestpay\Payment::PROP_SHIPTOSTREET1 => $reservation->data['address']['street'],
            \Cubes\Nestpay\Payment::PROP_SHIPTOCITY => $reservation->data['address']['city'],
            \Cubes\Nestpay\Payment::PROP_SHIPTOPOSTALCODE => $reservation->data['address']['postalCode'],
            \Cubes\Nestpay\Payment::PROP_SHIPTOCOUNTRY => $reservation->data['address']['country'],
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA1 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA2 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA3 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA4 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA5 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA6 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA7 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA8 => '?',
            \Cubes\Nestpay\Payment::PROP_DIMCRITERIA9 => '?',
        ];
    }
}
