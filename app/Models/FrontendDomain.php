<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class FrontendDomain
 * @package App\Models
 */
class FrontendDomain extends Model
{
    public function travelAgency()
    {
        return $this->belongsTo(TravelAgency::class, 'travel_agency_id', 'id');
    }
}