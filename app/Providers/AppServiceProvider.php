<?php

namespace App\Providers;

use App\Logging\TravelPortLogger;
use App\Models\FlightsSearchFlightInfo;
use App\Models\User;
use App\Models\UserTravelAgency;
use App\Observers\CheckoutObserver;
use App\Observers\UserObserver;
use App\Observers\UserTravelAgencyObserver;
use FilippoToso\Travelport\TravelportLogger as BaseTravelPortLogger;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app->bind(BaseTravelPortLogger::class, TravelPortLogger::class);

        FlightsSearchFlightInfo::observe(CheckoutObserver::class);
    }
}
