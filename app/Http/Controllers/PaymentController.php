<?php

namespace App\Http\Controllers;

use App\Http\Resources\Payment\Confirm;
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
    private $paymentService;

    public function __construct(MerchantService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function confirment($reservation_id)
    {
        $reservation = Reservation::find($reservation_id);

        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found']);
        }

        if ($reservation->is_paid) {
            return response()->json(['error' => 'Reservation has been already paid']);
        }

        return view('nestpay::confirm', [
            'paymentData' => $this->getPaymentData($reservation)
        ]);

    }

    /**
     * @param $reservation_id
     * @return Confirm|\Illuminate\Http\JsonResponse
     */
    public function confirm($reservation_id)
    {
        $reservation = Reservation::find($reservation_id);

        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found']);
        }

        if ($reservation->is_paid) {
            return response()->json(['error' => 'Reservation has been already paid']);
        }

        $this->paymentService->getMerchantConfig()->setConfig([
            'okUrl' => route('payment.success'),
            'failUrl' => route('payment.fail'),
        ]);

        $formFields = $this->paymentService->paymentMakeRequestParameters($this->getPaymentData($reservation));
        $formFields['setup']['nestpay']['3DGateUrl'] = $this->paymentService->get3DGateUrl();

        return new Confirm($formFields);
    }

    /**
     * The success url
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function success(Request $request)
    {
        $payment = null;
        $error = null;

        try {
            /**
             * the payment has been process successfully
             * THAT DOES NOT MEAN THAT CUSTOMER HAS PAID!!!!
             * FOR SUCCESSFULL PAYMENT SEE \App\Listeners\NestpayEventSubscriber!!!
             * DO NOT ADD CODE HERE FOR SUCCESSFULL PAYMENT!!!!
             */

            $payment = $this->paymentService->paymentProcess3DGateResponse($request->all());

        } catch (\Cubes\Nestpay\PaymentAlreadyProcessedException $paymentAlreadyProcessedException) {
            /**
             * the payment has been already processed
             * this error occures if customer refresh result page
             */

            $error = $paymentAlreadyProcessedException;
        } catch (\Exception $exception) {

            $error = $exception;
        } finally {
            try {
                $payment = $this->paymentService->getWorkingPayment();
            } catch (\LogicException $logicException) {
                $error = $logicException;
            }
        }

        return view('nestpay::result', [
            'payment' => $payment,
            'error' => $error,
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
    public function fail(Request $request)
    {
        $payment = null;
        $error = null;

        try {
            $payment = $this->paymentService->paymentProcess3DGateResponse($request->all(), true);

        } catch (\Cubes\Nestpay\PaymentAlreadyProcessedException $paymentAlreadyProcessedException) {
            /**
             * the payment has been already processed
             * this error occures if customer refresh result page
             */

            $error = $paymentAlreadyProcessedException;
        } catch (\Exception $exception) {

            $error = $exception;
        } finally {
            try {
                $payment = $this->paymentService->getWorkingPayment();
            } catch (\LogicException $logicException) {
                $error = $logicException;
            }
        }

        return view('nestpay::result', [
            'payment' => $payment,
            'exception' => $error,
            'isFail' => true
        ]);
    }

    /**
     * @param Reservation $reservation
     * @return array
     */
    protected function getPaymentData(Reservation $reservation): array
    {
        $firstPassenger = $reservation->data['passengers'][0];

        return [
            \Cubes\Nestpay\Payment::PROP_AMOUNT => $reservation->amount,
            \Cubes\Nestpay\Payment::PROP_CURRENCY => $reservation->currency_code,
            \Cubes\Nestpay\Payment::PROP_TRANTYPE => \Cubes\Nestpay\Payment::TRAN_TYPE_PREAUTH,
            \Cubes\Nestpay\Payment::PROP_LANG => app()->getLocale(),
            \Cubes\Nestpay\Payment::PROP_INVOICENUMBER => $reservation->id,
            \Cubes\Nestpay\Payment::PROP_DESCRIPTION => $reservation->id,
            \Cubes\Nestpay\Payment::PROP_COMMENTS => false,
            \Cubes\Nestpay\Payment::PROP_EMAIL => $reservation->data['email'],
            \Cubes\Nestpay\Payment::PROP_TEL => implode('', $reservation->data['phoneNumber']),
            \Cubes\Nestpay\Payment::PROP_BILLTONAME => sprintf('%s. %s %s', $firstPassenger['prefix'], $firstPassenger['first'], $firstPassenger['last']),
            \Cubes\Nestpay\Payment::PROP_BILLTOSTREET1 => $reservation->data['address']['street'],
            \Cubes\Nestpay\Payment::PROP_BILLTOCITY => $reservation->data['address']['city'],
            \Cubes\Nestpay\Payment::PROP_BILLTOPOSTALCODE => $reservation->data['address']['postalCode'],
            \Cubes\Nestpay\Payment::PROP_BILLTOCOUNTRY => $reservation->data['address']['country'],
        ];
    }
}
