<?php

namespace App\Providers;

use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Country;
use App\Models\FrontendDomain;
use App\Models\FrontendDomainRule;
use App\Models\Reservation;
use App\Models\TravelAgency;
use App\Models\UserFrontendDomain;
use App\Models\UserTravelAgency;
use App\Nova\Policies\DictionariesPolicy;
use App\Nova\Policies\FrontendDomainPolicy;
use App\Nova\Policies\ReservationPolicy;
use App\Nova\Policies\TravelAgencyPolicy;
use App\Nova\Policies\UserFrontendDomainPolicy;
use App\Nova\Policies\UserPolicy;
use App\Models\User;
use App\Nova\Policies\UserTravelAgencyPolicy;
use App\Observers\FrontendDomainRuleObserver;
use Illuminate\Support\Facades\Gate;
use Laravel\Nova\Cards\Help;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Nova::serving(function () {
            /** Users policy */
            Gate::policy(User::class, UserPolicy::class);

            /** Dictionaries policy */
            Gate::policy(Country::class, DictionariesPolicy::class);
            Gate::policy(City::class, DictionariesPolicy::class);
            Gate::policy(Airport::class, DictionariesPolicy::class);
            Gate::policy(Airline::class, DictionariesPolicy::class);
            Gate::policy(Aircraft::class, DictionariesPolicy::class);

            /** Business policy */
            Gate::policy(Reservation::class, ReservationPolicy::class);
            Gate::policy(TravelAgency::class, TravelAgencyPolicy::class);
            Gate::policy(FrontendDomain::class, FrontendDomainPolicy::class);
            Gate::policy(UserTravelAgency::class, UserTravelAgencyPolicy::class);
            Gate::policy(UserFrontendDomain::class, UserFrontendDomainPolicy::class);

            /** Observers */
            FrontendDomainRule::observe(FrontendDomainRuleObserver::class);
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
                ->withAuthenticationRoutes()
                ->withPasswordResetRoutes()
                ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function (User $user) {
            return $user->isGod() ||
                $user->isActive();
        });
    }

    /**
     * Get the cards that should be displayed on the default Nova dashboard.
     *
     * @return array
     */
    protected function cards()
    {
        return [
            new Help,
        ];
    }

    /**
     * Get the extra dashboards that should be displayed on the Nova dashboard.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
