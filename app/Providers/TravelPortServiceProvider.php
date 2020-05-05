<?php

namespace App\Providers;

use App\Services\TravelPortService;
use Illuminate\Support\Facades\App;
use FilippoToso\Travelport\Travelport;
use FilippoToso\Travelport\Endpoints;
use Illuminate\Support\ServiceProvider;

class TravelPortServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('TP', function () {
            return App::makeWith(TravelPortService::class, ['travelPort' => new Travelport(
                config('services.travel_port.user_id'),
                config('services.travel_port.password'),
                config('services.travel_port.target_branch'),
                Endpoints::REGION_EMEA,
                config('app.env') === 'production' ? true : false,
                null
            )]);
        });
    }
}
