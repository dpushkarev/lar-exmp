<?php


namespace App\Services\PlatformRule;


class PassengerTypeCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {
        if (!in_array($collection, $this->rule->passenger_types)) {
            return false;
        }

        return true;
    }
}