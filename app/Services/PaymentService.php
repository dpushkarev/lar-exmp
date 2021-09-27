<?php


namespace App\Services;


use App\Exceptions\PaymentException;
use App\Models\Reservation;
use Cubes\Nestpay\MerchantService;
use Illuminate\Http\Request;

class PaymentService
{
    protected $nestpayService;

    public function __construct(MerchantService $nestpayService)
    {
        $this->nestpayService = $nestpayService;
    }

    public function getFormFields(Reservation $reservation)
    {
        $this->nestpayService->getMerchantConfig()->setConfig([
            'okUrl' => route('payment.success'),
            'failUrl' => route('payment.fail'),
        ]);

        $formFields = $this->nestpayService->paymentMakeRequestParameters($this->getPaymentData($reservation));
        $formFields['setup']['nestpay']['3DGateUrl'] = $this->nestpayService->get3DGateUrl();

        return $formFields;
    }


    public function processCallback(Request $request, $fail = false)
    {
        $payment = null;
        $exception = null;

        try {
            /**
             * the payment has been process successfully
             * THAT DOES NOT MEAN THAT CUSTOMER HAS PAID!!!!
             * FOR SUCCESSFULL PAYMENT SEE \App\Listeners\NestpayEventSubscriber!!!
             * DO NOT ADD CODE HERE FOR SUCCESSFULL PAYMENT!!!!
             */

            $payment = $this->nestpayService->paymentProcess3DGateResponse($request->all(), $fail);

        } catch (\Cubes\Nestpay\PaymentAlreadyProcessedException $paymentAlreadyProcessedException) {
            /**
             * the payment has been already processed
             * this error occures if customer refresh result page
             */

            $exception = PaymentException::getInstance($paymentAlreadyProcessedException->getMessage());
        } catch (\Exception $exception) {
            $exception = PaymentException::getInstance($exception->getMessage());
        } finally {
            try {
                $payment = $this->nestpayService->getWorkingPayment();
            } catch (\LogicException $logicException) {
                $exception = PaymentException::getInstance($logicException->getMessage());
            }
        }

        if (!is_null($exception)) {
            app('sentry')->captureException($exception);
        }

        return $payment;
    }

    /**
     * @param Reservation $reservation
     * @return array
     */
    public function getPaymentData(Reservation $reservation): array
    {
        $firstPassenger = $reservation->data['passengers'][0];

        return [
            \Cubes\Nestpay\Payment::PROP_AMOUNT => $reservation->total_price,
            \Cubes\Nestpay\Payment::PROP_CURRENCY => $reservation->currency_code,
            \Cubes\Nestpay\Payment::PROP_TRANTYPE => \Cubes\Nestpay\Payment::TRAN_TYPE_PREAUTH,
            \Cubes\Nestpay\Payment::PROP_LANG => app()->getLocale(),
            \Cubes\Nestpay\Payment::PROP_INVOICENUMBER => $reservation->id,
            \Cubes\Nestpay\Payment::PROP_DESCRIPTION => $reservation->id,
            \Cubes\Nestpay\Payment::PROP_COMMENTS => false,
            \Cubes\Nestpay\Payment::PROP_EMAIL => $reservation->data['email'],
            \Cubes\Nestpay\Payment::PROP_TEL => implode('', $reservation->data['phoneNumber']),
            \Cubes\Nestpay\Payment::PROP_BILLTONAME => sprintf('%s %s', $firstPassenger['first'], $firstPassenger['last']),
            \Cubes\Nestpay\Payment::PROP_BILLTOSTREET1 => $reservation->data['address']['street'],
            \Cubes\Nestpay\Payment::PROP_BILLTOCITY => $reservation->data['address']['city'],
            \Cubes\Nestpay\Payment::PROP_BILLTOPOSTALCODE => $reservation->data['address']['postalCode'],
            \Cubes\Nestpay\Payment::PROP_BILLTOCOUNTRY => $reservation->data['address']['country'],
        ];
    }
}