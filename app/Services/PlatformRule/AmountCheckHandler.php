<?php


namespace App\Services\PlatformRule;


use Libs\Money;

class AmountCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {
        $minPrice = $this->rule->min_price;
        $maxPrice = $this->rule->max_price;
        $totalPrice = Money::of($collection['totalPrice']['amount'], $collection['totalPrice']['currency']);

        if (!is_null($minPrice) && $totalPrice->isLessThan($minPrice)) {
            return false;
        }

        if (!is_null($maxPrice) && $totalPrice->isGreaterThan($maxPrice)) {
            return false;
        }

        return parent::check($collection);
    }
}