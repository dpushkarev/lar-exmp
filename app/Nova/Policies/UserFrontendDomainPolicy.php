<?php

namespace App\Nova\Policies;


use App\Models\User;

class UserFrontendDomainPolicy extends \App\Nova\Policies\Types\TravelAgencyPolicy
{
    public function update(User $user, $model)
    {
        return false;
    }

    public function restore(User $user, $model)
    {
        return false;
    }
}
