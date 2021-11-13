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

    const TYPE_FIX = 'fix';
    const TYPE_PERCENT = 'percent';

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

    public function isIntesaFix(): bool
    {
        return ($this->intesa_fee_type == static::TYPE_FIX);
    }

    public function isACashFix(): bool
    {
        return ($this->cash_fee_type == static::TYPE_FIX);
    }

    public function getIntesaFee(Money $money)
    {
        if ($this->isIntesaFix()) {
            return Money::of($this->intesa_fee, $this->currency_code);
        }

        return $money->dividedBy(100)->multipliedBy($this->intesa_fee);
    }

    public function getCashFee(Money $money)
    {
        if ($this->isACashFix()) {
            return Money::of($this->cash_fee, $this->currency_code);
        }

        return $money->dividedBy(100)->multipliedBy($this->cash_fee);
    }

}