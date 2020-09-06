<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FlightsSearchFlightInfo
 * @package App\Models
 */
class FlightsSearchFlightInfo extends Model
{
    const IS_BOOKED = 1;
    const IS_NOT_BOOKED = 0;

    public function result()
    {
        return $this->belongsTo(FlightsSearchResult::class, 'flight_search_result_id', 'id');
    }

    /**
     * @return bool
     */
    public function isBooked(): bool
    {
        return ($this->booked === static::IS_BOOKED);
    }
}