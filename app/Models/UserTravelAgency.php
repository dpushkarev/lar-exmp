<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class UserTravelAgency
 * @package App\Models
 */
class UserTravelAgency extends Model
{
    public function travelAgency()
    {
        return $this->belongsTo(TravelAgency::class, 'travel_agency_id', 'id');
    }
}