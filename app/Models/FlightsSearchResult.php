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

}