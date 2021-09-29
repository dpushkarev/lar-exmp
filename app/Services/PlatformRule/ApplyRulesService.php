<?php


namespace App\Services\PlatformRule;


use App\Dto\AirReservationRequestDto;
use App\Models\FlightsSearchResult;
use App\Models\FrontendDomain;
use App\Models\FrontendDomainRule;
use App\Services\MoneyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Libs\Money;

class ApplyRulesService
{
    private $moneyService;

    public function __construct(MoneyService $moneyService)
    {
        $this->moneyService = $moneyService;
    }

    /**
     * @param Money $totalPrice
     * @param array $passengers
     * @param int|null $ruleId
     * @return array['totalPrice' => Money[]]
     * @throws \Brick\Money\Exception\MoneyMismatchException
     */
    private function coverTotalPrice(Money $totalPrice, array $passengers, ?int $ruleId): array
    {
        /** @var FrontendDomain $platform */
        $platform = App::make('platform');
        $fee = Money::zero($platform->currency_code);
        $cashFee = Money::zero($platform->currency_code);
        $intesaFee = Money::zero($platform->currency_code);
        $payPalFee = Money::zero($platform->currency_code);

        $agencyFeeAmounts = [
            FrontendDomainRule::TYPE_ADT => Money::zero($platform->currency_code),
            FrontendDomainRule::TYPE_CLD => Money::zero($platform->currency_code),
            FrontendDomainRule::TYPE_INF => Money::zero($platform->currency_code)
        ];

        if ($ruleId) {
            /** @var FrontendDomainRule $rule */
            $rule = FrontendDomainRule::find($ruleId);
            $agencyFee = $rule->getAgencyFee($totalPrice);
            $cashFee = $rule->getCashFee($totalPrice);
            $intesaFee = $rule->getIntesaFee($totalPrice);
        } else {
            $agencyFee = $platform->getAgencyFee();
        }

        foreach ($passengers as $passenger) {
            $agencyFeeAmounts[$passenger['type']] = $agencyFee;
            $fee = $fee->plus($agencyFee->multipliedBy($passenger['count']));
        }

        if ($ruleId) {
            $fee = $fee->plus($rule->getIntesaFee($totalPrice))->plus($rule->getCashFee($totalPrice));
        }

        $return = [
            '_totalPrice' => $totalPrice,
            'totalPrice' => $totalPrice->plus($fee),
            'agencyCharge' => [
                'totalPrice' => $fee,
                'byPassenger' => $agencyFeeAmounts,
            ],
            'paymentOptionCharge' => [
                'cash' => $cashFee,
                'intesa' => $intesaFee,
                'paypal' => $payPalFee,
            ]
        ];

        return $return;

    }

    /**
     * @param Collection $aiePriceRsp
     * @param FlightsSearchResult $result
     * @throws \Brick\Money\Exception\MoneyMismatchException
     */
    public function covertFlightInfo(Collection $aiePriceRsp, FlightsSearchResult $result)
    {
        $oldPrice = Money::of($aiePriceRsp->get('priceStatus')['oldValue']['amount'], $aiePriceRsp->get('priceStatus')['oldValue']['currency']);
        $newPrice = Money::of($aiePriceRsp->get('priceStatus')['newValue']['amount'], $aiePriceRsp->get('priceStatus')['newValue']['currency']);

        $resultOld = $this->coverTotalPrice($oldPrice, $result->request->data['passengers'], $result->rule_id);
        $resultNew = $this->coverTotalPrice($newPrice, $result->request->data['passengers'], $result->rule_id);

        $aiePriceRsp->put('priceStatus', [
            "changed" => !$newPrice->isEqualTo($oldPrice),
            'oldValue' => [
                'amount' => $resultOld['totalPrice']->getAmountAsFloat(),
                'currency' => $resultOld['totalPrice']->getCurrency()->getCurrencyCode()
            ],
            'newValue' => [
                'amount' => $resultNew['totalPrice']->getAmountAsFloat(),
                'currency' => $resultNew['totalPrice']->getCurrency()->getCurrencyCode()
            ],
        ]);

    }

    public function coverLowFareSearch($LowFareSearchResponse)
    {
        $platform = App::make('platform');
        $rules = $platform->rules;
        $LowFareSearchResponse->get('results')->get('groupsData')->get('prices')->transform(function ($item, $key) use (
            $LowFareSearchResponse,
            $platform,
            $rules
        ) {
            $suitableRuleId = null;
            $totalPrice = Money::of($item['totalPrice']['amount'], $item['totalPrice']['currency']);
            foreach ($item['passengerFares'] as $passengerFare) {
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
                            $suitableRuleId = $rule->id;
                            FlightsSearchResult::whereIn('id', $LowFareSearchResponse->get('mapPriceToIdes')[$key])->update(['rule_id' => $rule->id]);
                            break 2;
                        }
                    }
                }
            }

            $result = $this->coverTotalPrice($totalPrice, $item['passengerFares'], $suitableRuleId);

            $item['agencyCharge'] = [
                'amount' => $result['agencyCharge']['totalPrice']->getAmountAsFloat(),
                'currency' => $result['agencyCharge']['totalPrice']->getCurrency()->getCurrencyCode(),
                'regular' => array_map(function (Money $value) {
                    return $value->getAmountAsFloat();
                }, $result['agencyCharge']['byPassenger']),
                'brand' => array_map(function (Money $value) {
                    return $value->getAmountAsFloat();
                }, $result['agencyCharge']['byPassenger']),
            ];
            $item['totalPrice'] = [
                'amount' => $result['totalPrice']->getAmountAsFloat(),
                'currency' => $result['totalPrice']->getCurrency()->getCurrencyCode()
            ];
            $item['_totalPrice'] = [
                'amount' => $result['_totalPrice']->getAmountAsFloat(),
                'currency' => $result['_totalPrice']->getCurrency()->getCurrencyCode()
            ];

