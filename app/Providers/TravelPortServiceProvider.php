<?php

namespace App\Providers;

use App\Logging\TravelPortLogger;
use App\Services\TravelPortService;
use Illuminate\Support\Facades\App;
use FilippoToso\Travelport\Endpoints;
use Illuminate\Support\ServiceProvider;
use Libs\FilippoToso\Travelport;

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
                resolve(\FilippoToso\Travelport\TravelportLogger::class)
            )]);
        });

//        App::bind('TP', function () {
//            return App::makeWith(TravelPortService::class, ['travelPort' => new Travelport(
//                config('services.travel_port.user_id'),
//                config('services.travel_port.password'),
//                config('services.travel_port.target_branch'),
//                Endpoints::REGION_EMEA,
//                config('app.env') === 'production' ? true : false,
//                resolve(\FilippoToso\Travelport\TravelportLogger::class)
//            )]);
//        });

        App::bind('TP', function () {
            return App::makeWith(TravelPortService::class, ['travelPort' => new Travelport(
                'Universal API/uAPI2405065644-c384cfd6',
                'Xk9}g%P67c',
                'P3589307',
                Endpoints::REGION_EMEA,
                true,
                resolve(\FilippoToso\Travelport\TravelportLogger::class)
            )]);
        });
    }
}
