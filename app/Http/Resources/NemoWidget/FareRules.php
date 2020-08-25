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
                        'agencyRules' => '?',
                        'canBeTranslated' => '?',
                        'manualRulesArray' => '?',
                        'tariffRules' => [
                            $this->resource
                        ]
                    ]
                ]
            ]
        ];
    }
}