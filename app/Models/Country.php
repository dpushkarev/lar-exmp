<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class Country
 * @package App\Models
 */
class Country extends Model
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cities()
    {
        return $this->hasMany(City::class, 'country_code', 'code');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function airports()
    {
        return $this->hasMany(Airport::class, 'country_code', 'code');
    }
}