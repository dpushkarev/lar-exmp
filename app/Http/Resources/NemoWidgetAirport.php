<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NemoWidgetAirport extends JsonResource
{

    static public $airportCollection = [];

    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "IATA" => $this->resource->code,
            "cityId" => $this->resource->city->id,
            "isAggregation" => false,
            "airportRating" => null,
            "baseType" => "airport",
            "properName" => null,
            "properNameEn" => null,
            "name" => __($this->resource->name),
            "nameEn" => $this->resource->name,
            "countryCode" => $this->resource->country_code,
        ];
    }
}