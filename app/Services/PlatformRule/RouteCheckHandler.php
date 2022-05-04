<?php


namespace App\Services\PlatformRule;


class RouteCheckHandler extends AbstractHandler
{
    public function check($collection): bool
    {
        $segments = $collection->get('request')->data['segments'];

        if (!is_null($this->rule->origin_id)) {
            $originMatched = false;
            foreach (array_column($segments, 'departure') as $departure) {
                if ($this->rule->origin->nameable->code == $departure['IATA']) {
                    $originMatched = true;
                }
            }

            if (!$originMatched) {
                return false;
            }
        }

        if (!is_null($this->rule->destination_id)) {
            $destinationMatched = false;
            foreach (array_column($segments, 'arrival') as $arrival) {
                if ($this->rule->destination->nameable->code == $arrival['IATA']) {
                    $destinationMatched = true;
                }
            }

            if (!$destinationMatched) {
                return false;
            }
        }

        return parent::check($collection);
    }
}
