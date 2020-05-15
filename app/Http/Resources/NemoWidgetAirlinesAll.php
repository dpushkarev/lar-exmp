<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class NemoWidgetAirport
 * @package App\Http\Resources
 */
class NemoWidgetAirlinesAll extends JsonResource
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
                "airlines" => new NemoWidgetAirlineList($this->resource->get('airlines')),
                "countries" => new NemoWidgetCountryList($this->resource->get('countries')),
            ],
            'system' => new NemoWidgetSystem(1)
        ];
    }
}