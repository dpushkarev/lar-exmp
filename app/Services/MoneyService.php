<?php


namespace App\Services;


use Libs\Money;

class MoneyService
{
    public function getMoneyByString($string): Money
    {
        if (is_null($string)) return Money::zero('RSD');

        return Money::of(substr($string, 3), substr($string, 0, 3));
    }

}

