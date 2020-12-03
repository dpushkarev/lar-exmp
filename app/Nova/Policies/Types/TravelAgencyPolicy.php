<?php


namespace App\Nova\Policies\Types;


use App\Models\User;

abstract class TravelAgencyPolicy extends AdminPolicy
{
    protected function checkPermission(User $user, $model = null)
    {
        return (parent::checkPermission($user, $model) || $user->isTravelAgency());
    }
}