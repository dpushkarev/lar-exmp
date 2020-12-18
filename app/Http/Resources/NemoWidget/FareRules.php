<?php

namespace App\Http\Resources\NemoWidget;


/**
 * Class FareRules
 * @package App\Http\Resources
 */
class FareRules extends AbstractResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'flights' => [
                'utils' => [
                    'rules' => [
                        'agencyRules' => '<a target="_blank" href="https://ekarte.rs/uslovi-koriscenja/">Uslovi korišćenja</a> | <a target="_blank" href="https://ekarte.rs/politika-privatnosti/">Politika privatnosti</a>',
                        'canBeTranslated' => false,
                        'manualRulesArray' => null,
                        'tariffRules' => $this->resource
                    ]
                ]
            ]
        ];
    }
}