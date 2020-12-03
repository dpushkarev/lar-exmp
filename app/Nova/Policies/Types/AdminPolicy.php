<?php


namespace App\Nova\Policies\Types;


use App\Models\User;

abstract class AdminPolicy extends GodPolicy
{
    protected function checkPermission(User $user, $model = null)
    {
        return (parent::checkPermission($user, $model) || $user->isAdmin());
    }
}