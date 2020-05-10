<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class Airport
 * @package App\Models
 */
class Airport extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function names()
    {
        return $this->morphOne(VocabularyName::class, 'nameable');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function city()
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }
}