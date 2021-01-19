<?php

namespace App\Http\Controllers;

use App\Models\CabinClass;
use App\Models\FrontendDomainRule;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{

    public function index()
    {
        $ob = FrontendDomainRule::first();
        dd($ob->cabinClasses);
    }
}
