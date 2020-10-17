<?php


namespace App\Listeners\Payment;


use Cubes\Nestpay\Laravel\NestpayPaymentProcessedSuccessfullyEvent;
use Illuminate\Support\Facades\Mail;

class PaymentAlert
{
    /** @var NestpayPaymentProcessedSuccessfullyEvent $event */
    public function handle($event)
    {
        $payment = $event->getPayment();
        $reservation = $payment->reservation;
        $template = $payment->isSuccess() ? 'mail.nestpaysuccess' : 'mail.nestpayfail';

        Mail::send($template, ['payment' => $payment->toArray(), 'reservation' => $reservation], function ($message) use($payment){
            $message->to($payment->getEmail(), 'Receiver')->subject('Information about reservation');
        });
    }
}