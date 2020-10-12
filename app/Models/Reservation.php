<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Reservation
 * @package App\Models
 * @property $data
 */
class Reservation extends Model
{
    const IS_PAID = 1;
    const IS_NOT_PAID = 0;

    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'json'
    ];

    public function flightInfo()
    {
        return $this->belongsTo(FlightsSearchFlightInfo::class, 'flights_search_flight_info_id', 'id');
    }
}