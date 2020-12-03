<?php


namespace App\Nova\Policies\Types;


use App\Models\User;

abstract class TravelAgentPolicy extends TravelAgencyPolicy
{
    protected function checkPermission(User $user, $model = null)
    {
        return (parent::checkPermission($user, $model) || $user->isTravelAgent());
    }
}