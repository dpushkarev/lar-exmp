<?php


namespace App\Models;


use App\Models\Traits\CacheTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Country
 * @package App\Models
 */
class Country extends Model
{
    protected static $cacheTags = ['countries'];
    protected static $cacheMinutes = 0;

    use CacheTrait;

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

    /**
     * @return Country[]|\Illuminate\Database\Eloquent\Collection
     */
    static public function getAll()
    {
        return static::orderBy('name', 'asc')->get();
    }
}