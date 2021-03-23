<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Libs\Money;

/**
 * Class FrontendDomain
 * @package App\Models
 */
class FrontendDomain extends Model
{
    protected $table = 'platforms';

    public function travelAgency()
    {
        return $this->belongsTo(TravelAgency::class, 'travel_agency_id', 'id');
    }

    public function rules()
    {
        return $this->hasMany(FrontendDomainRule::class, 'platform_id', 'id');
    }

    public function getAgencyFee()
    {
        return Money::of($this->agency_fee_default, $this->currency_code);
    }
}