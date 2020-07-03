<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FlightsSearchResult
 * @package App\Models
 */
class FlightsSearchResult extends Model
{
    protected $casts = [
        'segments' => 'array'
    ];

    public function request()
    {
        return $this->belongsTo(FlightsSearchRequest::class, 'flight_search_request_id', 'id');
    }

}