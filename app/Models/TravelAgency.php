<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class City
 * @package App\Models
 */
class TravelAgency extends Model
{
    public function frontendDomains()
    {
        return $this->hasMany(FrontendDomain::class, 'travel_agency_id', 'id');
    }
}