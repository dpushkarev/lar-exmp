<?php


namespace App\Listeners\Payment;


use App\Models\Reservation;
use Cubes\Nestpay\Laravel\NestpayPaymentProcessedSuccessfullyEvent;
use Cubes\Nestpay\Payment;

class UpdateReservation
{
    public function handle(NestpayPaymentProcessedSuccessfullyEvent $event)
    {
        $payment = $event->getPayment();

        Reservation::where('id', $payment->getProperty(Payment::PROP_INVOICENUMBER))
            ->update(['is_paid' => Reservation::IS_PAID]);
    }
}