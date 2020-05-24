<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirlineList;
use App\Http\Resources\NemoWidget\Common\CountryList;

/**
 * Class NemoWidgetAirport
 * @package App\Http\Resources
 */
class AirlinesAll extends AbstractResource
{

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'guide' => [
                "airlines" => new AirlineList($this->resource->get('airlines')),
                "countries" => new CountryList($this->resource->get('countries')),
            ],
        ];
    }
}