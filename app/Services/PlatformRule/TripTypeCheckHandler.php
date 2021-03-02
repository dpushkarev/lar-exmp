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

        if (!empty($this->rule->trip_types[0])) {
            $checked = false;

            foreach ($this->rule->trip_types as $type) {
                if ($usedType == $type) $checked = true;
            }

            if (!$checked) return false;
        }

        echo __CLASS__ . PHP_EOL;
        return parent::check($collection);
    }
}