<?php


namespace App\Listeners\Payment;


use Cubes\Nestpay\Laravel\NestpayPaymentProcessedSuccessfullyEvent;

class PaymentAlert
{
    /** @var NestpayPaymentProcessedSuccessfullyEvent $event */
    public function handle($event)
    {
//        $payment = $event->getPayment();
//
//        Mail::to(
//            'psuk@bk.ru',
//            $payment->getProperty(Payment::PROP_EMAIL),
//            $payment->getProperty(Payment::PROP_BILLTONAME)
//        )->send(new NestpayPaymentMail($payment));
    }
}