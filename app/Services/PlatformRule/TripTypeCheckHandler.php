<?php


namespace App\Services\PlatformRule;


use App\Models\FrontendDomainRule;

class TripTypeCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {
        $segments = $collection->get('request')->data['segments'];

        $usedType = [
            1 => FrontendDomainRule::ONE_WAY_TYPE,
            2 => FrontendDomainRule::RETURN_TYPE,
        ][count($segments)] ?? FrontendDomainRule::MULTI_TYPE;

        if ($usedType != $this->rule->trip_type) {
            return false;
        }

        return parent::check($collection);
    }
}