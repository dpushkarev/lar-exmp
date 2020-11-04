<?php

namespace App\Observers;

use App\Models\Reservation;

class ReservationObserver
{
    public function creating(Reservation $model)
    {
        $model->code = getUniqueCode(5);
    }
}
