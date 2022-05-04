<?php


namespace App\Models;


use App\Models\Traits\CacheTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Airline
 * @package App\Models
 */
class Aircraft extends Model
{
    const PRODUCTION_AIRLINE = 'P';

    protected static $cacheTags = ['airCrafts'];
    protected static $cacheMinutes = 0;
    protected $table = 'aircrafts';

    use CacheTrait;
}
