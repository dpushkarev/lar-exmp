<?php

namespace App\Models;

use Cubes\Nestpay\Laravel\PaymentModel as Model;

class NestpayPayment extends Model
{
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'INVOICENUMBER', 'id');
    }
}