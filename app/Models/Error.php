<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class Error
 * @package App\Models
 */
class Error extends Model
{

    protected $casts = [
        'error' => 'json',
    ];
}