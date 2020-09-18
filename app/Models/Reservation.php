<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Reservation
 * @package App\Models
 */
class Reservation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'data' => 'json'
    ];

    public function flightInfo()
    {
        return $this->belongsTo(FlightsSearchFlightInfo::class, 'flights_search_flight_info_id', 'id');
    }
}