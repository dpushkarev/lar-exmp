<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class VocabularyName
 * @package App\Models
 */
class VocabularyName extends Model
{
    public function nameable()
    {
        return $this->morphTo();
    }
}