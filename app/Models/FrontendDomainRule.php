<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Libs\Money;

/**
 * Class FrontendDomainRule
 * @package App\Models
 */
class FrontendDomainRule extends Model
{
    protected $table = 'platform_rules';

    const ONE_WAY_TYPE = 'one_way';
    const RETURN_TYPE = 'return';
    const MULTI_TYPE = 'multi';

    const TYPE_FIX = 'fix';
    const TYPE_PERCENT = 'percent';

    const TYPE_ADT = 'ADT';
    const TYPE_CLD = 'CLD';
    const TYPE_CLD_ALTER = 'CNN';
    const TYPE_INF = 'INF';

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function origin()
    {
        return $this->belongsTo(VocabularyName::class, 'origin_id', 'id');
    }

    public function destination()
    {
        return $this->belongsTo(VocabularyName::class, 'destination_id', 'id');
    }

    public function platform()
    {
        return $this->belongsTo(FrontendDomain::class);
    }

    public function setCabinClassesAttribute($value)
    {
        $this->attributes['cabin_classes'] = implode(',', $value);
    }

    public function setPassengerTypesAttribute($value)
    {
        $this->attributes['passenger_types'] = implode(',', $value);
    }

    public function setFareTypesAttribute($value)
    {
        $this->attributes['fare_types'] = implode(',', $value);
    }

    public function getCabinClassesAttribute($value)
    {
        return explode(',', $value);
    }

    public function getPassengerTypesAttribute($value)
    {
        return explode(',', $value);
    }

    public function getFareTypesAttribute($value)
    {
        return explode(',', $value);
    }

    public function getMinPriceAttribute()
    {
        if (is_null($this->min_amount)) return null;

        return Money::of($this->min_amount, $this->platform->currency_code);
    }

    public function getMaxPriceAttribute()
    {
        if (is_null($this->max_amount)) return null;

        return Money::of($this->max_amount, $this->platform->currency_code);
    }

    public function isAgencyFeeFix(): bool
    {
        return ($this->agency_fee_type == static::TYPE_FIX);
    }

    public function getAgencyFee(Money $money)
    {
        if ($this->isAgencyFeeFix()) {
            return Money::of($this->agency_fee, $this->platform->currency_code);
        }

        return $money->dividedBy(100)->multipliedBy($this->agency_fee);
    }
}