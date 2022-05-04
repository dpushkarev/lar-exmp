<?php

namespace App\Http\Controllers;

use App\Http\Resources\Payment\Confirm;
use App\Models\Reservation;
use App\Services\PaymentService;
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

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function confirment($reservationCode)
    {
        $reservation = Reservation::whereCode($reservationCode)->first();

        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found']);
        }

        if ($reservation->is_paid) {
            return response()->json(['error' => 'Reservation has been already paid']);
        }

        return view('nestpay::confirm', [
            'paymentData' => $this->paymentService->getPaymentData($reservation),
            'reservation' => $reservation
        ]);
    }

    /**
     * @param $reservationCode
     * @return Confirm|\Illuminate\Http\JsonResponse
     */
    public function confirm($reservationCode)
    {
        $reservation = Reservation::whereCode($reservationCode)->first();

        if (!$reservation) {
            return response()->json(['error' => 'The reservation not found']);
        }

        if ($reservation->is_paid) {
            return response()->json(['error' => 'The reservation has been already paid']);
        }

        $formFields = $this->paymentService->getFormFields($reservation);

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
        $payment = $this->paymentService->processCallback($request);

        return view('web.sections.payment.success', [
            'payment' => $payment,
            'reservation' => $payment->reservation,
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
        $payment = $this->paymentService->processCallback($request, true);

        return view('web.sections.payment.fail', [
            'payment' => $payment,
            'reservation' => $payment->reservation,
        ]);
    }

}
