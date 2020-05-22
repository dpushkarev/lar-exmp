<?php

namespace App\Http\Resources\NemoWidget;

use App\Http\Resources\NemoWidget\Common\AirlineList;
use App\Http\Resources\NemoWidget\Common\CountryList;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class NemoWidgetAirport
 * @package App\Http\Resources
 */
class AirlinesAll extends JsonResource
{
    public static $wrap;

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
            'system' => new System([])
        ];
    }
}