<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class City
 * @package App\Models
 */
class City extends Model
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
        return $this->belongsTo(country::class, 'country_code', 'code');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function airports()
    {
        return $this->hasMany(Airport::class, 'city_code', 'code');
    }

    public function city()
    {
        return $this->belongsTo(self::class, 'code', 'code');
    }
}