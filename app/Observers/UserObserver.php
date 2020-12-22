<?php

namespace App\Observers;

use App\Events\User\UserCreated;
use App\Events\User\UserCreating;
use App\Events\User\UserUpdated;
use App\Models\User;

class UserObserver
{
    public function created(User $user)
    {
        UserCreated::dispatch($user);
    }

    public function updated(User $user)
    {
        UserUpdated::dispatch($user);
    }

    public function creating(User $user)
    {
        UserCreating::dispatch($user);
    }

}
