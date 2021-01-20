<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class FrontendDomainRule
 * @package App\Models
 */
class FrontendDomainRule extends Model
{

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

    public function frontendDomain()
    {
        return $this->belongsTo(FrontendDomain::class);
    }

    public function setCabinClassesAttribute($value)
    {
        $this->attributes['cabin_classes'] = implode(',', $value);
    }

    public function setTripTypesAttribute($value)
    {
        $this->attributes['trip_types'] = implode(',', $value);
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

    public function getTripTypesAttribute($value)
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

}