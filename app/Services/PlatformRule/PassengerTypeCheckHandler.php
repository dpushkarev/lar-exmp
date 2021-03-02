<?php


namespace App\Services\PlatformRule;


class PassengerTypeCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {
        $passengers = $collection->get('request')->data['passengers'];

        print_r($passengers);die;

        return parent::check($collection);
    }
}