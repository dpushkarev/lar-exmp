<?php

namespace App\Observers;

use App\Models\FrontendDomain;

class FrontendDomainObserver
{
    public function creating(FrontendDomain $model)
    {
        $model->token = md5(rand(1, 10) . microtime());
    }
}
