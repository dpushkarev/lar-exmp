<?php


namespace App\Models;


use App\Models\Traits\CacheTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Airline
 * @package App\Models
 */
class Airline extends Model
{
    const PRODUCTION_AIRLINE = 'P';

    protected static $cacheTags = ['airLines'];
    protected static $cacheMinutes = 0;

    use CacheTrait;

    /**
     * @return Airline[]|\Illuminate\Database\Eloquent\Collection
     */
    static public function getAll()
    {
        return static::where('vendor_type', static::PRODUCTION_AIRLINE)->orderBy('name', 'asc')->get();
    }
}