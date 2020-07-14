<?php

namespace App\Providers;

use App\Logging\TravelPortLogger;
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
    }
}
