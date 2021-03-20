<?php


namespace App\Services\PlatformRule;


use App\Models\FrontendDomain;
use App\Models\FrontendDomainRule;

class ApplyRulesService
{
    private function applyRule(AbstractHandler $commonHandler, AbstractHandler $resultHandler, $collection, FrontendDomainRule $rule)
    {
        if ($commonHandler->check($collection)) {
            echo '<pre>';

            $prices = $collection->get('results')->get('groupsData')->get('prices');

            foreach ($prices as $price) {
                if ($resultHandler->check($price)) {

                }
            }

            return true;
        }

        return false;
    }

    public function coverLowFareSearch(FrontendDomain $platform, $LowFareSearchResponse)
    {
        $rules = $platform->rules;

        if ($rules->isEmpty()) {
            return true;
        }

        foreach ($rules as $rule) {
            $commonHandler = new DateCheckHandler($rule);

            $commonHandler->with(new TripTypeCheckHandler($rule))
                ->with(new PassengerTypeCheckHandler($rule))
                ->with(new RouteCheckHandler($rule));

            $resultHandler = new AmountCheckHandler($rule);
            $resultHandler->with(new CabinClassCheckHandler($rule));

            if ($commonHandler->check($LowFareSearchResponse)) {
                echo '<pre>';

                $prices = $LowFareSearchResponse->get('results')->get('groupsData')->get('prices');

                foreach ($prices as $price) {
                    if ($resultHandler->check($price)) {

                    }
                }
            }

            die();
        }
    }


}