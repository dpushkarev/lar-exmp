<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FlightsSearchRequest
 * @package App\Models
 */
class FlightsSearchRequest extends Model
{
    protected $casts = [
        'data' => 'json'
    ];
}