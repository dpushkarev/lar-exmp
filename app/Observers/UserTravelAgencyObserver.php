<?php

namespace App\Observers;

use App\Events\User\UserTravelAgencyUpdated;
use App\Models\UserTravelAgency;

class UserTravelAgencyObserver
{
    public function created(UserTravelAgency $userTravelAgency)
    {
        UserTravelAgencyUpdated::dispatch($userTravelAgency);
    }

    public function updated(UserTravelAgency $userTravelAgency)
    {
        UserTravelAgencyUpdated::dispatch($userTravelAgency);
    }

    public function deleted(UserTravelAgency $userTravelAgency)
    {
        UserTravelAgencyUpdated::dispatch($userTravelAgency);
    }

}
