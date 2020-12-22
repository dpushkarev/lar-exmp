<?php

namespace App\Providers;

use App\Events\User\UserCreated;
use App\Events\User\UserCreating;
use App\Events\User\UserUpdated;
use App\Listeners\Payment\PaymentAlert;
use App\Listeners\Payment\UpdateReservation;
use App\Listeners\User\SetCreator;
use App\Listeners\User\SetTravelAgency;
use Cubes\Nestpay\Laravel\NestpayPaymentProcessedErrorEvent;
use Cubes\Nestpay\Laravel\NestpayPaymentProcessedFailedEvent;
use Cubes\Nestpay\Laravel\NestpayPaymentProcessedSuccessfullyEvent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
        NestpayPaymentProcessedErrorEvent::class => [],
        UserUpdated::class => [

        ],
        UserCreated::class => [
            SetTravelAgency::class,
        ],
        UserCreating::class => [
            SetCreator::class
        ]
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
