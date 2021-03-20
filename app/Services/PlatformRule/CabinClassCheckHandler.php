<?php


namespace App\Services\PlatformRule;


class CabinClassCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {

        $serviceClasses = array_unique(array_column($collection['segmentInfo'], 'serviceClass'));
        $cabinClasses = $this->rule->cabin_classes;

        if (empty(array_intersect($cabinClasses, $serviceClasses))) {
            return false;
        }

        echo __CLASS__ . PHP_EOL;
        return parent::check($collection);
    }
}