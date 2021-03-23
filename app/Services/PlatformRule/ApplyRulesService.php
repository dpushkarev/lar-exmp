<?php


namespace App\Services\PlatformRule;


use App\Models\FrontendDomain;
use App\Models\FrontendDomainRule;
use Libs\Money;

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
        $LowFareSearchResponse->get('results')->get('groupsData')->get('prices')->transform(function ($item) use (
            $LowFareSearchResponse,
            $platform,
            $rules
        ) {
            $fee = Money::zero($platform->currency_code);
            $totalPrice = Money::of($item['totalPrice']['amount'], $item['totalPrice']['currency']);
            $agencyFeeAmounts = [];

            foreach ($item['passengerFares'] as $passengerFare) {
                $agencyFee = Money::zero($platform->currency_code);

                if ($rules->isNotEmpty()) {
                    /** @var FrontendDomainRule $rule */
                    foreach ($rules as $rule) {
                        $commonHandler = new DateCheckHandler($rule);
                        $commonHandler->with(new TripTypeCheckHandler($rule))
                            ->with(new RouteCheckHandler($rule));

                        $resultHandler = new AmountCheckHandler($rule);
                        $resultHandler->with(new CabinClassCheckHandler($rule));

                        $passengerHandler = new PassengerTypeCheckHandler($rule);

                        $commonHandlerChecked = $commonHandler->check($LowFareSearchResponse);

                        if ($commonHandlerChecked &&
                            $resultHandler->check($item) &&
                            $passengerHandler->check($passengerFare['type'])
                        ) {
                            $agencyFee = $rule->getAgencyFee($totalPrice);
                            break;
                        }
                    }
                }

                if ($agencyFee->isZero()) {
                    $agencyFee = $platform->getAgencyFee();
                }

                $agencyFeeAmounts[$passengerFare['type']] = $agencyFee->getAmountAsFloat();
                $fee = $fee->plus($agencyFee->multipliedBy($passengerFare['count']));

            }

            $item['agencyCharge'] = [
                'amount' => $fee->getAmountAsFloat(),
                'currency' => $fee->getCurrency()->getCurrencyCode(),
                'regular' => [
                    FrontendDomainRule::TYPE_ADT => $agencyFeeAmounts[FrontendDomainRule::TYPE_ADT],
                    FrontendDomainRule::TYPE_CLD => $agencyFeeAmounts[FrontendDomainRule::TYPE_CLD_ALTER] ?? 0,
                    FrontendDomainRule::TYPE_INF => $agencyFeeAmounts[FrontendDomainRule::TYPE_INF] ?? 0,
                ],
                'brand' => [
                    FrontendDomainRule::TYPE_ADT => $agencyFeeAmounts[FrontendDomainRule::TYPE_ADT],
                    FrontendDomainRule::TYPE_CLD => $agencyFeeAmounts[FrontendDomainRule::TYPE_CLD_ALTER] ?? 0,
                    FrontendDomainRule::TYPE_INF => $agencyFeeAmounts[FrontendDomainRule::TYPE_INF] ?? 0,
                ]
            ];

            $item['totalPrice'] = [
                'amount' => $totalPrice->plus($fee)->getAmountAsFloat(),
                'currency' => $totalPrice->getCurrency()->getCurrencyCode(),
            ];

            return $item;
        });

        return $LowFareSearchResponse;
    }


}