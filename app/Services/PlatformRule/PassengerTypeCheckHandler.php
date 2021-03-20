<?php


namespace App\Services\PlatformRule;


class PassengerTypeCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {
        $passengersRequest = array_column($collection->get('request')->data['passengers'], 'type');
        $passengerRule = $this->rule->passenger_types;

        if (empty(array_intersect($passengerRule, $passengersRequest))) {
            return false;
        }

        echo __CLASS__ . PHP_EOL;
        return parent::check($collection);
    }
}