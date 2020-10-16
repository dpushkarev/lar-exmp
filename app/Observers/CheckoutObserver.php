<?php

namespace App\Observers;

use App\Models\FlightsSearchFlightInfo;

class CheckoutObserver
{
    public function creating(FlightsSearchFlightInfo $model)
    {
        $model->code = getUniqueCode(5);
    }
}
