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

    public function setCabinClassesAttribute($value)
    {
        $this->attributes['cabin_classes'] = implode(',', $value);
    }

    public function getCabinClassesAttribute($value)
    {
        return explode(',', $value);
    }

}