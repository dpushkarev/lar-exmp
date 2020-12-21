<?php

namespace App\Observers;

use App\Events\User\UserCreate;
use App\Events\User\UserUpdate;
use App\Models\User;

class UserObserver
{
    public function created(User $user)
    {
        UserCreate::dispatch($user);
    }

    public function updated(User $user)
    {
        UserUpdate::dispatch($user);
    }

}
