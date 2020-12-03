<?php

namespace App\Nova\Policies;

use App\Models\User;
use App\Nova\Policies\Types\AdminPolicy;
use App\Nova\Policies\Types\GodPolicy;
use Illuminate\Auth\Access\HandlesAuthorization;

class DictionariesPolicy extends AdminPolicy
{

}
