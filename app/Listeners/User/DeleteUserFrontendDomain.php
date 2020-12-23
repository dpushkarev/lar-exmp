<?php


namespace App\Listeners\User;


use App\Models\User;
use App\Models\UserTravelAgency;

class DeleteUserFrontendDomain
{
    public function handle($event)
    {
        $model = $event->model;
        $user = null;

        if ($model instanceof UserTravelAgency) {
            /** @var User $user */
            $user = $model->user;
        }

        if ($model instanceof User && $model->isDirty('type')) {
            $user = $model;
        }

        if (is_null($user)) return true;

        if ($relations = $user->userFrontendDomains()) {
            $relations->delete();
        }
    }

}