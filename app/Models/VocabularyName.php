<?php


namespace App\Models;


use App\Models\Traits\CacheTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Class VocabularyName
 * @package App\Models
 */
class VocabularyName extends Model
{
    use CacheTrait;

    protected static $cacheTags = ['vocabulary'];
    protected static $cacheMinutes = 0;

    static public function getByName($q)
    {
        return VocabularyName::where('name', 'like', $q . '%')
            ->with(['nameable.city.airports', 'nameable.country'])
            ->limit(15)
            ->distinct(['nameable_id', 'nameable_type'])
            ->get(['nameable_id', 'nameable_type']);
    }

    public function nameable()
    {
        return $this->morphTo();
    }
}