            return $item;
        });
    }

    public function coverCheckout(Collection $airPriceResult, ?int $ruleId)
    {
        /** @var FrontendDomain $platform */
        foreach ($airPriceResult->get('results')->get('groupsData')->get('prices') as $key1 => $prices) {
            foreach ($prices['airSolution'] as $key2 => $airSolution) {
                $totalPrice = Money::of($airSolution['totalPrice']['amount'], $airSolution['totalPrice']['currency']);

                $result = $this->coverTotalPrice($totalPrice, $airSolution['airPricingInfo'], $ruleId);

                $airSolution['agencyCharge'] = [
                    'amount' => $result['agencyCharge']['totalPrice']->getAmountAsFloat(),
                    'currency' => $result['agencyCharge']['totalPrice']->getCurrency()->getCurrencyCode(),
                    'regular' => array_map(function (Money $value) {
                        return $value->getAmountAsFloat();
                    }, $result['agencyCharge']['byPassenger']),
                    'brand' => array_map(function (Money $value) {
                        return $value->getAmountAsFloat();
                    }, $result['agencyCharge']['byPassenger']),
                ];
                $airSolution['totalPrice'] = [
                    'amount' => $result['totalPrice']->getAmountAsFloat(),
                    'currency' => $result['totalPrice']->getCurrency()->getCurrencyCode()
                ];
                $airSolution['paymentOptionCharge'] = [
                    'cash' => [
                        'amount' => $result['paymentOptionCharge']['cash']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['cash']->getCurrency()->getCurrencyCode()
                    ],
                    'intesa' => [
                        'amount' => $result['paymentOptionCharge']['intesa']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['intesa']->getCurrency()->getCurrencyCode()
                    ],
                    'paypal' => [
                        'amount' => $result['paymentOptionCharge']['paypal']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['paypal']->getCurrency()->getCurrencyCode()
                    ]
                ];

                $prices['airSolution'][$key2] = $airSolution;
            }

            $airPriceResult->get('results')->get('groupsData')->get('prices')[$key1] = $prices;
        }
    }

    public function coverReservationRequest(AirReservationRequestDto $dto)
    {
        $airSolution = $dto->getAirSolution();
        $result = $dto->getOrder()->result;
        $totalPrice = $this->moneyService->getMoneyByString($airSolution->getTotalPrice());
        $approximateTotalPrice = $this->moneyService->getMoneyByString($airSolution->getApproximateTotalPrice());

        $newTotalPrice = $this->coverTotalPrice($totalPrice, $result->request->data['passengers'], $result->rule_id);
        $newApproximateTotalPrice = $this->coverTotalPrice($approximateTotalPrice, $result->request->data['passengers'], $result->rule_id);
        $airSolution->setTotalPrice($newTotalPrice['totalPrice']->getConcatValue());
        $airSolution->setApproximateTotalPrice($newApproximateTotalPrice['totalPrice']->getConcatValue());

        return $newTotalPrice;
    }

    public function coverReservationResponse(Collection $reservation, Money $totalPrice, ?int $rule_id)
    {
        $passengers = $reservation->get('passengersCount')->map(function ($item, $key) {
            return [
                'type' => $key,
                'count' => $item,
            ];
        });

        $result = $this->coverTotalPrice($totalPrice, $passengers->toArray(), $rule_id);

        $reservation->get('universalRecord')['agencyCharge'] = [
            'amount' => $result['agencyCharge']['totalPrice']->getAmountAsFloat(),
            'currency' => $result['agencyCharge']['totalPrice']->getCurrency()->getCurrencyCode(),
            'regular' => array_map(function (Money $value) {
                return $value->getAmountAsFloat();
            }, $result['agencyCharge']['byPassenger']),
            'brand' => array_map(function (Money $value) {
                return $value->getAmountAsFloat();
            }, $result['agencyCharge']['byPassenger']),
        ];

        $reservation->put('universalRecord', array_merge(
            [
                'agencyCharge' => [
                    'cash' => [
                        'amount' => $result['paymentOptionCharge']['cash']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['cash']->getCurrency()->getCurrencyCode()
                    ],
                    'intesa' => [
                        'amount' => $result['paymentOptionCharge']['intesa']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['intesa']->getCurrency()->getCurrencyCode()
                    ],
                    'paypal' => [
                        'amount' => $result['paymentOptionCharge']['paypal']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['paypal']->getCurrency()->getCurrencyCode()
                    ]
                ],
                'paymentOptionCharge' => [
                    'cash' => [
                        'amount' => $result['paymentOptionCharge']['cash']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['cash']->getCurrency()->getCurrencyCode()
                    ],
                    'intesa' => [
                        'amount' => $result['paymentOptionCharge']['intesa']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['intesa']->getCurrency()->getCurrencyCode()
                    ],
                    'paypal' => [
                        'amount' => $result['paymentOptionCharge']['paypal']->getAmountAsFloat(),
                        'currency' => $result['paymentOptionCharge']['paypal']->getCurrency()->getCurrencyCode()
                    ]
                ]
            ],
            $reservation->get('universalRecord')
        ));

        $reservation->put('totalPrice', [
            'amount' => $result['totalPrice']->getAmountAsFloat(),
            'currency' => $result['totalPrice']->getCurrency()->getCurrencyCode()
        ]);
    }


}