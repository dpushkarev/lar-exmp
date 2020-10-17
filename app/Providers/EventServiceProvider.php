<?php

namespace App\Providers;

use App\Listeners\Payment\PaymentAlert;
use App\Listeners\Payment\UpdateReservation;
use Cubes\Nestpay\Laravel\NestpayPaymentProcessedErrorEvent;
use Cubes\Nestpay\Laravel\NestpayPaymentProcessedFailedEvent;
use Cubes\Nestpay\Laravel\NestpayPaymentProcessedSuccessfullyEvent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        NestpayPaymentProcessedSuccessfullyEvent::class => [
            UpdateReservation::class,
            PaymentAlert::class,
        ],
        NestpayPaymentProcessedFailedEvent::class => [
            PaymentAlert::class,
        ],
        NestpayPaymentProcessedErrorEvent::class => []
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
