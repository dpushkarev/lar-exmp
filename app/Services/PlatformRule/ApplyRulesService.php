<?php


namespace App\Services\PlatformRule;


use App\Models\FrontendDomain;

class ApplyRulesService
{
    private function applyRule(AbstractHandler $handler, $collection)
    {
        if ($handler->check($collection)) {
            return true;
        }

        return false;
    }

    public function coverLowFareSearch(FrontendDomain $platform, $LowFareSearchResponse)
    {
        $rules = $platform->frontendDomainRules;

        if ($rules->isEmpty()) {
            return true;
        }

        foreach ($rules as $rule) {
            $handler = new DateCheckHandler($rule);

            $handler->with(new TripTypeCheckHandler($rule))
                ->with(new PassengerTypeCheckHandler($rule))
                ->with(new AmountCheckHandler($rule))
                ->with(new RouteCheckHandler($rule))
                ->with(new CabinClassCheckHandler($rule));

            if ($this->applyRule($handler, $LowFareSearchResponse)) {
                echo 'Rule is fit';
            }

            die();
        }
    }


}