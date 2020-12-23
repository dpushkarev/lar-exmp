<?php

namespace App\Nova\Policies;

use App\Models\User;
use App\Nova\Policies\Types\AdminPolicy;

class UserTravelAgencyPolicy extends AdminPolicy
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
