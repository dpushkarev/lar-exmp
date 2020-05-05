<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class TP extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'TP';
    }
}