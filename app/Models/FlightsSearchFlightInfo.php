<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FlightsSearchFlightInfo
 * @package App\Models
 */
class FlightsSearchFlightInfo extends Model
{

    public function result()
    {
        return $this->belongsTo(FlightsSearchResult::class, 'flight_search_result_id', 'id');
    }

    public function reservation()
    {
        return $this->hasOne(Reservation::class, 'flights_search_flight_info_id', 'id');
    }

}