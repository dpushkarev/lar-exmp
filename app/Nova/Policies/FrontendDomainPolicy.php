<?php

namespace App\Nova\Policies;

use App\Models\User;
use App\Nova\Policies\Types\AdminPolicy;

class FrontendDomainPolicy extends AdminPolicy
{
   public function view(User $user)
   {
       return parent::view($user) || $user->isTravelAgency();
   }

   public function viewAny(User $user)
   {
       return parent::view($user) || $user->isTravelAgency();
   }
}
